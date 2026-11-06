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
$targetDir = "assets/img/barang/";
if (!file_exists($targetDir)) { mkdir($targetDir, 0777, true); }

try {
    if ($act == 'tambah') {
        $kode   = trim($_POST['kode_barang'] ?? '');
        $nama   = trim($_POST['nama_barang'] ?? '');
        $baik   = max(0, (int)($_POST['jml_baik'] ?? 0));
        $rusak  = max(0, (int)($_POST['jml_rusak'] ?? 0));
        $hilang = max(0, (int)($_POST['jml_hilang'] ?? 0));
        $lokasi = trim($_POST['lokasi'] ?? '');

        if ($kode === '' || $nama === '') {
            header("Location: index.php?page=master_barang&status=invalid");
            exit;
        }

        $fotoNama = null;

        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "brg_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
        }
        $sql = "INSERT INTO barang (kode_barang, nama_barang, jml_baik, jml_rusak, jml_hilang, lokasi, foto_barang) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kode, $nama, $baik, $rusak, $hilang, $lokasi, $fotoNama]);

        header("Location: index.php?page=master_barang");
        exit;

    } elseif ($act == 'edit') {
        $id     = (int)($_POST['id'] ?? 0);
        $nama   = trim($_POST['nama_barang'] ?? '');
        $baik   = max(0, (int)($_POST['jml_baik'] ?? 0));
        $rusak  = max(0, (int)($_POST['jml_rusak'] ?? 0));
        $hilang = max(0, (int)($_POST['jml_hilang'] ?? 0));
        $lokasi = trim($_POST['lokasi'] ?? '');

        if ($id <= 0 || $nama === '') {
            header("Location: index.php?page=master_barang&status=invalid");
            exit;
        }

        if (!empty($_FILES['foto']['name'])) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotoNama = "brg_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . $fotoNama);
            $stmt = $pdo->prepare("SELECT foto_barang FROM barang WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetchColumn();
            if ($old && file_exists($targetDir . $old)) { unlink($targetDir . $old); }

            $sql = "UPDATE barang SET nama_barang=?, jml_baik=?, jml_rusak=?, jml_hilang=?, lokasi=?, foto_barang=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $baik, $rusak, $hilang, $lokasi, $fotoNama, $id]);
        } else {
            $sql = "UPDATE barang SET nama_barang=?, jml_baik=?, jml_rusak=?, jml_hilang=?, lokasi=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama, $baik, $rusak, $hilang, $lokasi, $id]);
        }

        header("Location: index.php?page=master_barang");
        exit;

    } elseif ($act == 'hapus') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: index.php?page=master_barang&status=invalid");
            exit;
        }

        $stmt = $pdo->prepare("SELECT foto_barang FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists($targetDir . $img)) { unlink($targetDir . $img); }

        $pdo->prepare("DELETE FROM barang WHERE id=?")->execute([$id]);
        header("Location: index.php?page=master_barang");
        exit;
    }

} catch (PDOException $e) {
    header("Location: index.php?page=master_barang&status=error");
    exit;
}

header("Location: index.php?page=master_barang");
exit;
?>