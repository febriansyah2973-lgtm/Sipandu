<?php
require_once '../config/database.php';
require_once '../config/auth.php';
header('Content-Type: application/json');

auth_session_start();
if (auth_current_user_id() <= 0) {
    http_response_code(401);
    exit(json_encode(['status' => 'error', 'message' => 'Unauthenticated']));
}
$role = auth_current_role();
$isAdmin = in_array($role, ['admin', 'administrator'], true);

// Petugas hanya boleh melihat dirinya sendiri; admin bisa memfilter user_id.
$uid = $isAdmin ? (int)($_GET['user_id'] ?? 0) : auth_current_user_id();
$tgl = $_GET['tgl'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) $tgl = date('Y-m-d');

$where = "DATE(p.waktu_scan) = :tgl AND p.koordinat LIKE '%,%'";
$params = ['tgl' => $tgl];
if ($uid > 0) { $where .= " AND p.user_id = :uid"; $params['uid'] = $uid; }

$stmt = $pdo->prepare("SELECT p.user_id, u.nama_lengkap, p.waktu_scan, p.koordinat,
                              c.nama_lokasi, c.latitude AS cp_lat, c.longitude AS cp_lng
                       FROM patroli p
                       JOIN users u ON u.id = p.user_id
                       LEFT JOIN checkpoints c ON c.id = p.checkpoint_id
                       WHERE $where
                       ORDER BY p.user_id, p.waktu_scan ASC");
$stmt->execute($params);

$petugas = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    [$lat, $lng] = array_map('floatval', explode(',', $r['koordinat']));
    $petugas[$r['user_id']]['nama'] = $r['nama_lengkap'];
    $petugas[$r['user_id']]['titik'][] = [
        'lokasi' => $r['nama_lokasi'] ?: 'Checkpoint',
        'lat' => $lat, 'lng' => $lng,
        'waktu' => $r['waktu_scan'],
    ];
}

// Posisi terkini dari users (diperbarui update_lokasi_petugas.php)
$sql = "SELECT id, nama_lengkap, latitude, longitude, last_seen FROM users
        WHERE latitude IS NOT NULL AND last_seen >= (NOW() - INTERVAL 10 MINUTE)";
$p2 = [];
if ($uid > 0) { $sql .= " AND id = :uid"; $p2['uid'] = $uid; }
$s2 = $pdo->prepare($sql);
$s2->execute($p2);

echo json_encode([
    'status' => 'success',
    'rute' => array_values(array_map(fn($k, $v) => ['user_id' => $k] + $v, array_keys($petugas), $petugas)),
    'posisi' => $s2->fetchAll(PDO::FETCH_ASSOC),
]);
