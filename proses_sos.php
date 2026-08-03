<?php
session_start();
require_once 'config/database.php';
require_once 'config/helper_notif.php';

header('Content-Type: application/json');

$act = $_POST['act'] ?? '';
$id_user = $_SESSION['id_user']; // ID Petugas (Pengirim)
$nama_petugas = $_SESSION['nama_lengkap'];

if ($act == 'panic_button') {
    $koordinat = $_POST['koordinat'] ?? 'Lokasi tidak terdeteksi';
    $waktu = date('H:i:s');

    try {
        // 1. Ambil ID Semua Admin & Pengawas (Penerima)
        $penerima = getAdminIds($pdo);

        // 2. Kirim Notifikasi dengan menyertakan sender_id ($id_user)
        sendNotification(
            $pdo, 
            $penerima, 
            'alert', 
            '?? DARURAT: SOS DITEKAN!', 
            "PETUGAS $nama_petugas MINTA BANTUAN SEGERA! Lokasi: $koordinat jam $waktu.", 
            "index.php?page=monitoring_patroli",
            $id_user // <--- INI PENTING: Mengirim ID Febri sebagai pengirim
        );

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>