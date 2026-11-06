<?php
require_once 'config/database.php';
require_once 'config/auth.php';
date_default_timezone_set('Asia/Jakarta');

auth_session_start();
$userId = auth_require_login('index.php?page=login');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_absen   = (int)($_POST['id_absen'] ?? 0);
    $image_data = (string)($_POST['image_data'] ?? '');
    $keterangan = trim((string)($_POST['keterangan_pulang'] ?? ''));
    $lat        = trim((string)($_POST['lat'] ?? ''));
    $lng        = trim((string)($_POST['lng'] ?? ''));

    if ($id_absen <= 0 || $image_data === '' || $lat === '' || $lng === '') {
        header('Location: index.php?page=dashboard&status=invalid');
        exit;
    }

    $jam_pulang = date('H:i:s');
    $image_parts  = explode(";base64,", $image_data);
    if (count($image_parts) !== 2) {
        header('Location: index.php?page=dashboard&status=invalid_photo');
        exit;
    }

    $image_base64 = base64_decode($image_parts[1]);
    if ($image_base64 === false) {
        header('Location: index.php?page=dashboard&status=invalid_photo');
        exit;
    }

    $filename     = "pulang_" . $userId . "_" . time() . ".jpg";
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

        header('Location: index.php?page=dashboard&status=clockout_success');
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?page=dashboard&status=error');
        exit;
    }
}

header('Location: index.php?page=dashboard');
exit;
?>