<?php
session_start();
require_once '../config/database.php';

// Cek notif alert (SOS) yang belum dibaca dalam 1 menit terakhir
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications 
                           WHERE category = 'alert' 
                           AND is_read = 0 
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    echo json_encode(['emergency' => $count > 0]);
} catch (Exception $e) {
    echo json_encode(['emergency' => false]);
}