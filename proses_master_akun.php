<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
require_once 'config/auth.php';
require_once 'config/audit.php';

auth_session_start();
$current_user_id = auth_require_login();
$role = auth_require_roles(['admin', 'administrator']);

if (!csrf_validate_request()) {
    http_response_code(419);
    exit('CSRF token tidak valid');
}

$act = $_GET['act'] ?? $_POST['act'] ?? '';
$targetDir = "assets/img/users/";

if (!file_exists($targetDir)) { 
    mkdir($targetDir, 0777, true); 
}

try {
    // --- 1. UPDATE STATUS (DENGAN PROTEKSI DIRI SENDIRI) ---
    if ($act == 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo "error";
            exit;
        }
        
        // PROTEKSI BETON: Cek apakah ID target sama dengan ID Login
        if ($id == $current_user_id) {
            echo "error_self"; // Kirim sinyal error ke Javascript
            exit;
        }

        $statusBaru = $_POST['status_baru'] ?? '';
        if (!in_array($statusBaru, ['Aktif', 'Nonaktif'], true)) {
            echo "error";
            exit;
        }
        $isActive = ($statusBaru === 'Aktif') ? 1 : 0;

        // Update Sinkronisasi 3 Kolom
        $stmt = $pdo->prepare("UPDATE users SET status = ?, status_akun = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$statusBaru, $statusBaru, $isActive, $id]);
        audit_log_event('akun.update_status', ['target_id' => $id, 'status' => $statusBaru]);

        echo "success";
        exit;
    }

    // --- 2. TAMBAH AKUN BARU ---
    if ($act == 'tambah') {
        $nama     = htmlspecialchars(trim($_POST['nama'] ?? ''));
        $username = htmlspecialchars(trim($_POST['username'] ?? ''));
        $rawPass  = (string)($_POST['password'] ?? '');
        $pass     = password_hash($rawPass, PASSWORD_DEFAULT);
        $role     = $_POST['role'] ?? 'User'; 
        $status   = $_POST['status'] ?? 'Aktif';
        $isActive = ($status === 'Aktif') ? 1 : 0;

        if ($nama === '' || $username === '' || $rawPass === '') {
            header("Location: index.php?page=master_akun&msg=invalid");
            exit;
        }
        
        $fotoNama = null;
        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "user_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
        }

        $sql = "INSERT INTO users (nama_lengkap, username, password, role, status, status_akun, is_active, foto, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $username, $pass, $role, $status, $status, $isActive, $fotoNama]);

        $lastId = $pdo->lastInsertId();
        $kode   = 'USR-' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE users SET kode_personil = ? WHERE id = ?")->execute([$kode, $lastId]);
        audit_log_event('akun.create', ['target_id' => (int)$lastId, 'username' => $username, 'role' => $role]);

        header("Location: index.php?page=master_akun&msg=added");
        exit;
    
    // --- 3. EDIT AKUN ---
    } elseif ($act == 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: index.php?page=master_akun&msg=invalid");
            exit;
        }
        
        // Proteksi: Jangan biarkan edit status diri sendiri jadi Nonaktif lewat form edit
        if ($id == $current_user_id && isset($_POST['status']) && $_POST['status'] == 'Nonaktif') {
             // Paksa tetap aktif jika user mencoba bunuh diri
             $status = 'Aktif';
             $isActive = 1;
        } else {
             $status   = $_POST['status'] ?? 'Aktif';
             $isActive = ($status === 'Aktif') ? 1 : 0;
        }

        $nama     = htmlspecialchars(trim($_POST['nama'] ?? ''));
        $username = htmlspecialchars(trim($_POST['username'] ?? ''));
        $role     = $_POST['role'] ?? 'User';

        if ($nama === '' || $username === '') {
            header("Location: index.php?page=master_akun&msg=invalid");
            exit;
        }

        $params = [$nama, $username, $role, $status, $status, $isActive];
        $sqlBase = "UPDATE users SET nama_lengkap=?, username=?, role=?, status=?, status_akun=?, is_active=?";

        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sqlBase .= ", password=?";
            $params[] = $pass;
        }

        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "user_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
            
            $stmt = $pdo->prepare("SELECT foto FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetchColumn();
            if ($old && file_exists($targetDir . $old)) { unlink($targetDir . $old); }

            $sqlBase .= ", foto=?";
            $params[] = $fotoNama;
        }

        $sqlBase .= " WHERE id=?";
        $params[] = $id;

        $stmt = $pdo->prepare($sqlBase);
        $stmt->execute($params);
        audit_log_event('akun.update', ['target_id' => $id, 'username' => $username, 'role' => $role]);

        header("Location: index.php?page=master_akun&msg=updated");
        exit;

    // --- 4. HAPUS AKUN ---
    } elseif ($act == 'hapus') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: index.php?page=master_akun&msg=invalid");
            exit;
        }
        
        if ($id == $current_user_id) {
            header("Location: index.php?page=master_akun&msg=self_delete_blocked");
            exit;
        }

        $stmt = $pdo->prepare("SELECT foto FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists($targetDir . $img)) { unlink($targetDir . $img); }
        
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        audit_log_event('akun.delete', ['target_id' => $id]);
        
        header("Location: index.php?page=master_akun&msg=deleted");
        exit;
    }

} catch (PDOException $e) {
    header("Location: index.php?page=master_akun&msg=error");
    exit;
}

header("Location: index.php?page=master_akun");
exit;
?>