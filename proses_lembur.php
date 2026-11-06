<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';

auth_session_start();
auth_require_login();

if (!csrf_validate_request()) {
    http_response_code(419);
    exit('CSRF token tidak valid');
}

// Cek apakah ada ID dan Aksi
if (!isset($_GET['id']) || !isset($_GET['act'])) {
    header("Location: index.php?page=monitoring_lembur");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$act = $_GET['act'] ?? '';
$role_user = auth_current_role();

if ($id <= 0 || $act === '') {
    header("Location: index.php?page=monitoring_lembur&status=forbidden");
    exit;
}

try {
    if ($act == 'acc_pengawas' && $role_user == 'pengawas') {
        // Step 1: Pengawas menyetujui, naik ke Admin
        $sql = "UPDATE presensi SET status_lembur = 'Disetujui Pengawas' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

    } elseif ($act == 'acc_final' && in_array($role_user, ['admin', 'administrator'], true)) {
        // Step 2: Admin menyetujui secara final
        $sql = "UPDATE presensi SET status_lembur = 'Final Disetujui' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

    } elseif ($act == 'reject' && in_array($role_user, ['admin', 'administrator', 'pengawas'], true)) {
        // Siapapun yang menolak, status jadi Ditolak
        $sql = "UPDATE presensi SET status_lembur = 'Ditolak' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
    } else {
        header("Location: index.php?page=monitoring_lembur&status=forbidden");
        exit;
    }

    // Kembali ke halaman monitoring dengan status sukses
    header("Location: index.php?page=monitoring_lembur&status=success");
    exit;

} catch (PDOException $e) {
    header("Location: index.php?page=monitoring_lembur&status=error");
    exit;
}

header("Location: index.php?page=monitoring_lembur");
exit;