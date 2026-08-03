<?php
// 1. KONEKSI & SESSION
require_once 'config/database.php';
session_start();

// 2. PROTEKSI KEAMANAN
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['id_user'];
$nama_user = $_SESSION['nama_lengkap'];
$act       = $_POST['act'] ?? '';

try {
    // 3. LOGIKA PINJAM BARANG
    if ($act == 'pinjam') {
        $barang_id = $_POST['barang_id'];
        $jumlah    = (int) $_POST['jumlah'];
        $ket       = htmlspecialchars($_POST['keterangan']);

        // Mulai Transaksi Database (Mencegah data korup/stok ngaco)
        $pdo->beginTransaction();

        // A. CEK STOK BARANG TERBARU
        $stmt = $pdo->prepare("SELECT jml_baik, nama_barang FROM barang WHERE id = ? FOR UPDATE");
        $stmt->execute([$barang_id]);
        $barang = $stmt->fetch();

        if (!$barang) {
            throw new Exception("Barang tidak ditemukan di gudang.");
        }

        if ($barang['jml_baik'] < $jumlah) {
            throw new Exception("Stok tidak cukup! Sisa: " . $barang['jml_baik'] . " unit.");
        }

        // B. SIMPAN KE TABEL pinjam_barang
        // PENTING: Status harus 'Dipinjam' agar muncul di tabel monitoring Bapak
        $sqlInsert = "INSERT INTO pinjam_barang (user_id, barang_id, jumlah, tgl_pinjam, status, keterangan_pinjam) 
                      VALUES (?, ?, ?, NOW(), 'Dipinjam', ?)";
        $stmt_ins = $pdo->prepare($sqlInsert);
        $stmt_ins->execute([$user_id, $barang_id, $jumlah, $ket]);

        // C. POTONG STOK DI TABEL BARANG
        $sqlUpdate = "UPDATE barang SET jml_baik = jml_baik - ? WHERE id = ?";
        $stmt_upd = $pdo->prepare($sqlUpdate);
        $stmt_upd->execute([$jumlah, $barang_id]);

        // D. (OPSIONAL) SIMPAN KE LOG SISTEM (Biar ketahuan siapa yang input)
        $log_msg = "$nama_user meminjam $jumlah {$barang['nama_barang']}";
        $sql_log = "INSERT INTO log_sistem (user_id, aktivitas, waktu) VALUES (?, ?, NOW())";
        // Cek dulu apakah bapak punya tabel log_sistem, jika tidak ada hapus baris bawah ini
        // $pdo->prepare($sql_log)->execute([$user_id, $log_msg]);

        // SELESAI & SIMPAN PERUBAHAN
        $pdo->commit();

        // REDIRECT BALIK KE HALAMAN AWAL DENGAN SINYAL BERHASIL
        header("Location: index.php?page=barang_pinjam&status=success");
        exit();
    }

    // 4. LOGIKA PINJAM KENDARAAN (Jika ada modal kendaraan juga)
    elseif ($act == 'pinjam_kendaraan') {
        // ... logika kendaraan bapak yang sebelumnya ...
    }

} catch (Exception $e) {
    // JIKA GAGAL, BATALKAN SEMUA PERUBAHAN STOK
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // REDIRECT BALIK DENGAN PESAN ERROR (Akan ditangkap SweetAlert di halaman depan)
    header("Location: index.php?page=barang_pinjam&status=error&msg=" . urlencode($e->getMessage()));
    exit();
}