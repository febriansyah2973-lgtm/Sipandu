<?php
require_once 'config/database.php';

$nvr_id = $_GET['id'] ?? null;
if (!$nvr_id) die("ID NVR tidak valid!");

// Ambil data NVR
$stmt = $pdo->prepare("SELECT * FROM nvr_list WHERE id = ?");
$stmt->execute([$nvr_id]);
$nvr = $stmt->fetch();

if (!$nvr) die("NVR tidak ditemukan di database!");

// Kita bersihkan dulu data kamera lama untuk NVR ini agar tidak double
$pdo->prepare("DELETE FROM cctv_list WHERE nvr_id = ?")->execute([$nvr_id]);

$max_channels = 16; 
$start_port = 8082; 

for ($ch = 1; $ch <= $max_channels; $ch++) {
    $nama_cctv = "Kamera " . $ch . " (" . $nvr['nama_nvr'] . ")";
    $ws_port = $start_port + (($ch - 1) * 2); 

    // Kita isi semua kolom sesuai gambar image_450852.jpg
    $ins = $pdo->prepare("INSERT INTO cctv_list 
        (nvr_id, channel, nama_cctv, ip_address, port_web, username, password, ws_port, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aktif')");
    
    $ins->execute([
        $nvr['id'], 
        $ch, 
        $nama_cctv, 
        $nvr['ip_nvr'], 
        80, // Port web default
        $nvr['username'], 
        $nvr['password'], 
        $ws_port
    ]);
}

echo "
<div class='container mt-5 text-center'>
    <div class='card border-0 shadow p-5 rounded-4'>
        <i class='fas fa-check-circle fa-5x text-success mb-4'></i>
        <h2 class='fw-bold'>SINKRONISASI TOTAL BERHASIL!</h2>
        <p class='text-muted'>16 Channel telah dikonfigurasi otomatis untuk <b>{$nvr['nama_nvr']}</b>.</p>
        <a href='?page=live_cctv&nvr_id=$nvr_id' class='btn btn-primary btn-lg rounded-pill px-5'>Buka Monitoring Live</a>
    </div>
</div>";
?>