<?php
session_start();
require_once 'config/database.php';
require_once 'config/helper_notif.php';

if (!isset($_SESSION['id_user']) || strtolower($_SESSION['role']) != 'admin') {
    header("Location: index.php?page=login");
    exit;
}

$act = $_POST['act'] ?? $_GET['act'] ?? '';

try {
    // --- 1. TAMBAH KENDARAAN ---
    if ($act == 'tambah_kendaraan') {
        $nama  = htmlspecialchars($_POST['nama_kendaraan']);
        $nopol = strtoupper($_POST['nopol']);
        $jenis = $_POST['jenis']; // Mobil/Motor

        $sql = "INSERT INTO kendaraan (nama_kendaraan, nopol, jenis, status) VALUES (?, ?, ?, 'Tersedia')";
        if ($pdo->prepare($sql)->execute([$nama, $nopol, $jenis])) {
            header("Location: index.php?page=master_kendaraan&status=success");
        }
    }

    // --- 2. UPDATE STATUS KENDARAAN ---
    elseif ($act == 'edit_kendaraan') {
        $id     = $_POST['id'];
        $status = $_POST['status']; // Tersedia / Digunakan / Servis / Rusak
        $nama   = $_POST['nama_kendaraan'];

        $sql = "UPDATE kendaraan SET status = ? WHERE id = ?";
        if ($pdo->prepare($sql)->execute([$status, $id])) {
            
            // [NOTIFIKASI] Jika kendaraan tidak bisa digunakan
            if ($status == 'Servis' || $status == 'Rusak') {
                $penerima = getAdminIds($pdo); // Kirim ke Admin & Pengawas
                sendNotification(
                    $pdo, 
                    $penerima, 
                    'alert', 
                    'Kendaraan Tidak Siap Pakai! ??', 
                    "Unit $nama sedang dalam kondisi $status. Jangan gunakan untuk patroli sementara waktu.", 
                    'index.php?page=master_kendaraan'
                );
            }

            header("Location: index.php?page=master_kendaraan&status=updated");
        }
    }

    // --- 3. HAPUS KENDARAAN ---
    elseif ($act == 'hapus_kendaraan') {
        $id = $_GET['id'];
        $pdo->prepare("DELETE FROM kendaraan WHERE id = ?")->execute([$id]);
        header("Location: index.php?page=master_kendaraan&status=deleted");
    }

} catch (Exception $e) {
    die("Error Master Kendaraan: " . $e->getMessage());
}
?>