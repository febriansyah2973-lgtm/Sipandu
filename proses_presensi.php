<?php
// 1. BUFFER OUTPUT
ob_start();

// 2. LOAD CONFIG
require_once 'config/database.php';
require_once 'config/helper_notif.php';
require_once 'config/csrf.php';
require_once 'config/audit.php';
require_once 'config/auth.php';

// 3. CEK SESSION
auth_session_start();
$id_user = auth_require_login('login.php');

$nama_user = auth_current_name();
$act       = $_POST['act'] ?? $_GET['act'] ?? '';

// DEBUG: Pastikan act ada
if (empty($act)) {
    header("Location: index.php?page=dashboard&status=invalid_act");
    exit;
}

// --- HELPER FUNCTIONS FACE RECOGNITION ---
function get_user_reference_photo($pdo, $id_user) {
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$id_user]);
    $uData = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$uData) return null;

    $namaFile = $uData['foto_profil'] ?? $uData['foto'] ?? '';
    if (empty($namaFile) || $namaFile === 'default.png' || $namaFile === 'default.jpg') {
        return null;
    }

    $possiblePaths = [
        __DIR__ . "/uploads/profil/" . $namaFile,
        __DIR__ . "/assets/uploads/profil/" . $namaFile,
        __DIR__ . "/assets/img/users/" . $namaFile
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path) && is_file($path)) {
            return $path;
        }
    }
    return null;
}

function verify_face_with_ai($pathProfil, $pathSelfie) {
    $ai_url = 'http://127.0.0.1:5000/verify-face';

    if (!file_exists($pathProfil) || !file_exists($pathSelfie)) {
        return ['status' => 'error', 'matched' => false, 'message' => 'Berkas gambar referensi profil atau selfie tidak ditemukan di server.'];
    }

    $cFileProfil = new CURLFile($pathProfil, mime_content_type($pathProfil), basename($pathProfil));
    $cFileSelfie = new CURLFile($pathSelfie, mime_content_type($pathSelfie), 'selfie.jpg');

    $postData = [
        'foto_profil' => $cFileProfil,
        'foto_selfie' => $cFileSelfie
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ai_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return [
            'status' => 'error',
            'matched' => false,
            'message' => 'Layanan AI Server Offline atau tidak merespons (' . ($err ?: "HTTP $httpCode") . ')'
        ];
    }

    $res = json_decode($response, true);
    if (!is_array($res)) {
        return ['status' => 'error', 'matched' => false, 'message' => 'Format respons server AI tidak valid.'];
    }

    return $res;
}

