<?php
// Cek session agar tidak error double start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function sendNotification($pdo, $user_id, $category, $title, $message, $link = '#') {
    try {
        $sql = "INSERT INTO notifications (user_id, sender_id, category, title, message, link, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        
        $sender = $_SESSION['id_user'] ?? NULL;

        if (is_array($user_id)) {
            foreach ($user_id as $uid) {
                $stmt->execute([$uid, $sender, $category, $title, $message, $link]);
            }
        } else {
            $stmt->execute([$user_id, $sender, $category, $title, $message, $link]);
        }
        return true;
    } catch (Exception $e) {
        // Silent error: Biar kalau gagal simpan notif, aplikasi tetap jalan (gak layar putih)
        return false;
    }
}

function getAdminIds($pdo) {
    // Mengambil ID semua admin dan pengawas
    $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'pengawas', 'Admin', 'Pengawas')");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}