<?php
session_start();
require_once 'config/database.php';

// Proteksi Admin
if (!isset($_SESSION['id_user'])) { exit; }

// 1. PROSES TAMBAH
if (isset($_POST['tambah'])) {
    $nama    = htmlspecialchars($_POST['nama_cabang']);
    $alamat  = htmlspecialchars($_POST['alamat_cabang']);
    $kontak  = htmlspecialchars($_POST['kontak_cabang']);

    $sql = "INSERT INTO cabang (nama_cabang, alamat_cabang, kontak_cabang) VALUES (?, ?, ?)";
    $pdo->prepare($sql)->execute([$nama, $alamat, $kontak]);
    header("Location: index.php?page=master_cabang&status=success");
}

// 2. PROSES EDIT
if (isset($_POST['edit'])) {
    $id      = $_POST['id'];
    $nama    = htmlspecialchars($_POST['nama_cabang']);
    $alamat  = htmlspecialchars($_POST['alamat_cabang']);
    $kontak  = htmlspecialchars($_POST['kontak_cabang']);

    $sql = "UPDATE cabang SET nama_cabang = ?, alamat_cabang = ?, kontak_cabang = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$nama, $alamat, $kontak, $id]);
    header("Location: index.php?page=master_cabang&status=updated");
}

// 3. PROSES HAPUS
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $pdo->prepare("DELETE FROM cabang WHERE id = ?")->execute([$id]);
    header("Location: index.php?page=master_cabang&status=deleted");
}