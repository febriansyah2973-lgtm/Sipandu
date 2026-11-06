<?php
require_once 'config/database.php';
require_once 'config/auth.php';

auth_session_start();
$user_id = auth_require_login('index.php?page=login');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['act'] ?? '') == 'lapor') {
    $id_pinjam = (int)($_POST['id_pinjam'] ?? 0);
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $jumlah    = (int) $_POST['jumlah'];
    $kondisi   = trim((string)($_POST['kondisi_baru'] ?? ''));

    if ($id_pinjam <= 0 || $barang_id <= 0 || $jumlah <= 0 || !in_array($kondisi, ['Rusak', 'Hilang'], true)) {
        header("Location: index.php?page=barang_lapor&status=invalid");
        exit;
    }

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
        header("Location: index.php?page=barang_lapor&status=error");
    }
    exit();
}
// --- LOGIKA LAPOR MASALAH BARANG (Modul 5.1.5) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($_POST['act'] ?? '') == 'lapor_masalah_barang') {
    $barang_id     = (int)($_POST['barang_id'] ?? 0);
    $jenis_masalah = trim((string)($_POST['jenis_masalah'] ?? ''));
    $jumlah        = (int)$_POST['jumlah'];
    $detail        = trim((string)($_POST['detail_kejadian'] ?? ''));

    if ($barang_id <= 0 || $jumlah <= 0 || $detail === '' || !in_array($jenis_masalah, ['Rusak', 'Hilang'], true)) {
        header("Location: index.php?page=barang_lapor&status=invalid");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Lock the stock row so concurrent reports cannot oversubtract inventory.
        $stmtBarang = $pdo->prepare("SELECT id, jml_baik FROM barang WHERE id = ? FOR UPDATE");
        $stmtBarang->execute([$barang_id]);
        $barang = $stmtBarang->fetch(PDO::FETCH_ASSOC);
        if (!$barang) {
            throw new RuntimeException('Barang tidak ditemukan.');
        }
        if ((int)$barang['jml_baik'] < $jumlah) {
            throw new RuntimeException('Jumlah laporan melebihi stok barang yang tersedia.');
        }

        // 1. Catat ke tabel laporan masalah.
        $sqlMasalah = "INSERT INTO barang_masalah (barang_id, user_id, jenis_masalah, jumlah, detail_kejadian, created_at) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
        $pdo->prepare($sqlMasalah)->execute([$barang_id, $user_id, $jenis_masalah, $jumlah, $detail]);

        // 2. Pindahkan stok dari kondisi baik ke kategori masalah.
        $kolomMasalah = $jenis_masalah === 'Rusak' ? 'jml_rusak' : 'jml_hilang';
        $sqlUpdateStok = "UPDATE barang SET jml_baik = jml_baik - ?, {$kolomMasalah} = {$kolomMasalah} + ? WHERE id = ?";
        $pdo->prepare($sqlUpdateStok)->execute([$jumlah, $jumlah, $barang_id]);

        $pdo->commit();
        header("Location: index.php?page=barang_lapor&status=success");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: index.php?page=barang_lapor&status=error&msg=" . urlencode($e->getMessage()));
    }
    exit();
}

header('Location: index.php?page=barang_lapor');
exit;