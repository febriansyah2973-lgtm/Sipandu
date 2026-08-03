<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id_keluar = $_SESSION['id_user'];
    $user_id_masuk  = $_POST['user_id_masuk'];
    $catatan        = htmlspecialchars($_POST['catatan']);

    try {
        $pdo->beginTransaction();

        // 1. Simpan riwayat serah terima
        $sql_st = "INSERT INTO serah_terima (user_id_keluar, user_id_masuk, catatan_situasi) VALUES (?, ?, ?)";
        $pdo->prepare($sql_st)->execute([$user_id_keluar, $user_id_masuk, $catatan]);

        // 2. UPDATE PEMEGANG BARANG (Pindahkan dari ID Anda ke ID Petugas Baru)
        $upd_b = "UPDATE pinjam_barang SET user_id = ? WHERE user_id = ? AND status = 'Dipinjam'";
        $pdo->prepare($upd_b)->execute([$user_id_masuk, $user_id_keluar]);

        // 3. UPDATE PEMEGANG KENDARAAN (Pindahkan dari ID Anda ke ID Petugas Baru)
        $upd_k = "UPDATE pinjam_kendaraan SET user_id = ? WHERE user_id = ? AND status = 'Dipakai'";
        $pdo->prepare($upd_k)->execute([$user_id_masuk, $user_id_keluar]);

        $pdo->commit();
        echo "<script>alert('Serah Terima Berhasil! Selamat Beristirahat.'); window.location.href='index.php?page=dashboard';</script>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}