<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Proteksi: Hanya Admin & Pengawas yang bisa akses data SOS
if (!isset($_SESSION['id_user']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'pengawas')) {
    echo json_encode([]);
    exit;
}

try {
    // Ambil semua notifikasi kategori 'alert' (SOS) yang belum dibaca
    // Kita join dengan tabel users untuk dapat nama dan foto petugas
    $sql = "SELECT n.*, u.nama_lengkap, u.foto 
            FROM notifications n 
            JOIN users u ON n.user_id = u.id 
            WHERE n.category = 'alert' 
            AND n.is_read = 0 
            ORDER BY n.created_at DESC";
            
    $stmt = $pdo->query($sql);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($alerts);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}