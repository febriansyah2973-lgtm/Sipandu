<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act'] == 'kembali') {
    $id_pinjam = $_POST['id_pinjam'];
    $barang_id = $_POST['barang_id'];
    $jumlah    = (int) $_POST['jumlah'];

    try {
        $pdo->beginTransaction();
        $sqlReturn = "UPDATE pinjam_barang SET status = 'Dikembalikan', tgl_kembali = NOW() WHERE id = ?";
        $pdo->prepare($sqlReturn)->execute([$id_pinjam]);
        $sqlStock = "UPDATE barang SET jml_baik = jml_baik + ? WHERE id = ?";
        $pdo->prepare($sqlStock)->execute([$jumlah, $barang_id]);

        $pdo->commit();
        header("Location: index.php?page=barang_kembali&status=success");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: index.php?page=barang_kembali&status=error&msg=" . urlencode($e->getMessage()));
    }
    exit();
}