<?php
require_once '../config/database.php';
require_once '../config/presensi_report.php';

$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$search_name = $_GET['search_name'] ?? '';

$data = presensi_report_fetch($pdo, $mulai, $selesai, $search_name);

header('Content-Type: application/json');
echo json_encode([
    'html' => presensi_report_rows($data),
    'stats' => presensi_report_stats($data),
]);
