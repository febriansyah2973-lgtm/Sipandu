<?php
// Cek status session agar tidak double start (Pencegah Error Notice)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

$type = $_GET['type'] ?? 'pdf';
$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

// Query Filtered Data
$sql = "SELECT st.*, u1.nama_lengkap AS petugas_keluar, u2.nama_lengkap AS petugas_masuk
        FROM serah_terima st
        JOIN users u1 ON st.user_id_keluar = u1.id
        JOIN users u2 ON st.user_id_masuk = u2.id";

if ($tgl_mulai && $tgl_akhir) {
    $sql .= " WHERE DATE(st.waktu_serah_terima) BETWEEN '$tgl_mulai' AND '$tgl_akhir'";
}
$sql .= " ORDER BY st.waktu_serah_terima DESC";
$data = $pdo->query($sql)->fetchAll();

// --- LOGIKA EXCEL ---
if ($type == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Rekap_Serah_Terima_".date('Ymd').".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <table border="1">
        <tr>
            <th colspan="6" style="background:#f2f2f2; height:30px; text-align:center;">LAPORAN SERAH TERIMA SHIFT SECURITY</th>
        </tr>
        <?php if($tgl_mulai): ?>
        <tr><th colspan="6">Periode: <?= $tgl_mulai ?> s/d <?= $tgl_akhir ?></th></tr>
        <?php endif; ?>
        <tr style="background:#eee; font-weight:bold;">
            <th>No</th>
            <th>Waktu</th>
            <th>Petugas Keluar</th>
            <th>Petugas Masuk</th>
            <th>Status Aset</th>
            <th>Catatan Situasi</th>
        </tr>
        <?php $no=1; foreach($data as $r): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $r['waktu_serah_terima'] ?></td>
            <td><?= $r['petugas_keluar'] ?></td>
            <td><?= $r['petugas_masuk'] ?></td>
            <td><?= $r['status_inventaris'] ?></td>
            <td><?= $r['catatan_situasi'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php
    exit;

} else {
    // --- LOGIKA PDF (PRINT VERSION) ---
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Cetak Laporan - SIPANDU</title>
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        <style>
            body { font-size: 11px; font-family: sans-serif; }
            .kop { border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 25px; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body onload="window.print()">
        <div class="container mt-4">
            <div class="kop text-center">
                <h4 class="fw-bold mb-0">REKAPITULASI LAPORAN SERAH TERIMA</h4>
                <p class="mb-0">Security Command Center - SIPANDU Digital Report</p>
                <?php if($tgl_mulai): ?>
                <span class="badge bg-dark">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></span>
                <?php endif; ?>
            </div>
            
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr class="table-secondary">
                        <th>Waktu</th>
                        <th>Petugas Keluar</th>
                        <th>Petugas Masuk</th>
                        <th>Kondisi Aset</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data as $r): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($r['waktu_serah_terima'])) ?></td>
                        <td><?= $r['petugas_keluar'] ?></td>
                        <td><?= $r['petugas_masuk'] ?></td>
                        <td><?= strtoupper($r['status_inventaris']) ?></td>
                        <td><?= $r['catatan_situasi'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="row mt-5 text-center">
                <div class="col-4">Dibuat Oleh,<br><br><br><br>( .................... )</div>
                <div class="col-4"></div>
                <div class="col-4">Mengetahui (Pimpinan),<br><br><br><br>( .................... )</div>
            </div>
        </div>
    </body>
    </html>
    <?php
}