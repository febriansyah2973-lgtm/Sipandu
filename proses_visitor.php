<?php
require_once 'config/database.php';
require_once 'config/helper_notif.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/audit.php';

auth_session_start();
$id_user = auth_require_login('index.php?page=login');

$act = $_GET['act'] ?? $_POST['act'] ?? '';

if ($act == 'tambah_tamu') {
    if (!csrf_validate_request()) {
        http_response_code(419);
        exit('CSRF token tidak valid');
    }

    $nama_tamu = trim((string)($_POST['nama_tamu'] ?? ''));
    $instansi  = trim((string)($_POST['instansi'] ?? ''));
    $tujuan    = trim((string)($_POST['tujuan_bertemu'] ?? ''));
    $keperluan = trim((string)($_POST['keperluan'] ?? ''));

    if ($nama_tamu === '' || $instansi === '' || $tujuan === '' || $keperluan === '') {
        audit_log_event('visitor.submit.invalid', []);
        header("Location: index.php?page=input_visitor&status=invalid");
        exit;
    }
    
    $sql = "INSERT INTO visitors (nama_tamu, instansi, tujuan_bertemu, keperluan, waktu_masuk, petugas_id) 
            VALUES (?, ?, ?, ?, NOW(), ?)";
    
    if ($pdo->prepare($sql)->execute([$nama_tamu, $instansi, $tujuan, $keperluan, $id_user])) {
        audit_log_event('visitor.submit', [
            'nama_tamu' => $nama_tamu,
            'instansi' => $instansi,
            'tujuan_bertemu' => $tujuan
        ]);
        
        // [NOTIFIKASI] Beritahu Admin ada tamu datang
        $admins = getAdminIds($pdo);
        sendNotification(
            $pdo, 
            $admins, 
            'info', 
            'Tamu Baru Tiba ??', 
            "$nama_tamu dari $instansi datang untuk bertemu $tujuan.", 
            'index.php?page=laporan_visitor'
        );

        header("Location: index.php?page=input_visitor&status=success");
        exit;
    }
}

header('Location: index.php?page=input_visitor');
exit;
?>