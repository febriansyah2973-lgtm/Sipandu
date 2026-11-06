<?php
require_once 'config/database.php';

$mulai   = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$keyword = $_GET['keyword'] ?? '';

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Visitor_SIPANDU_$mulai-$selesai.xls");

$sql = "SELECT * FROM visitors 
        WHERE (DATE(waktu_masuk) BETWEEN :mulai AND :selesai)";
$params = ['mulai' => $mulai, 'selesai' => $selesai];

if (!empty($keyword)) {
    $sql .= " AND (nama_tamu LIKE :keyword OR instansi LIKE :keyword OR bertemu_dengan LIKE :keyword OR keperluan LIKE :keyword)";
    $params['keyword'] = "%$keyword%";
}
$sql .= " ORDER BY waktu_masuk DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 6px; font-family: Arial, sans-serif; font-size: 11pt; }
        th { background-color: #e2e8f0; text-align: center; font-weight: bold; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <center>
        <h3>LAPORAN KUNJUNGAN TAMU (VISITOR LOG)</h3>
        <p>Periode: <?= $mulai ?> s.d. <?= $selesai ?></p>
    </center>
    
    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu Kedatangan</th>
                <th>Nama Tamu</th>
                <th>Instansi / Perusahaan</th>
                <th>No. Telepon / HP</th>
                <th>Bertemu Dengan</th>
                <th>Keperluan / Tujuan</th>
                <th>Waktu Keluar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data)): ?>
                <tr><td colspan="9" class="center">Tidak ada data kunjungan tamu pada periode ini.</td></tr>
            <?php else: ?>
                <?php $no=1; foreach($data as $row): ?>
                <tr>
                    <td class="center"><?= $no++ ?></td>
                    <td class="center"><?= $row['waktu_masuk'] ?></td>
                    <td><?= htmlspecialchars($row['nama_tamu']) ?></td>
                    <td><?= htmlspecialchars($row['instansi'] ?? '-') ?></td>
                    <td class="center"><?= htmlspecialchars($row['no_hp'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['bertemu_dengan']) ?></td>
                    <td><?= htmlspecialchars($row['keperluan']) ?></td>
                    <td class="center"><?= (!empty($row['waktu_keluar']) && $row['waktu_keluar'] != '0000-00-00 00:00:00') ? $row['waktu_keluar'] : '-' ?></td>
                    <td class="center"><?= (empty($row['waktu_keluar']) || $row['waktu_keluar'] == '0000-00-00 00:00:00') ? 'On Site' : 'Check Out' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>