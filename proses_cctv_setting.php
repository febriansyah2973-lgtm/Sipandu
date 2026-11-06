<?php
require_once 'config/database.php';
require_once 'config/auth.php';

auth_session_start();
auth_require_login('index.php?page=login');
auth_require_roles(['admin', 'administrator']);

// 2. PROSES TAMBAH & EDIT KAMERA
if (isset($_POST['save_cctv'])) {
    $id      = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $nama    = trim((string)($_POST['nama'] ?? ''));
    $ip      = trim((string)($_POST['ip'] ?? ''));
    $port    = !empty($_POST['port']) ? (int)$_POST['port'] : 80;
    $user    = trim((string)($_POST['user'] ?? ''));
    $pass    = trim((string)($_POST['pass'] ?? ''));
    $channel = !empty($_POST['channel']) ? (int)$_POST['channel'] : 1;
    $status  = trim((string)($_POST['status'] ?? 'Aktif'));

    if ($nama === '' || $ip === '' || $user === '' || $pass === '' || $port < 1 || $port > 65535 || $channel < 1 || !in_array($status, ['Aktif', 'Nonaktif'], true)) {
        header("Location: index.php?page=cctv_setting&status=invalid");
        exit;
    }

    try {
        if ($id) {
            // LOGIKA EDIT
            $sql = "UPDATE cctv_list SET 
                    nama_cctv = ?, ip_address = ?, port_web = ?, 
                    username = ?, password = ?, channel = ?, status = ? 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $ip, $port, $user, $pass, $channel, $status, $id]);
            $msg = "updated";
        } else {
            // LOGIKA TAMBAH BARU
            $sql = "INSERT INTO cctv_list (nama_cctv, ip_address, port_web, username, password, channel, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $ip, $port, $user, $pass, $channel, $status]);
            $msg = "added";
        }

        // Pastikan nama page 'cctv_setting' (tanpa s) sesuai index.php
        header("Location: index.php?page=cctv_setting&status=$msg");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=cctv_setting&status=error");
        exit;
    }
}

// 3. PROSES HAPUS KAMERA
if (isset($_GET['delete_id'])) {
    $id = (int)($_GET['delete_id'] ?? 0);
    if ($id <= 0) {
        header("Location: index.php?page=cctv_setting&status=invalid");
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM cctv_list WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?page=cctv_setting&status=deleted");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=cctv_setting&status=error");
        exit;
    }
}

header('Location: index.php?page=cctv_setting');
exit;