<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
require_once 'config/auth.php';

auth_session_start();
auth_require_login('index.php?page=login');
auth_require_roles(['admin', 'administrator']);

if (!csrf_validate_request()) {
    http_response_code(419);
    exit('CSRF token tidak valid');
}

$act = $_POST['act'] ?? $_GET['act'] ?? '';

if ($act == 'tambah') {
    $nama = trim($_POST['nama_lokasi'] ?? '');
    $cabang_id = !empty($_POST['cabang_id']) ? (int)$_POST['cabang_id'] : null;
    $lat  = is_numeric($_POST['latitude'] ?? null) ? (float)$_POST['latitude'] : 0;
    $long = is_numeric($_POST['longitude'] ?? null) ? (float)$_POST['longitude'] : 0;
    $ket  = trim($_POST['keterangan'] ?? '');
    $kode = "LOC-" . time(); 

    if ($nama === '') {
        header("Location: index.php?page=master_lokasi&status=invalid");
        exit;
    }

    $sql = "INSERT INTO checkpoints (nama_lokasi, kode_qr, latitude, longitude, keterangan, cabang_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $kode, $lat, $long, $ket, $cabang_id]);
    header("Location: index.php?page=master_lokasi");
    exit;

} elseif ($act == 'edit') {
    $id   = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama_lokasi'] ?? '');
    $cabang_id = !empty($_POST['cabang_id']) ? (int)$_POST['cabang_id'] : null;
    $lat  = is_numeric($_POST['latitude'] ?? null) ? (float)$_POST['latitude'] : 0;
    $long = is_numeric($_POST['longitude'] ?? null) ? (float)$_POST['longitude'] : 0;
    $ket  = trim($_POST['keterangan'] ?? '');

    if ($id <= 0 || $nama === '') {
        header("Location: index.php?page=master_lokasi&status=invalid");
        exit;
    }
    
    $sql = "UPDATE checkpoints SET nama_lokasi=?, cabang_id=?, latitude=?, longitude=?, keterangan=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $cabang_id, $lat, $long, $ket, $id]);
    header("Location: index.php?page=master_lokasi");
    exit;

} elseif ($act == 'hapus') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: index.php?page=master_lokasi&status=invalid");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM checkpoints WHERE id=?");
    $stmt->execute([$id]);
    header("Location: index.php?page=master_lokasi");
    exit;

} elseif ($act == 'update_gps') {
    header('Content-Type: application/json');
    $id  = (int)($_POST['id'] ?? 0);
    $lat = is_numeric($_POST['latitude'] ?? null) ? (float)$_POST['latitude'] : null;
    $lng = is_numeric($_POST['longitude'] ?? null) ? (float)$_POST['longitude'] : null;

    if ($id <= 0 || $lat === null || $lng === null) {
        echo json_encode(['status' => 'error']);
        exit;
    }

    $sql = "UPDATE checkpoints SET latitude = ?, longitude = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $res = $stmt->execute([$lat, $lng, $id]);

    echo json_encode(['status' => ($res ? 'success' : 'error')]);
    exit;
}

header('Location: index.php?page=master_lokasi');
exit;