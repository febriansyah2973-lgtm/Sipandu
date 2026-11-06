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

$act = $_GET['act'] ?? $_POST['act'] ?? '';
$targetDir = "assets/img/kendaraan/";
if (!file_exists($targetDir)) { mkdir($targetDir, 0777, true); }

try {
    if ($act == 'tambah') {
        $nopol  = strtoupper(trim($_POST['no_polisi'] ?? ''));
        $jenis  = trim($_POST['jenis'] ?? '');
        $merk   = trim($_POST['merk_model'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $ket    = trim($_POST['keterangan'] ?? '');

        if ($nopol === '' || $jenis === '' || $merk === '' || $status === '') {
            header("Location: index.php?page=master_kendaraan&status=invalid");
            exit;
        }

        $fotoNama = null;

        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "veh_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
        }

        $sql = "INSERT INTO kendaraan (no_polisi, jenis, merk_model, status, keterangan, foto_kendaraan) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nopol, $jenis, $merk, $status, $ket, $fotoNama]);

        header("Location: index.php?page=master_kendaraan");
        exit;

    } elseif ($act == 'edit') {
        $id     = (int)($_POST['id'] ?? 0);
        $nopol  = strtoupper(trim($_POST['no_polisi'] ?? ''));
        $jenis  = trim($_POST['jenis'] ?? '');
        $merk   = trim($_POST['merk_model'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $ket    = trim($_POST['keterangan'] ?? '');

        if ($id <= 0 || $nopol === '' || $jenis === '' || $merk === '' || $status === '') {
            header("Location: index.php?page=master_kendaraan&status=invalid");
            exit;
        }

        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "veh_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
            $stmt = $pdo->prepare("SELECT foto_kendaraan FROM kendaraan WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetchColumn();
            if ($old && file_exists($targetDir . $old)) { unlink($targetDir . $old); }

            $sql = "UPDATE kendaraan SET no_polisi=?, jenis=?, merk_model=?, status=?, keterangan=?, foto_kendaraan=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nopol, $jenis, $merk, $status, $ket, $fotoNama, $id]);
        } else {
            $sql = "UPDATE kendaraan SET no_polisi=?, jenis=?, merk_model=?, status=?, keterangan=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nopol, $jenis, $merk, $status, $ket, $id]);
        }

        header("Location: index.php?page=master_kendaraan");
        exit;

    } elseif ($act == 'hapus') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: index.php?page=master_kendaraan&status=invalid");
            exit;
        }

        $stmt = $pdo->prepare("SELECT foto_kendaraan FROM kendaraan WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists($targetDir . $img)) { unlink($targetDir . $img); }

        $pdo->prepare("DELETE FROM kendaraan WHERE id=?")->execute([$id]);
        header("Location: index.php?page=master_kendaraan");
        exit;
    }

} catch (PDOException $e) {
    header("Location: index.php?page=master_kendaraan&status=error");
    exit;
}

header("Location: index.php?page=master_kendaraan");
exit;
?>