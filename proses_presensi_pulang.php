<?php
session_start();
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_absen   = $_POST['id_absen'];
    $image_data = $_POST['image_data'];
    $keterangan = $_POST['keterangan_pulang'];
    $lat        = $_POST['lat'];
    $lng        = $_POST['lng'];
    $jam_pulang = date('H:i:s');
    $image_parts  = explode(";base64,", $image_data);
    $image_base64 = base64_decode($image_parts[1]);
    $filename     = "pulang_" . $_SESSION['user_id'] . "_" . time() . ".jpg";
    $folderPath   = "assets/img/presensi/";
    
    if (!file_exists($folderPath)) { mkdir($folderPath, 0777, true); }
    file_put_contents($folderPath . $filename, $image_base64);

    try {
        $sql = "UPDATE presensi SET 
                jam_pulang = ?, 
                foto_wajah_pulang = ?, 
                lat_pulang = ?,
                lng_pulang = ?,
                keterangan_pulang = ? 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jam_pulang, $filename, $lat, $lng, $keterangan, $id_absen]);

        echo "<script>alert('Terima kasih! Absen Pulang Berhasil.'); window.location='index.php?page=dashboard';</script>";
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>