<?php
session_start();
require_once 'config/database.php';
require_once 'config/helper_notif.php';

$act = $_GET['act'] ?? $_POST['act'] ?? '';
$id_user = $_SESSION['id_user'];
$nama_petugas = $_SESSION['nama_lengkap'];

if ($act == 'tambah_tamu') {
    $nama_tamu = htmlspecialchars($_POST['nama_tamu']);
    $instansi  = htmlspecialchars($_POST['instansi']);
    $tujuan    = htmlspecialchars($_POST['tujuan_bertemu']);
    $keperluan = htmlspecialchars($_POST['keperluan']);
    
    $sql = "INSERT INTO visitors (nama_tamu, instansi, tujuan_bertemu, keperluan, waktu_masuk, petugas_id) 
            VALUES (?, ?, ?, ?, NOW(), ?)";
    
    if ($pdo->prepare($sql)->execute([$nama_tamu, $instansi, $tujuan, $keperluan, $id_user])) {
        
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
    }
}
?>