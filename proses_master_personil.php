<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
require_once 'config/auth.php';
require_once 'config/audit.php';

auth_session_start();
$current_user_id = auth_require_login();
auth_require_roles(['admin', 'administrator']);

if (!csrf_validate_request()) {
    http_response_code(419);
    exit('CSRF token tidak valid');
}

$act = $_GET['act'] ?? $_POST['act'] ?? '';

try {
    if ($act == 'tambah') {
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $user = trim($_POST['username'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        $role_form = trim($_POST['role'] ?? '');
        $stat = trim($_POST['status'] ?? '');

        if ($nama === '' || $user === '' || $pass === '' || $role_form === '' || $stat === '') {
            header("Location: index.php?page=master_personil&status=invalid");
            exit;
        }
        
        // --- Tangkap nilai Cabang & Shift ---
        $cabang_id = !empty($_POST['cabang_id']) ? (int)$_POST['cabang_id'] : null;
        $shift_id  = !empty($_POST['shift_id'])  ? (int)$_POST['shift_id']  : null;
        // ----------------------------------------------

        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
        $cek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $cek->execute([$user]);
        if($cek->rowCount() > 0) {
            echo "<script>alert('Username/NIP sudah terdaftar!'); window.history.back();</script>";
            exit;
        }

        // --- Update query INSERT ---
        $sql = "INSERT INTO users (nama_lengkap, username, password, role, status, cabang_id, shift_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $user, $hashed_password, $role_form, $stat, $cabang_id, $shift_id]);
        $lastId = $pdo->lastInsertId();
        $kode   = 'USR-' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE users SET kode_personil = ? WHERE id = ?")->execute([$kode, $lastId]);
        audit_log_event('personil.create', ['target_id' => (int)$lastId, 'username' => $user, 'role' => $role_form]);

        header("Location: index.php?page=master_personil");
        exit;
        
    } elseif ($act == 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $user = trim($_POST['username'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        $role_form = trim($_POST['role'] ?? '');
        $stat = trim($_POST['status'] ?? '');

        if ($id <= 0 || $nama === '' || $user === '' || $role_form === '' || $stat === '') {
            header("Location: index.php?page=master_personil&status=invalid");
            exit;
        }
        
        // --- Tangkap nilai Cabang & Shift ---
        $cabang_id = !empty($_POST['cabang_id']) ? (int)$_POST['cabang_id'] : null;
        $shift_id  = !empty($_POST['shift_id'])  ? (int)$_POST['shift_id']  : null;
        // ----------------------------------------------

        if (!empty($pass)) {
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            // --- Update query UPDATE (Dengan Password) ---
            $sql = "UPDATE users SET nama_lengkap=?, username=?, password=?, role=?, status=?, cabang_id=?, shift_id=? WHERE id=?";
            $params = [$nama, $user, $hashed_password, $role_form, $stat, $cabang_id, $shift_id, $id];
        } else {
            // --- Update query UPDATE (Tanpa Password) ---
            $sql = "UPDATE users SET nama_lengkap=?, username=?, role=?, status=?, cabang_id=?, shift_id=? WHERE id=?";
            $params = [$nama, $user, $role_form, $stat, $cabang_id, $shift_id, $id];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        audit_log_event('personil.update', ['target_id' => $id, 'username' => $user, 'role' => $role_form]);

        header("Location: index.php?page=master_personil");
        exit;
        
    } elseif ($act == 'mutasi') {
        // --- LOGIKA MUTASI AREA (BARU) ---
        $redirect_page = in_array($_POST['redirect_page'] ?? '', ['master_akun', 'master_personil'], true)
            ? $_POST['redirect_page']
            : 'master_personil';
        $user_id = (int)($_POST['id'] ?? 0);
        $cabang_asal_id = !empty($_POST['cabang_asal_id']) ? (int)$_POST['cabang_asal_id'] : null;
        $cabang_tujuan_id = (int)($_POST['cabang_tujuan_id'] ?? 0);
        $tanggal_efektif = $_POST['tanggal_efektif'] ?? '';
        $keterangan = trim($_POST['keterangan'] ?? '');
        $created_by = auth_current_name() ?: 'System';

        if ($user_id <= 0 || $cabang_tujuan_id <= 0 || $tanggal_efektif === '') {
            header("Location: index.php?page=$redirect_page&status=invalid");
            exit;
        }

        $pdo->beginTransaction();
        
        try {
            // 1. Masukkan ke tabel Riwayat Sejarah
            $sql_riwayat = "INSERT INTO riwayat_mutasi (user_id, cabang_asal_id, cabang_tujuan_id, tanggal_efektif, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql_riwayat);
            $stmt->execute([$user_id, $cabang_asal_id, $cabang_tujuan_id, $tanggal_efektif, $keterangan, $created_by]);

            // 2. Update Cabang Utama di Tabel Users
            $sql_update = "UPDATE users SET cabang_id = ? WHERE id = ?";
            $stmt2 = $pdo->prepare($sql_update);
            $stmt2->execute([$cabang_tujuan_id, $user_id]);
            audit_log_event('personil.mutasi', [
                'target_id' => $user_id,
                'cabang_asal_id' => $cabang_asal_id,
                'cabang_tujuan_id' => $cabang_tujuan_id,
                'tanggal_efektif' => $tanggal_efektif
            ]);

            $pdo->commit();

            // SweetAlert Redirect via JavaScript (Biar Keren)
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<body style='background:#f4f6f9;'></body>";
            echo "<script>
                Swal.fire('Mutasi Berhasil!', 'Petugas telah dipindahkan ke cabang baru.', 'success')
                .then(() => { window.location.href='index.php?page=$redirect_page'; });
            </script>";
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: index.php?page=$redirect_page&status=error");
            exit;
        }

    } elseif ($act == 'hapus') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: index.php?page=master_personil&status=invalid");
            exit;
        }

        if ($id === $current_user_id) {
            header("Location: index.php?page=master_personil&status=self_delete_blocked");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        audit_log_event('personil.delete', ['target_id' => $id]);
        
        header("Location: index.php?page=master_personil");
        exit;
    }

} catch (PDOException $e) {
    header("Location: index.php?page=master_personil&status=error");
    exit;
}

header("Location: index.php?page=master_personil");
exit;
?>