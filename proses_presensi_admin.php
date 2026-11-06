<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
require_once 'config/auth.php';

auth_session_start();
auth_require_login();
auth_require_roles(['admin', 'administrator']);

if (!csrf_validate_request()) {
    http_response_code(419);
    exit('CSRF token tidak valid');
}

$aksi = $_REQUEST['aksi'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $aksi == 'tambah') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_masuk = $_POST['jam_masuk'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($user_id <= 0 || $tanggal === '' || $jam_masuk === '') {
        header("Location: index.php?page=presensi_monitor&status=invalid");
        exit;
    }

    $jam_masuk_full = $jam_masuk . ":00";

    try {
        $sql = "INSERT INTO presensi (user_id, tanggal, jam_masuk, keterangan, status) VALUES (?, ?, ?, ?, 'Hadir Manual')";
        $pdo->prepare($sql)->execute([$user_id, $tanggal, $jam_masuk_full, $keterangan]);

        header("Location: index.php?page=presensi_monitor&status=add_success");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=presensi_monitor&status=error");
        exit;
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && $aksi == 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $jam_masuk = $_POST['jam_masuk'] ?? '';
    $jam_pulang = !empty($_POST['jam_pulang']) ? $_POST['jam_pulang'] . ":00" : NULL;
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($id <= 0 || $jam_masuk === '') {
        header("Location: index.php?page=presensi_monitor&status=invalid");
        exit;
    }

    try {
        $sql = "UPDATE presensi SET jam_masuk = ?, jam_pulang = ?, keterangan = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$jam_masuk, $jam_pulang, $keterangan, $id]);

        header("Location: index.php?page=presensi_monitor&status=update_success");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=presensi_monitor&status=error");
        exit;
    }

} elseif ($aksi == 'hapus' && isset($_GET['id'])) {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: index.php?page=presensi_monitor&status=invalid");
        exit;
    }

    try {
        $sql = "DELETE FROM presensi WHERE id = ?";
        $pdo->prepare($sql)->execute([$id]);

        header("Location: index.php?page=presensi_monitor&status=delete_success");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=presensi_monitor&status=error");
        exit;
    }
} else {
    header("Location: index.php?page=presensi_monitor");
    exit;
}
?>