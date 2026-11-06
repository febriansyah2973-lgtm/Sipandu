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

try {
    if ($act == 'tambah') {
        $nama   = trim($_POST['nama_shift'] ?? '');
        $masuk  = $_POST['jam_masuk'] ?? '';
        $pulang = $_POST['jam_pulang'] ?? '';

        if ($nama === '' || $masuk === '' || $pulang === '') {
            header("Location: index.php?page=master_shift&status=invalid");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO shifts (nama_shift, jam_masuk, jam_pulang) VALUES (?, ?, ?)");
        $stmt->execute([$nama, $masuk, $pulang]);
        $lastId = $pdo->lastInsertId();
        $kode   = 'SFT-' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE shifts SET kode_shift = ? WHERE id = ?")->execute([$kode, $lastId]);

        header("Location: index.php?page=master_shift");
        exit;

    } elseif ($act == 'edit') {
        $id     = (int)($_POST['id'] ?? 0);
        $nama   = trim($_POST['nama_shift'] ?? '');
        $masuk  = $_POST['jam_masuk'] ?? '';
        $pulang = $_POST['jam_pulang'] ?? '';

        if ($id <= 0 || $nama === '' || $masuk === '' || $pulang === '') {
            header("Location: index.php?page=master_shift&status=invalid");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE shifts SET nama_shift=?, jam_masuk=?, jam_pulang=? WHERE id=?");
        $stmt->execute([$nama, $masuk, $pulang, $id]);

        header("Location: index.php?page=master_shift");
        exit;

    } elseif ($act == 'hapus') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: index.php?page=master_shift&status=invalid");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM shifts WHERE id=?");
        $stmt->execute([$id]);

        header("Location: index.php?page=master_shift");
        exit;
    }

} catch (PDOException $e) {
    header("Location: index.php?page=master_shift&status=error");
    exit;
}

header("Location: index.php?page=master_shift");
exit;
?>