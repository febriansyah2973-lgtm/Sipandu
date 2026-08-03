<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act'] == 'lapor') {
    $id_pinjam = $_POST['id_pinjam'];
    $barang_id = $_POST['barang_id'];
    $jumlah    = (int) $_POST['jumlah'];
    $kondisi   = $_POST['kondisi_baru']; // 'Rusak' atau 'Hilang'

    try {
        $pdo->beginTransaction();

        // 1. Update status di tabel pinjam_barang
        $sqlReturn = "UPDATE pinjam_barang SET status = ?, tgl_kembali = NOW() WHERE id = ?";
        $pdo->prepare($sqlReturn)->execute([$kondisi, $id_pinjam]);

        // 2. Tambahkan ke kolom rincian yang sesuai di tabel barang
        if ($kondisi == 'Rusak') {
            $sqlStock = "UPDATE barang SET jml_rusak = jml_rusak + ? WHERE id = ?";
        } else {
            $sqlStock = "UPDATE barang SET jml_hilang = jml_hilang + ? WHERE id = ?";
        }
        $pdo->prepare($sqlStock)->execute([$jumlah, $barang_id]);

        $pdo->commit();
        header("Location: index.php?page=barang_lapor&status=success");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: index.php?page=barang_lapor&status=error&msg=" . urlencode($e->getMessage()));
    }
    exit();
}
// --- LOGIKA LAPOR MASALAH BARANG (Modul 5.1.5) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act'] == 'lapor_masalah_barang') {
    $barang_id     = $_POST['barang_id'];
    $jenis_masalah = $_POST['jenis_masalah']; // Rusak / Hilang
    $jumlah        = (int)$_POST['jumlah'];
    $detail        = htmlspecialchars($_POST['detail_kejadian']);
    $user_id       = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // 1. Catat ke tabel log/masalah (Gunakan tabel barang_masalah)
        $sqlMasalah = "INSERT INTO barang_masalah (barang_id, user_id, jenis_masalah, jumlah, detail, created_at) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
        $pdo->prepare($sqlMasalah)->execute([$barang_id, $user_id, $jenis_masalah, $jumlah, $detail]);

        // 2. Jika Barang Rusak/Hilang, kurangi stok jml_baik di master barang
        $sqlUpdateStok = "UPDATE barang SET jml_baik = jml_baik - ? WHERE id = ?";
        $pdo->prepare($sqlUpdateStok)->execute([$jumlah, $barang_id]);

        $pdo->commit();
        header("Location: index.php?page=barang_lapor&status=success");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: index.php?page=barang_lapor&status=error&msg=" . urlencode($e->getMessage()));
    }
    exit();
}