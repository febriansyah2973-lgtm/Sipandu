<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/audit.php';

auth_session_start();

// Proteksi: Pastikan user sudah login
auth_require_login();

$user_id = auth_current_user_id();
$role_user = auth_current_role();

// ==========================================
// 1. LOGIKA INPUT IZIN (UNTUK PERSONIL)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jenis_izin'])) {
    if (!csrf_validate_request()) {
        http_response_code(419);
        exit('CSRF token tidak valid');
    }

    $jenis       = trim($_POST['jenis_izin'] ?? '');
    $mulai       = $_POST['tgl_mulai'] ?? '';
    $selesai     = $_POST['tgl_selesai'] ?? '';
    $alasan      = htmlspecialchars(trim($_POST['alasan'] ?? ''));
    $fileName    = null;

    if ($jenis === '' || $mulai === '' || $selesai === '' || $alasan === '') {
        header("Location: index.php?page=presensi_izin&status=error&msg=" . urlencode('Data pengajuan izin tidak lengkap.'));
        exit();
    }

    // Handle Upload Lampiran
    if (!empty($_FILES['lampiran']['name'])) {
        $path = "assets/img/izin/";
        if (!file_exists($path)) mkdir($path, 0777, true);
        
        $fileName = time() . "_" . $_FILES['lampiran']['name'];
        move_uploaded_file($_FILES['lampiran']['tmp_name'], $path . $fileName);
    }

    try {
        // Status awal WAJIB 'Pending' agar bisa divalidasi Pengawas
        $sql = "INSERT INTO pengajuan_izin (user_id, jenis_izin, tgl_mulai, tgl_selesai, alasan, lampiran, status_approval) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $pdo->prepare($sql)->execute([$user_id, $jenis, $mulai, $selesai, $alasan, $fileName]);
        $izin_id = (int)$pdo->lastInsertId();
        audit_log_event('izin.submit', [
            'izin_id' => $izin_id,
            'jenis' => $jenis,
            'tgl_mulai' => $mulai,
            'tgl_selesai' => $selesai
        ]);
        
        header("Location: index.php?page=presensi_izin&status=success");
        exit();
    } catch (Exception $e) {
        header("Location: index.php?page=presensi_izin&status=error");
        exit();
    }
}

// ==========================================
// 2. LOGIKA APPROVAL 2 TINGKAT (PENGAWAS & ADMIN)
// ==========================================
if (isset($_GET['act']) && isset($_GET['id'])) {
    if (!csrf_validate_request()) {
        http_response_code(419);
        exit('CSRF token tidak valid');
    }

    $id = (int)($_GET['id'] ?? 0);
    $act = $_GET['act'] ?? '';
    $status_baru = '';

    if ($id <= 0 || $act === '') {
        header("Location: index.php?page=monitoring_izin&status=forbidden");
        exit();
    }

    try {
        if ($act == 'acc_pengawas' && $role_user == 'pengawas') {
            // Pengawas ACC Tahap 1
            $status_baru = 'Disetujui Pengawas';
        } elseif ($act == 'acc_final' && in_array($role_user, ['admin', 'administrator'], true)) {
            // Admin ACC Tahap 2 (Selesai)
            $status_baru = 'Final Disetujui';
        } elseif ($act == 'reject' && in_array($role_user, ['admin', 'administrator', 'pengawas'], true)) {
            // Penolakan oleh salah satu pihak
            $status_baru = 'Ditolak';
        } else {
            // Jika ada yang coba tembak URL tanpa role yang sesuai
            header("Location: index.php?page=monitoring_izin&status=forbidden");
            exit();
        }

        // Update status dan catat siapa yang memproses terakhir
        $sql = "UPDATE pengajuan_izin SET status_approval = ?, disetujui_oleh = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$status_baru, $user_id, $id]);
        audit_log_event('izin.approval', [
            'izin_id' => $id,
            'action' => $act,
            'status_baru' => $status_baru
        ]);
        
        header("Location: index.php?page=monitoring_izin&status=success");
        exit();

    } catch (Exception $e) {
        header("Location: index.php?page=monitoring_izin&status=error");
        exit();
    }
}

header("Location: index.php?page=presensi_izin");
exit();