try {
    // ==========================================
    // 1. PROSES ABSEN MASUK DENGAN AI FACE MATCHING
    // ==========================================
    if ($act == 'clock_in' || $act == 'masuk') {
        $latlong    = $_POST['latlong'] ?? '-';
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        $jam        = date('H:i:s');
        $tgl        = date('Y-m-d');
        
        // Cek Absen Harian
        $cek = $pdo->prepare("SELECT id, jam_masuk FROM presensi WHERE id_user = ? AND tgl_presensi = ?");
        $cek->execute([$id_user, $tgl]);
        $presensiRow = $cek->fetch(PDO::FETCH_ASSOC);
        if ($presensiRow && !empty($presensiRow['jam_masuk'])) {
            header("Location: index.php?page=dashboard&status=error&msg=" . urlencode("Anda sudah melakukan absen masuk hari ini!"));
            exit;
        }

        // --- A. CEK FOTO PROFIL REFERENSI ---
        $pathFisikProfil = get_user_reference_photo($pdo, $id_user);
        if (!$pathFisikProfil) {
            header("Location: index.php?page=akun_saya&status=wajib_foto");
            exit;
        }

        // --- B. PROSES VALIDASI KE BACKEND AI PYTHON ---
        if (empty($_FILES['foto_presensi']['tmp_name'])) {
            header("Location: index.php?page=presensi_masuk&status=error&msg=" . urlencode("Foto selfie wajib diambil!"));
            exit;
        }

        $tmpSelfie = $_FILES['foto_presensi']['tmp_name'];
        $aiResult = verify_face_with_ai($pathFisikProfil, $tmpSelfie);

        if (isset($aiResult['matched']) && $aiResult['matched'] === true) {
            // Simpan foto selfie
            $target_dir = "assets/img/presensi/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['foto_presensi']['name'] ?? 'selfie.jpg', PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) $ext = 'jpg';
            $foto_nama = "masuk_" . time() . "_" . $id_user . "." . $ext;
            $fullPath = $target_dir . $foto_nama;
            
            if (move_uploaded_file($tmpSelfie, $fullPath)) {
                require_once 'helpers/WatermarkHelper.php';
                add_photo_watermark($fullPath, $nama_user, $latlong, 'Presensi Masuk');
            }

            // Ekstraksi lat & lng jika format lat,lng
            $lat = null;
            $lng = null;
            if (strpos($latlong, ',') !== false) {
                $parts = explode(',', $latlong);
                $lat = trim($parts[0]);
                $lng = trim($parts[1]);
            }

            // INSERT ke Database
            $sql = "INSERT INTO presensi (id_user, tgl_presensi, jam_masuk, lokasi_masuk, foto_masuk, foto_wajah, lat_absen, lng_absen, keterangan, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Hadir')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_user, $tgl, $jam, $latlong, $foto_nama, $foto_nama, $lat, $lng, $keterangan]);
            
            $similarityScore = $aiResult['similarity'] ?? 99.0;
            audit_log_event('presensi.masuk', ['jam' => $jam, 'lokasi' => $latlong, 'ai_similarity' => $similarityScore . '%']);
            
            $penerima = getAdminIds($pdo);
            sendNotification($pdo, $penerima, 'info', 'Personil On-Duty 🟢', "$nama_user Masuk Tugas (Match AI: $similarityScore%).", 'index.php?page=monitoring_presensi');
            
            header("Location: index.php?page=dashboard&status=clockin_success&sim=" . urlencode($similarityScore));
            exit;
        } else {
            // Wajah tidak cocok atau AI server offline
            $errMsg = $aiResult['message'] ?? 'Wajah tidak cocok dengan foto profil terdaftar!';
            $sim = $aiResult['similarity'] ?? 0;
            header("Location: index.php?page=presensi_masuk&status=face_mismatch&msg=" . urlencode($errMsg) . "&sim=" . urlencode($sim));
            exit;
        }
    }

    // ==========================================
    // 2. PROSES ABSEN PULANG (CLOCK OUT DENGAN AI FACE MATCHING)
    // ==========================================
    elseif ($act == 'clock_out' || $act == 'pulang') {
        $latlong    = $_POST['latlong'] ?? '-';
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        $jam        = date('H:i:s');
        $tgl        = date('Y-m-d');

        // Cek data presensi hari ini
        $stmtCek = $pdo->prepare("SELECT id, jam_masuk, jam_pulang FROM presensi WHERE id_user = ? AND tgl_presensi = ? ORDER BY id DESC LIMIT 1");
        $stmtCek->execute([$id_user, $tgl]);
        $presensiData = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if (!$presensiData || empty($presensiData['jam_masuk'])) {
            header("Location: index.php?page=dashboard&status=error&msg=" . urlencode("Anda belum melakukan absen masuk hari ini!"));
            exit;
        }

        if (!empty($presensiData['jam_pulang'])) {
            header("Location: index.php?page=dashboard&status=error&msg=" . urlencode("Anda sudah melakukan absen pulang hari ini!"));
            exit;
        }

        $idPresensi = $presensiData['id'];

        // --- A. CEK FOTO PROFIL REFERENSI ---
        $pathFisikProfil = get_user_reference_photo($pdo, $id_user);
        if (!$pathFisikProfil) {
            header("Location: index.php?page=akun_saya&status=wajib_foto");
            exit;
        }

        // --- B. PROSES VALIDASI KE BACKEND AI PYTHON ---
        if (empty($_FILES['foto_presensi']['tmp_name'])) {
            header("Location: index.php?page=presensi_pulang&status=error&msg=" . urlencode("Foto selfie wajib diambil!"));
            exit;
        }

        $tmpSelfie = $_FILES['foto_presensi']['tmp_name'];
        $aiResult = verify_face_with_ai($pathFisikProfil, $tmpSelfie);

        if (isset($aiResult['matched']) && $aiResult['matched'] === true) {
            // Simpan foto selfie
            $target_dir = "assets/img/presensi/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['foto_presensi']['name'] ?? 'selfie.jpg', PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) $ext = 'jpg';
            $foto_nama = "pulang_" . time() . "_" . $id_user . "." . $ext;
            $fullPath = $target_dir . $foto_nama;
            
            if (move_uploaded_file($tmpSelfie, $fullPath)) {
                require_once 'helpers/WatermarkHelper.php';
                add_photo_watermark($fullPath, $nama_user, $latlong, 'Presensi Pulang');
            }

            // Ekstraksi lat & lng jika format lat,lng
            $lat = null;
            $lng = null;
            if (strpos($latlong, ',') !== false) {
                $parts = explode(',', $latlong);
                $lat = trim($parts[0]);
                $lng = trim($parts[1]);
            }

            // UPDATE data presensi
            $sql = "UPDATE presensi SET 
                        jam_pulang = ?, 
                        lokasi_pulang = ?, 
                        foto_pulang = ?, 
                        foto_wajah_pulang = ?, 
                        lat_pulang = ?, 
                        lng_pulang = ?, 
                        keterangan_pulang = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$jam, $latlong, $foto_nama, $foto_nama, $lat, $lng, $keterangan, $idPresensi]);

            $similarityScore = $aiResult['similarity'] ?? 99.0;
            audit_log_event('presensi.pulang', ['jam' => $jam, 'lokasi' => $latlong, 'ai_similarity' => $similarityScore . '%']);
            
            $penerima = getAdminIds($pdo);
            sendNotification($pdo, $penerima, 'secondary', 'Personil Off-Duty 🔴', "$nama_user Selesai Tugas jam $jam (Match AI: $similarityScore%).", 'index.php?page=monitoring_presensi');
            
            header("Location: index.php?page=dashboard&status=clockout_success&sim=" . urlencode($similarityScore));
            exit;
        } else {
            // Wajah tidak cocok atau AI server offline
            $errMsg = $aiResult['message'] ?? 'Wajah tidak cocok dengan foto profil terdaftar!';
            $sim = $aiResult['similarity'] ?? 0;
            header("Location: index.php?page=presensi_pulang&status=face_mismatch&msg=" . urlencode($errMsg) . "&sim=" . urlencode($sim));
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

    // ==========================================
    // 5. APPROVAL LEMBUR (PENGAWAS / ADMIN)
    // ==========================================
    elseif ($act == 'approve_lembur') {
        if (!csrf_validate_request()) {
            header("Location: index.php?page=monitoring_lembur&status=forbidden");
            exit;
        }

        $id_lembur = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $status_target = (int)($_GET['status'] ?? $_POST['status'] ?? 0);
        $note = trim($_GET['note'] ?? $_POST['note'] ?? '');
        $role_user = auth_current_role();

        if ($id_lembur <= 0 || !in_array($status_target, [1, 2, 9], true)) {
            header("Location: index.php?page=monitoring_lembur&status=forbidden");
            exit;
        }

        $stmt = $pdo->prepare("SELECT id_user, status_lembur FROM lembur WHERE id_lembur = ?");
        $stmt->execute([$id_lembur]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            header("Location: index.php?page=monitoring_lembur&status=notfound");
            exit;
        }

        $owner_id = (int)$row['id_user'];
        $current_status = (int)$row['status_lembur'];
        $allowed = false;

        if ($status_target === 1 && $role_user === 'pengawas' && $current_status === 0 && $owner_id !== (int)$id_user) {
            $allowed = true;
        } elseif ($status_target === 2 && in_array($role_user, ['admin', 'administrator'], true) && $current_status === 1) {
            $allowed = true;
        } elseif ($status_target === 9) {
            if ($role_user === 'pengawas' && $current_status === 0 && $owner_id !== (int)$id_user) {
                $allowed = true;
            }
            if (in_array($role_user, ['admin', 'administrator'], true) && $current_status === 1) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            header("Location: index.php?page=monitoring_lembur&status=forbidden");
            exit;
        }

        $sql = "UPDATE lembur SET status_lembur = ?, komentar_pengawas = ? WHERE id_lembur = ?";
        $pdo->prepare($sql)->execute([$status_target, $note, $id_lembur]);
        audit_log_event('lembur.approval', [
            'id_lembur' => $id_lembur,
            'status_target' => $status_target,
            'note' => $note
        ]);

        header("Location: index.php?page=monitoring_lembur&status=success");
        exit;
    }

    // ERROR HANDLER
    else {
        header("Location: index.php?page=dashboard&status=invalid_act");
        exit;
    }

} catch (PDOException $e) {
    header("Location: index.php?page=dashboard&status=db_error");
    exit;
}
?>