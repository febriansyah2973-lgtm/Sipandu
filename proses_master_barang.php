<?php
session_start();
require_once 'config/database.php';

$act = $_GET['act'] ?? $_POST['act'];
$targetDir = "assets/img/barang/";
if (!file_exists($targetDir)) { mkdir($targetDir, 0777, true); }

try {
    if ($act == 'tambah') {
        $kode   = $_POST['kode_barang'];
        $nama   = $_POST['nama_barang'];
        $baik   = $_POST['jml_baik'];
        $rusak  = $_POST['jml_rusak'];
        $hilang = $_POST['jml_hilang'];
        $lokasi = $_POST['lokasi'];
        $fotoNama = null;

        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "brg_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
        }
        $sql = "INSERT INTO barang (kode_barang, nama_barang, jml_baik, jml_rusak, jml_hilang, lokasi, foto_barang) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kode, $nama, $baik, $rusak, $hilang, $lokasi, $fotoNama]);

        header("Location: index.php?page=master_barang");

    } elseif ($act == 'edit') {
        $id     = $_POST['id'];
        $nama   = $_POST['nama_barang'];
        $baik   = $_POST['jml_baik'];
        $rusak  = $_POST['jml_rusak'];
        $hilang = $_POST['jml_hilang'];
        $lokasi = $_POST['lokasi'];
        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "brg_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
            $stmt = $pdo->prepare("SELECT foto_barang FROM barang WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetchColumn();
            if ($old && file_exists($targetDir . $old)) { unlink($targetDir . $old); }

            $sql = "UPDATE barang SET nama_barang=?, jml_baik=?, jml_rusak=?, jml_hilang=?, lokasi=?, foto_barang=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $baik, $rusak, $hilang, $lokasi, $fotoNama, $id]);
        } else {
            $sql = "UPDATE barang SET nama_barang=?, jml_baik=?, jml_rusak=?, jml_hilang=?, lokasi=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $baik, $rusak, $hilang, $lokasi, $id]);
        }

        header("Location: index.php?page=master_barang");

    } elseif ($act == 'hapus') {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT foto_barang FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists($targetDir . $img)) { unlink($targetDir . $img); }

        $pdo->prepare("DELETE FROM barang WHERE id=?")->execute([$id]);
        header("Location: index.php?page=master_barang");
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>