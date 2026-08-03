<?php
session_start();
require_once 'config/database.php';
$aksi = $_REQUEST['aksi'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $aksi == 'tambah') {
    $user_id = $_POST['user_id'];
    $tanggal = $_POST['tanggal'];
    $jam_masuk = $_POST['jam_masuk'];
    $keterangan = $_POST['keterangan'];
    $jam_masuk_full = $jam_masuk . ":00";

    try {
        $sql = "INSERT INTO presensi (user_id, tanggal, jam_masuk, keterangan, status) VALUES (?, ?, ?, ?, 'Hadir Manual')";
        $pdo->prepare($sql)->execute([$user_id, $tanggal, $jam_masuk_full, $keterangan]);
        
        echo "<script>alert('Data berhasil ditambahkan!'); window.location='index.php?page=presensi_monitor';</script>";
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $aksi == 'edit') {
    $id = $_POST['id'];
    $jam_masuk = $_POST['jam_masuk'];
    $jam_pulang = !empty($_POST['jam_pulang']) ? $_POST['jam_pulang'] . ":00" : NULL;
    $keterangan = $_POST['keterangan'];

    try {
        $sql = "UPDATE presensi SET jam_masuk = ?, jam_pulang = ?, keterangan = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$jam_masuk, $jam_pulang, $keterangan, $id]);

        echo "<script>alert('Data berhasil diupdate!'); window.location='index.php?page=presensi_monitor';</script>";
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }

} elseif ($aksi == 'hapus' && isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $sql = "DELETE FROM presensi WHERE id = ?";
        $pdo->prepare($sql)->execute([$id]);

        echo "<script>alert('Data berhasil dihapus!'); window.location='index.php?page=presensi_monitor';</script>";
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: index.php?page=presensi_monitor");
}
?>