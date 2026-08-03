<?php
session_start();
require_once 'config/database.php';

$act = $_POST['act'] ?? $_GET['act'] ?? '';

if ($act == 'tambah') {
    $nama = $_POST['nama_lokasi'];
    $lat  = $_POST['latitude'] ?: 0;
    $long = $_POST['longitude'] ?: 0;
    $ket  = $_POST['keterangan'];
    $kode = "LOC-" . time(); 

    $sql = "INSERT INTO checkpoints (nama_lokasi, kode_qr, latitude, longitude, keterangan) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $kode, $lat, $long, $ket]);
    header("Location: index.php?page=master_lokasi");

} elseif ($act == 'edit') {
    $id   = $_POST['id'];
    $nama = $_POST['nama_lokasi'];
    $lat  = $_POST['latitude'] ?: 0;
    $long = $_POST['longitude'] ?: 0;
    $ket  = $_POST['keterangan'];
    
    $sql = "UPDATE checkpoints SET nama_lokasi=?, latitude=?, longitude=?, keterangan=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $lat, $long, $ket, $id]);
    header("Location: index.php?page=master_lokasi");

} elseif ($act == 'hapus') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM checkpoints WHERE id=?");
    $stmt->execute([$id]);
    header("Location: index.php?page=master_lokasi");

} elseif ($act == 'update_gps') {
    header('Content-Type: application/json');
    $id  = $_POST['id'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];

    $sql = "UPDATE checkpoints SET latitude = ?, longitude = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $res = $stmt->execute([$lat, $lng, $id]);

    echo json_encode(['status' => ($res ? 'success' : 'error')]);
    exit;
}