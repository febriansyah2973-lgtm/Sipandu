<?php
session_start();
require_once 'config/database.php';
require_once 'config/helper_notif.php';

if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) != 'admin') {
    die("Akses Ilegal!");
}

$act = $_POST['act'] ?? '';

try {
    if ($act == 'tambah' || $act == 'edit') {
        $id       = $_POST['id'] ?? null;
        $username = trim($_POST['username']);
        $nama     = trim($_POST['nama']);
        $role     = $_POST['role'];
        $status   = $_POST['status']; 
        
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
        } 
        else if ($act == 'edit') {
            // UPDATE DATA
            $sql = "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, status_akun = ? $sql_pass WHERE id = ?";
            $params[] = $id; // Masukkan ID untuk klausa WHERE
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            header("Location: index.php?page=master_akun&status=update_success");
        }
    }
} catch (Exception $e) {
    die("Kesalahan Sistem: " . $e->getMessage());
}