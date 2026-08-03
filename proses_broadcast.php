<?php
session_start();
require_once 'config/database.php';
require_once 'config/helper_notif.php';

// Proteksi: Hanya Admin yang bisa akses file ini
if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) != 'admin') {
    header("Location: index.php?page=login");
    exit;
}

$act = $_POST['act'] ?? '';

if ($act == 'send_broadcast') {
    $judul    = htmlspecialchars($_POST['judul']);
    $pesan    = htmlspecialchars($_POST['pesan']);
    $kategori = $_POST['kategori']; // info, warning, atau alert
    $admin_nama = $_SESSION['nama_lengkap'];

    try {
        // 1. Ambil SEMUA ID User yang statusnya Aktif
        $stmt = $pdo->query("SELECT id FROM users WHERE status = 'Aktif'");
        $all_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($all_users) {
            // 2. Kirim notifikasi ke setiap user dalam loop
            // Kita gunakan fungsi sendNotification yang sudah ada di helper_notif.php
            sendNotification(
                $pdo, 
                $all_users, // Parameter array ID (fungsi kita sudah support array)
                $kategori, 
                "[BROADCAST] $judul", 
                $pesan, 
                'index.php?page=notifikasi' // Klik notif langsung ke riwayat
            );

            // 3. Beri info ke Admin kalau broadcast sudah selesai
            header("Location: index.php?page=broadcast&status=success&count=" . count($all_users));
            exit;
        } else {
            header("Location: index.php?page=broadcast&status=no_users");
            exit;
        }

    } catch (Exception $e) {
        die("Gagal Broadcast: " . $e->getMessage());
    }
} else {
    header("Location: index.php?page=broadcast");
    exit;
}