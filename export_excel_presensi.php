<?php
require_once 'config/database.php';

$mulai   = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Presensi_SIPANDU_$mulai-$selesai.xls");
$sql = "SELECT p.*, u.nama_lengkap, u.id as user_id 
        FROM presensi p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.tanggal BETWEEN :mulai AND :selesai 
        ORDER BY p.tanggal ASC, p.jam_masuk ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['mulai' => $mulai, 'selesai' => $selesai]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; font-family: Arial, sans-serif; }
        th { background-color: #f8f9fa; text-align: center; font-weight: bold; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <center>
        <h3>LAPORAN PRESENSI & LEMBUR PT. TARGET KELOLA SECURINDO</h3>
        <p>Periode: <?= $mulai ?> s.d. <?= $selesai ?></p>
    </center>
    
    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>ID Personil</th>
                <th>Nama Personil</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Lembur (OVT)</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($data as $row): ?>
            <tr>
                <td class="center"><?= $no++ ?></td>
                <td class="center"><?= $row['tanggal'] ?></td>
                <td class="center">PEG-<?= sprintf("%03d", $row['user_id']) ?></td>
                <td><?= $row['nama_lengkap'] ?></td>
                <td class="center"><?= $row['jam_masuk'] ? substr($row['jam_masuk'], 0, 5) : '-' ?></td>
                <td class="center"><?= $row['jam_pulang'] ? substr($row['jam_pulang'], 0, 5) : '-' ?></td>
                <td class="center">
                    <?php 
                    if ($row['jam_lembur_mulai']) {
                        echo substr($row['jam_lembur_mulai'], 0, 5) . " - " . ($row['jam_lembur_selesai'] ? substr($row['jam_lembur_selesai'], 0, 5) : 'Running');
                    } else {
                        echo "-";
                    }
                    ?>
                </td>
                <td class="center"><?= $row['status'] ?></td>
                <td>
                    <?= $row['keterangan'] ?? '' ?> 
                    <?= $row['alasan_lembur'] ? " | OVT: ".$row['alasan_lembur'] : '' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>