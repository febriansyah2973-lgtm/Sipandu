<?php
session_start();
require_once 'config/database.php';

$act = $_GET['act'] ?? $_POST['act'];

try {
    if ($act == 'tambah') {
        $nama = $_POST['nama_lengkap'];
        $user = $_POST['username'];
        $pass = $_POST['password']; 
        $role = $_POST['role'];
        $stat = $_POST['status'];
        
        // --- Tangkap nilai Cabang & Shift ---
        $cabang_id = !empty($_POST['cabang_id']) ? $_POST['cabang_id'] : null;
        $shift_id  = !empty($_POST['shift_id'])  ? $_POST['shift_id']  : null;
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
        $stmt->execute([$nama, $user, $hashed_password, $role, $stat, $cabang_id, $shift_id]);

        header("Location: index.php?page=master_personil");
        
    } elseif ($act == 'edit') {
        $id   = $_POST['id'];
        $nama = $_POST['nama_lengkap'];
        $user = $_POST['username'];
        $pass = $_POST['password'];
        $role = $_POST['role'];
        $stat = $_POST['status'];
        
        // --- Tangkap nilai Cabang & Shift ---
        $cabang_id = !empty($_POST['cabang_id']) ? $_POST['cabang_id'] : null;
        $shift_id  = !empty($_POST['shift_id'])  ? $_POST['shift_id']  : null;
        // ----------------------------------------------

        if (!empty($pass)) {
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            // --- Update query UPDATE (Dengan Password) ---
            $sql = "UPDATE users SET nama_lengkap=?, username=?, password=?, role=?, status=?, cabang_id=?, shift_id=? WHERE id=?";
            $params = [$nama, $user, $hashed_password, $role, $stat, $cabang_id, $shift_id, $id];
        } else {
            // --- Update query UPDATE (Tanpa Password) ---
            $sql = "UPDATE users SET nama_lengkap=?, username=?, role=?, status=?, cabang_id=?, shift_id=? WHERE id=?";
            $params = [$nama, $user, $role, $stat, $cabang_id, $shift_id, $id];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: index.php?page=master_personil");
        
    } elseif ($act == 'mutasi') {
        // --- LOGIKA MUTASI AREA (BARU) ---
        $user_id = $_POST['id'];
        $cabang_asal_id = !empty($_POST['cabang_asal_id']) ? $_POST['cabang_asal_id'] : null;
        $cabang_tujuan_id = $_POST['cabang_tujuan_id'];
        $tanggal_efektif = $_POST['tanggal_efektif'];
        $keterangan = $_POST['keterangan'];
        $created_by = $_SESSION['nama_lengkap'] ?? 'System';

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

            $pdo->commit();
            
            // SweetAlert Redirect via JavaScript (Biar Keren)
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<body style='background:#f4f6f9;'></body>";
            echo "<script>
                Swal.fire('Mutasi Berhasil!', 'Petugas telah dipindahkan ke cabang baru.', 'success')
                .then(() => { window.location.href='index.php?page=master_personil'; });
            </script>";
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error Proses Mutasi: " . $e->getMessage());
        }

    } elseif ($act == 'hapus') {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        
        header("Location: index.php?page=master_personil");
    }

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>