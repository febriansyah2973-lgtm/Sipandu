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

// 1. PROSES TAMBAH
if (isset($_POST['tambah'])) {
    $nama    = htmlspecialchars(trim($_POST['nama_cabang'] ?? ''));
    $alamat  = htmlspecialchars(trim($_POST['alamat_cabang'] ?? ''));
    $kontak  = htmlspecialchars(trim($_POST['kontak_cabang'] ?? ''));

    if ($nama === '') {
        header("Location: index.php?page=master_cabang&status=invalid");
        exit;
    }

    $sql = "INSERT INTO cabang (nama_cabang, alamat_cabang, kontak_cabang) VALUES (?, ?, ?)";
    $pdo->prepare($sql)->execute([$nama, $alamat, $kontak]);
    $lastId = $pdo->lastInsertId();
    $kode   = 'CBG-' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE cabang SET kode_cabang = ? WHERE id = ?")->execute([$kode, $lastId]);
    header("Location: index.php?page=master_cabang&status=success");
    exit;
}

// 2. PROSES EDIT
if (isset($_POST['edit'])) {
    $id      = (int)($_POST['id'] ?? 0);
    $nama    = htmlspecialchars(trim($_POST['nama_cabang'] ?? ''));
    $alamat  = htmlspecialchars(trim($_POST['alamat_cabang'] ?? ''));
    $kontak  = htmlspecialchars(trim($_POST['kontak_cabang'] ?? ''));

    if ($id <= 0 || $nama === '') {
        header("Location: index.php?page=master_cabang&status=invalid");
        exit;
    }

    $sql = "UPDATE cabang SET nama_cabang = ?, alamat_cabang = ?, kontak_cabang = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$nama, $alamat, $kontak, $id]);
    header("Location: index.php?page=master_cabang&status=updated");
    exit;
}

// 3. PROSES HAPUS
if (isset($_GET['hapus'])) {
    $id = (int)($_GET['hapus'] ?? 0);
    if ($id <= 0) {
        header("Location: index.php?page=master_cabang&status=invalid");
        exit;
    }

    $pdo->prepare("DELETE FROM cabang WHERE id = ?")->execute([$id]);
    header("Location: index.php?page=master_cabang&status=deleted");
    exit;
}

header('Location: index.php?page=master_cabang');
exit;