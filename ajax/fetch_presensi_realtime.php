<?php
require_once '../config/database.php';

$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$search_name = $_GET['search_name'] ?? '';

$sql = "SELECT p.*, u.nama_lengkap 
        FROM presensi p 
        JOIN users u ON p.id_user = u.id 
        WHERE (p.tgl_presensi BETWEEN :mulai AND :selesai)";

$params = ['mulai' => $mulai, 'selesai' => $selesai];
if (!empty($search_name)) {
    $sql .= " AND u.nama_lengkap LIKE :search_name";
    $params['search_name'] = "%$search_name%";
}
$sql .= " ORDER BY p.tgl_presensi ASC, p.jam_masuk ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung Statistik
$h = 0; $l = 0; $i = 0; $a = 0;
foreach($data as $r) {
    if($r['status'] == 'Hadir' || $r['status'] == 'Terlambat') $h++;
    if(!empty($r['jam_lembur_mulai'])) $l++;
    if(in_array($r['status'], ['Izin', 'Sakit', 'Cuti'])) $i++;
    if($r['status'] == 'Alpha') $a++;
}

echo json_encode(['data' => $data, 'stats' => ['h' => $h, 'l' => $l, 'i' => $i, 'a' => $a]]);