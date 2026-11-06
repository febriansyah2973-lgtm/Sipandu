<?php
require_once 'config/database.php';
require_once 'config/helper_notif.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';

header('Content-Type: application/json');

auth_session_start();
$id_user = auth_current_user_id();
if ($id_user <= 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthenticated']);
    exit;
}

$act = $_POST['act'] ?? '';
$nama_petugas = auth_current_name();

if ($act == 'panic_button') {
    if (!csrf_validate_request()) {
        http_response_code(419);
        echo json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid']);
        exit;
    }

    $koordinat = trim((string)($_POST['koordinat'] ?? 'Lokasi tidak terdeteksi'));
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
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim SOS']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid']);
?>