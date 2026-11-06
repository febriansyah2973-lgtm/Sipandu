<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

$category = $_POST['category'] ?? 'alert';
$note = trim($_POST['note'] ?? 'Sirene dimatikan oleh petugas/pengawas.');

try {
    if ($category === 'alert' || empty($category)) {
        $sql = "UPDATE notifications 
                SET is_read = 1, 
                    handling_note = ? 
                WHERE category = 'alert' AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$note]);
    } else {
        $sql = "UPDATE notifications SET is_read = 1 WHERE is_read = 0";
        $pdo->query($sql);
    }
    
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}