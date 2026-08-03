<?php
require_once 'config/database.php';
$periode = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m');
$status  = isset($_GET['status']) ? $_GET['status'] : 'Semua';
$tahun = substr($periode, 0, 4);
$bulan = substr($periode, 5, 2);
$nama_bulan = [
    '01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', '05'=>'Mei', '06'=>'Juni',
    '07'=>'Juli', '08'=>'Agustus', '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Patroli <?= $nama_bulan[$bulan] ?> <?= $tahun ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            @page { size: A4; margin: 2cm; }
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; }
        .kop-surat { border-bottom: 3px double black; padding-bottom: 10px; margin-bottom: 20px; }
        .tanda-tangan { margin-top: 50px; text-align: right; page-break-inside: avoid; }
    </style>
</head>
<body onload="window.print()"> <div class="container-fluid">
        
        <div class="text-center kop-surat">
            <h3 class="fw-bold mb-0">PT. TARGET SECURITY SYSTEM</h3>
            <p class="mb-0">Jalan Teknologi No. 123, Jakarta Selatan, Indonesia</p>
            <p class="small">Telp: (021) 555-1234 | Email: admin@sipandu.com</p>
        </div>

        <div class="text-center mb-4">
            <h4 class="text-uppercase fw-bold text-decoration-underline">LAPORAN BULANAN PATROLI</h4>
            <p>Periode: <?= $nama_bulan[$bulan] ?> <?= $tahun ?></p>
        </div>

        <table class="table table-bordered border-dark">
            <thead class="table-light text-center align-middle">
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 15%">Tanggal & Jam</th>
                    <th style="width: 20%">Petugas</th>
                    <th style="width: 20%">Lokasi / Checkpoint</th>
                    <th style="width: 10%">Kondisi</th>
                    <th style="width: 30%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT p.*, u.nama_lengkap, c.nama_lokasi 
                        FROM patroli p 
                        JOIN users u ON p.user_id = u.id 
                        JOIN checkpoints c ON p.checkpoint_id = c.id 
                        WHERE DATE_FORMAT(p.waktu_scan, '%Y-%m') = ?";
                
                $params = [$periode];

                if ($status == 'Rusak') {
                    $sql .= " AND p.status_temuan != 'Aman'";
                } elseif ($status == 'Aman') {
                    $sql .= " AND p.status_temuan = 'Aman'";
                }

                $sql .= " ORDER BY p.waktu_scan ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $no = 1;

                if ($stmt->rowCount() == 0) {
                    echo "<tr><td colspan='6' class='text-center fst-italic'>Tidak ada data patroli pada periode ini.</td></tr>";
                }

                while ($row = $stmt->fetch()) {
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['waktu_scan'])) ?></td>
                    <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                    <td><?= htmlspecialchars($row['nama_lokasi']) ?></td>
                    <td class="text-center">
                        <?php if($row['status_temuan'] == 'Aman'): ?>
                            <span class="badge border border-success text-success bg-white">AMAN</span>
                        <?php else: ?>
                            <span class="badge border border-danger text-danger bg-white"><?= strtoupper($row['status_temuan']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="row tanda-tangan">
            <div class="col-4 offset-8 text-center">
                <p>Jakarta, <?= date('d') ?> <?= $nama_bulan[date('m')] ?> <?= date('Y') ?></p>
                <p class="mb-5">Mengetahui,<br>Kepala Keamanan</p>
                <br><br>
                <p class="fw-bold text-decoration-underline">( .................................... )</p>
            </div>
        </div>

    </div>

</body>
</html>