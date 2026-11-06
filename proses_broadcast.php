<?php
require_once 'config/database.php';
require_once 'config/helper_notif.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';

auth_session_start();
auth_require_login('index.php?page=login');
auth_require_roles(['admin', 'administrator']);

$act = $_POST['act'] ?? '';

if ($act == 'send_broadcast') {
    if (!csrf_validate_request()) {
        http_response_code(419);
        exit('CSRF token tidak valid');
    }

    $judul    = trim((string)($_POST['judul'] ?? ''));
    $pesan    = trim((string)($_POST['pesan'] ?? ''));
    $kategori = trim((string)($_POST['kategori'] ?? ''));

    // --- BAGIAN YANG DIPERBAIKI (Penambahan 'broadcast_alert') ---
    if ($judul === '' || $pesan === '' || !in_array($kategori, ['info', 'warning', 'alert', 'broadcast_alert'], true)) {
        header("Location: index.php?page=broadcast&status=invalid");
        exit;
    }

    try {
        // 1. Ambil SEMUA ID User yang statusnya Aktif
        $stmt = $pdo->query("SELECT id FROM users WHERE status = 'Aktif'");
        $all_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($all_users) {
            // 2. Kirim notifikasi ke setiap user dalam loop
            sendNotification(
                $pdo, 
                $all_users, // Parameter array ID
                $kategori, 
                "[BROADCAST] $judul", 
                $pesan, 
                'index.php?page=notifikasi' 
            );

            // 3. Beri info ke Admin kalau broadcast sudah selesai
            header("Location: index.php?page=broadcast&status=success&count=" . count($all_users));
            exit;
        } else {
            header("Location: index.php?page=broadcast&status=no_users");
            exit;
        }

    } catch (Exception $e) {
        header("Location: index.php?page=broadcast&status=error");
        exit;
    }
} else {
    header("Location: index.php?page=broadcast");
    exit;
}