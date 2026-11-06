<?php
require_once 'config/database.php';
require_once 'config/helper_notif.php';
require_once 'config/csrf.php';
require_once 'config/auth.php';

auth_session_start();
auth_require_login('index.php?page=login');
auth_require_roles(['admin', 'administrator']);

if (!csrf_validate_request()) {
    http_response_code(419);
    exit("CSRF token tidak valid");
}

$act = $_POST['act'] ?? '';

try {
    if ($act == 'tambah' || $act == 'edit') {
        $id       = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $username = trim($_POST['username'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $role     = $_POST['role'] ?? '';
        $status   = $_POST['status'] ?? '';

        if ($username === '' || $nama === '' || $role === '' || $status === '') {
            header("Location: index.php?page=master_akun&status=invalid");
            exit;
        }
        
        $sql_pass = "";
        $params = [$username, $nama, $role, $status];

        // Jika ada password baru diinput
        if (!empty($_POST['password'])) {
            $hashed_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql_pass = ", password = ?";
            $params[] = $hashed_pass;
        }

        if ($act == 'tambah') {
            // INSERT BARU
            // Beri password default jika kosong saat tambah
            $final_pass = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : password_hash('123456', PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, nama_lengkap, role, status_akun, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $nama, $role, $status, $final_pass]);
            
            header("Location: index.php?page=master_akun&status=add_success");
            exit;
        } 
        else if ($act == 'edit') {
            if (empty($id) || $id <= 0) {
                header("Location: index.php?page=master_akun&status=invalid");
                exit;
            }

            // UPDATE DATA
            $sql = "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, status_akun = ? $sql_pass WHERE id = ?";
            $params[] = $id; // Masukkan ID untuk klausa WHERE
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            header("Location: index.php?page=master_akun&status=update_success");
            exit;
        }
    }
} catch (Exception $e) {
    header("Location: index.php?page=master_akun&status=error");
    exit;
}

header("Location: index.php?page=master_akun");
exit;