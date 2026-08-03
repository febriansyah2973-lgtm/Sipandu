<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_user'])) exit;

$category = $_POST['category'] ?? '';
$note = $_POST['note'] ?? 'Tanpa catatan.';

if ($category === 'alert') {
    try {
        // Update: Tandai Selesai + Simpan Catatan Penanganan
        $sql = "UPDATE notifications 
                SET is_read = 1, 
                    handling_note = ? 
                WHERE category = 'alert' AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$note]);
        
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error']);
    }
}