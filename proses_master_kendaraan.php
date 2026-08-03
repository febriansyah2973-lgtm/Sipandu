<?php
session_start();
require_once 'config/database.php';

$act = $_GET['act'] ?? $_POST['act'];
$targetDir = "assets/img/kendaraan/";
if (!file_exists($targetDir)) { mkdir($targetDir, 0777, true); }

try {
    if ($act == 'tambah') {
        $nopol  = strtoupper($_POST['no_polisi']);
        $jenis  = $_POST['jenis'];
        $merk   = $_POST['merk_model'];
        $status = $_POST['status'];
        $ket    = $_POST['keterangan'];
        $fotoNama = null;

        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "veh_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
        }

        $sql = "INSERT INTO kendaraan (no_polisi, jenis, merk_model, status, keterangan, foto_kendaraan) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nopol, $jenis, $merk, $status, $ket, $fotoNama]);

        header("Location: index.php?page=master_kendaraan");

    } elseif ($act == 'edit') {
        $id     = $_POST['id'];
        $nopol  = strtoupper($_POST['no_polisi']);
        $jenis  = $_POST['jenis'];
        $merk   = $_POST['merk_model'];
        $status = $_POST['status'];
        $ket    = $_POST['keterangan'];

        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "veh_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
            $stmt = $pdo->prepare("SELECT foto_kendaraan FROM kendaraan WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetchColumn();
            if ($old && file_exists($targetDir . $old)) { unlink($targetDir . $old); }

            $sql = "UPDATE kendaraan SET no_polisi=?, jenis=?, merk_model=?, status=?, keterangan=?, foto_kendaraan=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nopol, $jenis, $merk, $status, $ket, $fotoNama, $id]);
        } else {
            $sql = "UPDATE kendaraan SET no_polisi=?, jenis=?, merk_model=?, status=?, keterangan=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nopol, $jenis, $merk, $status, $ket, $id]);
        }

        header("Location: index.php?page=master_kendaraan");

    } elseif ($act == 'hapus') {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT foto_kendaraan FROM kendaraan WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists($targetDir . $img)) { unlink($targetDir . $img); }

        $pdo->prepare("DELETE FROM kendaraan WHERE id=?")->execute([$id]);
        header("Location: index.php?page=master_kendaraan");
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>