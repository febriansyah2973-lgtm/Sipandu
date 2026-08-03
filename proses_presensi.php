<?php
// 1. BUFFER OUTPUT
ob_start();

// 2. LOAD CONFIG
require_once 'config/database.php';
require_once 'config/helper_notif.php';

// 3. CEK SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user   = $_SESSION['id_user'];
$nama_user = $_SESSION['nama_lengkap'];
$act       = $_POST['act'] ?? $_GET['act'] ?? '';

// DEBUG: Pastikan act ada
if (empty($act)) {
    die("<h3>Error Fatal: Perintah (act) kosong. Cek form HTML Anda.</h3>");
}

try {
    // ==========================================
    // 1. PROSES ABSEN MASUK (CLOCK IN)
    // ==========================================
    if ($act == 'clock_in' || $act == 'masuk') {
        $latlong = $_POST['latlong'] ?? '-';
        $jam     = date('H:i:s');
        $tgl     = date('Y-m-d');
        
        // Cek Absen Harian
        $cek = $pdo->prepare("SELECT id FROM presensi WHERE id_user = ? AND tgl_presensi = ?");
        $cek->execute([$id_user, $tgl]);
        if ($cek->rowCount() > 0) {
             header("Location: index.php?page=dashboard&status=error&msg=Anda_Sudah_Absen");
             exit;
        }

        // Upload Foto
        $foto_nama = null;
        if (!empty($_FILES['foto_presensi']['name'])) {
            $target_dir = "assets/img/presensi/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $ext = pathinfo($_FILES['foto_presensi']['name'], PATHINFO_EXTENSION);
            $foto_nama = "masuk_" . time() . "_" . $id_user . "." . $ext;
            move_uploaded_file($_FILES['foto_presensi']['tmp_name'], $target_dir . $foto_nama);
        }

        // INSERT (Sesuai kolom DB: lokasi_masuk, foto_masuk)
        $sql = "INSERT INTO presensi (id_user, tgl_presensi, jam_masuk, lokasi_masuk, foto_masuk, status) 
                VALUES (?, ?, ?, ?, ?, 'Hadir')";
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id_user, $tgl, $jam, $latlong, $foto_nama])) {
            $penerima = getAdminIds($pdo);
            sendNotification($pdo, $penerima, 'info', 'Personil On-Duty ??', "$nama_user Masuk Tugas jam $jam.", 'index.php?page=monitoring_presensi');
            header("Location: index.php?page=dashboard&status=success");
            exit;
        }
    }

    // ==========================================
    // 2. PROSES ABSEN PULANG (CLOCK OUT)
    // ==========================================
    elseif ($act == 'clock_out' || $act == 'pulang') {
        $jam = date('H:i:s');
        $tgl = date('Y-m-d');

        $sql = "UPDATE presensi SET jam_pulang = ? WHERE id_user = ? AND tgl_presensi = ?";
        
        if ($pdo->prepare($sql)->execute([$jam, $id_user, $tgl])) {
            $penerima = getAdminIds($pdo);
            sendNotification($pdo, $penerima, 'secondary', 'Personil Off-Duty ??', "$nama_user Selesai Tugas jam $jam.", 'index.php?page=monitoring_presensi');
            header("Location: index.php?page=dashboard&status=success");
            exit;
        }
    }

    // ==========================================
    // 3. PROSES PENGAJUAN CUTI
    // ==========================================
    elseif ($act == 'ajukan_cuti') {
        $tgl_mulai   = $_POST['tgl_mulai'];
        $tgl_selesai = $_POST['tgl_selesai'];
        $alasan      = htmlspecialchars($_POST['alasan']);

        $sql = "INSERT INTO cuti (id_user, tgl_mulai, tgl_selesai, alasan, status, created_at) 
                VALUES (?, ?, ?, ?, 'Pending', NOW())";
        $pdo->prepare($sql)->execute([$id_user, $tgl_mulai, $tgl_selesai, $alasan]);
        
        header("Location: index.php?page=pengajuan_cuti&status=success");
        exit;
    }

// ==========================================
    // 4. PROSES PENGAJUAN LEMBUR (SUDAH FINAL & BENAR)
    // ==========================================
    elseif ($act == 'ajukan_lembur' || $act == 'ajukan_lembur_delegate') {
        
        $tgl_lembur  = $_POST['tgl_lembur'];
        $jam_mulai   = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        $tugas       = htmlspecialchars($_POST['tugas'] ?? $_POST['keterangan'] ?? '-'); 

        // 1. FIX DATABASE: status_lembur=0, id_p_tujuan=0
        $sql = "INSERT INTO lembur (id_user, tgl_lembur, jam_mulai, jam_selesai, keterangan, status_lembur, id_p_tujuan) 
                VALUES (?, ?, ?, ?, ?, 0, 0)";

        // Eksekusi
        $pdo->prepare($sql)->execute([$id_user, $tgl_lembur, $jam_mulai, $jam_selesai, $tugas]);
        
        // 2. NOTIFIKASI: Arah ke monitoring_lembur (Sesuai keinginan Bapak)
        $penerima = getAdminIds($pdo);
        sendNotification($pdo, $penerima, 'warning', 'Pengajuan Lembur ?', "$nama_user mengajukan lembur.", 'index.php?page=monitoring_lembur');

        // 3. REDIRECT: Balik ke presensi_lembur (Sesuai nama file asli Bapak)
        header("Location: index.php?page=presensi_lembur&status=success");
        exit;
    }

    // ERROR HANDLER
    else {
        die("<h3>Error: Perintah '$act' tidak dikenali sistem.<br>Cek value di input hidden name='act'.</h3>");
    }

} catch (PDOException $e) {
    die("<h3>Database Error:</h3>" . $e->getMessage() . "<br><br>Cek nama kolom database Anda lagi.");
}
?>