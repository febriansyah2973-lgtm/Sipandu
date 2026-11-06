<?php
// 1. KONEKSI & SESSION
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/audit.php';

auth_session_start();

// 2. PROTEKSI KEAMANAN
if (!csrf_validate_request()) {
    http_response_code(419);
    exit('CSRF token tidak valid');
}

$user_id   = auth_require_login();
$nama_user = auth_current_name();
$act       = $_POST['act'] ?? '';

try {
    // 3. LOGIKA PINJAM BARANG
    if ($act == 'pinjam') {
        $barang_id = (int)($_POST['barang_id'] ?? 0);
        $jumlah    = (int) $_POST['jumlah'];
        $ket       = htmlspecialchars(trim($_POST['keterangan'] ?? ''));

        if ($barang_id <= 0 || $jumlah <= 0 || $ket === '') {
            throw new Exception("Data peminjaman tidak valid.");
        }

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
        audit_log_event('barang.pinjam', [
            'barang_id' => $barang_id,
            'jumlah' => $jumlah,
            'keterangan' => $ket
        ]);

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