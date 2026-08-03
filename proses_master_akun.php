<?php
session_start();
require_once 'config/database.php';

$act = $_GET['act'] ?? $_POST['act'] ?? '';
$targetDir = "assets/img/users/";

if (!file_exists($targetDir)) { 
    mkdir($targetDir, 0777, true); 
}

try {
    // --- 1. UPDATE STATUS (DENGAN PROTEKSI DIRI SENDIRI) ---
    if ($act == 'update_status') {
        $id = $_POST['id'];
        
        // PROTEKSI BETON: Cek apakah ID target sama dengan ID Login
        if ($id == $_SESSION['id_user']) {
            echo "error_self"; // Kirim sinyal error ke Javascript
            exit;
        }

        $statusBaru = $_POST['status_baru'];
        $isActive = ($statusBaru === 'Aktif') ? 1 : 0;

        // Update Sinkronisasi 3 Kolom
        $stmt = $pdo->prepare("UPDATE users SET status = ?, status_akun = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$statusBaru, $statusBaru, $isActive, $id]);

        echo "success";
        exit;
    }

    // --- 2. TAMBAH AKUN BARU ---
    if ($act == 'tambah') {
        $nama     = htmlspecialchars($_POST['nama']);
        $username = htmlspecialchars($_POST['username']);
        $pass     = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role     = $_POST['role'] ?? 'User'; 
        $status   = $_POST['status'] ?? 'Aktif';
        $isActive = ($status === 'Aktif') ? 1 : 0;
        
        $fotoNama = null;
        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "user_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
        }

        $sql = "INSERT INTO users (nama_lengkap, username, password, role, status, status_akun, is_active, foto, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $username, $pass, $role, $status, $status, $isActive, $fotoNama]);

        header("Location: index.php?page=master_akun&msg=added");
    
    // --- 3. EDIT AKUN ---
    } elseif ($act == 'edit') {
        $id       = $_POST['id'];
        
        // Proteksi: Jangan biarkan edit status diri sendiri jadi Nonaktif lewat form edit
        if ($id == $_SESSION['id_user'] && isset($_POST['status']) && $_POST['status'] == 'Nonaktif') {
             // Paksa tetap aktif jika user mencoba bunuh diri
             $status = 'Aktif';
             $isActive = 1;
        } else {
             $status   = $_POST['status'] ?? 'Aktif';
             $isActive = ($status === 'Aktif') ? 1 : 0;
        }

        $nama     = htmlspecialchars($_POST['nama']);
        $username = htmlspecialchars($_POST['username']);
        $role     = $_POST['role'] ?? 'User';

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

        header("Location: index.php?page=master_akun&msg=updated");

    // --- 4. HAPUS AKUN ---
    } elseif ($act == 'hapus') {
        $id = $_GET['id'];
        
        if ($id == $_SESSION['id_user']) {
            echo "<script>alert('Eror: Tidak bisa menghapus akun sendiri!'); window.location='index.php?page=master_akun';</script>";
            exit;
        }

        $stmt = $pdo->prepare("SELECT foto FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists($targetDir . $img)) { unlink($targetDir . $img); }
        
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        
        header("Location: index.php?page=master_akun&msg=deleted");
    }

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>