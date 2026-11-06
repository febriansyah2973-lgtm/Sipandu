<?php
ob_start();
require_once 'config/database.php';
require_once 'config/helper_notif.php'; 
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/audit.php';
header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

if (!function_exists('respond_json')) {
    // Buang warning/notice yang sempat tercetak agar respons tetap JSON murni.
    function respond_json($payload) {
        if (ob_get_level() > 0) { ob_clean(); }
        echo json_encode($payload);
        exit;
    }
}

auth_session_start();
$user_id = auth_current_user_id();
if ($user_id <= 0) {
    http_response_code(401);
    respond_json(['status' => 'error', 'message' => 'Unauthenticated']);
}

function hitungJarak($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // dalam meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

$act = $_POST['act'] ?? '';
if ($act === '') {
    http_response_code(400);
    respond_json(['status' => 'error', 'message' => 'Aksi tidak valid.']);
}

if (!csrf_validate_request()) {
    http_response_code(419);
    respond_json(['status' => 'error', 'message' => 'CSRF token tidak valid']);
}

try {
    if ($act == 'scan') {
        $qr_content = trim((string)($_POST['qr_content'] ?? ''));
        $koordinat  = trim((string)($_POST['koordinat'] ?? ''));

        if ($qr_content === '') {
            respond_json(['status' => 'error', 'message' => 'Kode QR tidak valid.']);
        }
        
        // 1. Bersihkan URL jika discan via link
        if (filter_var($qr_content, FILTER_VALIDATE_URL)) {
            parse_str(parse_url($qr_content, PHP_URL_QUERY), $query);
            $qr_content = $query['code'] ?? $qr_content;
        }

        // Ambil data patokan lokasi
        $stmt = $pdo->prepare("SELECT id, nama_lokasi, latitude, longitude FROM checkpoints WHERE id = ? OR kode_qr = ? OR kode_lokasi = ?");
        $stmt->execute([$qr_content, $qr_content, $qr_content]);
        $dataCP = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dataCP) {
            respond_json(['status' => 'error', 'message' => "Kode tidak dikenal!"]);
        }

        if (!empty($koordinat)) {
            $uLoc = explode(',', $koordinat);
            if (count($uLoc) !== 2 || !is_numeric($uLoc[0]) || !is_numeric($uLoc[1])) {
                respond_json(['status' => 'error', 'message' => 'Format koordinat tidak valid.']);
            }
            $userLat = (float)$uLoc[0];
            $userLong = (float)$uLoc[1];

            // --- LOGIKA AUTO-PILOT (PENETAPAN PATOKAN) ---
            // Jika di tabel checkpoints koordinatnya masih 0, maka scan ini jadi PATOKAN PERMANEN
            if (empty($dataCP['latitude']) || $dataCP['latitude'] == 0) {
                $pdo->prepare("UPDATE checkpoints SET latitude = ?, longitude = ? WHERE id = ?")
                    ->execute([$userLat, $userLong, $dataCP['id']]);
                
                // Set variabel lokal agar tidak kena cek jarak di bawah untuk scan pertama ini
                $dataCP['latitude'] = $userLat;
                $dataCP['longitude'] = $userLong;
            }

            // 2. Cek Anti-Teleportasi (Kecepatan Wajar dari scan sebelumnya)
            $stmtLast = $pdo->prepare("SELECT waktu_scan, koordinat FROM patroli WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $stmtLast->execute([$user_id]);
            $lastScan = $stmtLast->fetch();

            if ($lastScan && !empty($lastScan['koordinat']) && $lastScan['koordinat'] != '-') {
                $lastTime = strtotime($lastScan['waktu_scan']);
                $diffMinutes = (time() - $lastTime) / 60;
                
                if ($diffMinutes > 0 && $diffMinutes < 60) {
                    $lLoc = explode(',', $lastScan['koordinat']);
                    $distTravel = hitungJarak($userLat, $userLong, $lLoc[0], $lLoc[1]);
                    $speed = ($distTravel / 1000) / ($diffMinutes / 60);

                    if ($speed > 80 && $distTravel > 500) {
                        respond_json(['status' => 'error', 'message' => "Terdeteksi Perpindahan Terlalu Cepat!"]);
                    }
                }
            }

            // 3. Cek Jarak terhadap Patokan (Geofence 100m)
            $jarakKePatokan = hitungJarak($userLat, $userLong, $dataCP['latitude'], $dataCP['longitude']);
            if ($jarakKePatokan > 100) {
                respond_json(['status' => 'error', 'message' => "Jarak Terlalu Jauh (".round($jarakKePatokan)."m). Anda harus di lokasi!"]);
            }
        }

        respond_json(['status' => 'confirm_condition', 'checkpoint_id' => $dataCP['id'], 'nama_lokasi' => $dataCP['nama_lokasi']]);
    }

    if ($act == 'simpan_aman') {
        $cp_id = (int)($_POST['checkpoint_id'] ?? 0);
        $coord = trim((string)($_POST['koordinat'] ?? '-'));
        $selfie = $_POST['foto_selfie'] ?? null;

        if ($cp_id <= 0) {
            respond_json(['status' => 'error', 'message' => 'Checkpoint tidak valid.']);
        }
        
        // Ambil data patokan untuk menghitung jarak audit yang akan disimpan di laporan
        $st = $pdo->prepare("SELECT latitude, longitude, nama_lokasi FROM checkpoints WHERE id = ?");
        $st->execute([$cp_id]);
        $cp = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $jarakAudit = 0;
        if ($cp && $coord !== '-' && strpos($coord, ',') !== false) {
            $uLoc = explode(',', $coord);
            if (count($uLoc) === 2 && is_numeric($uLoc[0]) && is_numeric($uLoc[1]) && isset($cp['latitude'], $cp['longitude'])) {
                $jarakAudit = hitungJarak((float)$uLoc[0], (float)$uLoc[1], (float)$cp['latitude'], (float)$cp['longitude']);
            }
        }

        // Simpan Foto Selfie
        $nama_file = null;
        if ($selfie) {
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $selfie));
            if ($data === false) {
                respond_json(['status' => 'error', 'message' => 'Format foto selfie tidak valid.']);
            }
            $target_dir = 'assets/img/patroli/';
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $nama_file = 'selfie_' . time() . '_' . $user_id . '.jpg';
            $fullPath = $target_dir . $nama_file;
            file_put_contents($fullPath, $data);

            require_once 'helpers/WatermarkHelper.php';
            add_photo_watermark($fullPath, auth_current_name(), $coord, $cp['nama_lokasi'] ?? '');
        }

        // SIMPAN KE TABEL PATROLI (Mencatat koordinat asli HP & Jarak auditnya)
        $sql = "INSERT INTO patroli (user_id, checkpoint_id, waktu_scan, koordinat, jarak_meter, status_temuan, status_penanganan, keterangan, foto_selfie) 
                VALUES (?, ?, NOW(), ?, ?, 'Aman', 'Selesai', 'Patroli Aman (Auto-Pilot)', ?)";
        
        $pdo->prepare($sql)->execute([
            $user_id, 
            $cp_id, 
            $coord, 
            round($jarakAudit), 
            $nama_file
        ]);

        audit_log_event('patroli.simpan_aman', [
            'checkpoint_id' => $cp_id,
            'koordinat' => $coord,
            'jarak_meter' => round($jarakAudit)
        ]);

        respond_json(['status' => 'success', 'message' => 'Laporan Berhasil Disimpan!']);
    }

    if ($act == 'patroli_lapor') {
        $cp_id = (int)($_POST['checkpoint_id'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $kategori = trim($_POST['kategori'] ?? 'Umum');
        $ket   = trim($_POST['keterangan'] ?? '');
        $coord = trim($_POST['koordinat'] ?? '-');
        $foto  = $_POST['foto_base64'] ?? null;
        $waktuFoto = trim($_POST['waktu_foto'] ?? '');

        if ($coord !== '-' && !preg_match('/^-?\d{1,3}(\.\d+)?,-?\d{1,3}(\.\d+)?$/', $coord)) {
            respond_json(['status' => 'error', 'message' => 'Format koordinat tidak valid.']);
        }

        if ($cp_id <= 0 || !$judul || !$kategori || !$ket) {
            respond_json(['status' => 'error', 'message' => 'Semua bidang wajib diisi.']);
        }

        if (!$foto) {
            respond_json(['status' => 'error', 'message' => 'Foto bukti wajib diambil.']);
        }

        $st = $pdo->prepare("SELECT latitude, longitude, nama_lokasi FROM checkpoints WHERE id = ?");
        $st->execute([$cp_id]);
        $cp = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $jarakAudit = 0;
        if ($coord !== '-' && strpos($coord, ',') !== false && isset($cp['latitude'], $cp['longitude']) && $cp['latitude'] != '' && $cp['longitude'] != '') {
            $uLoc = explode(',', $coord);
            $jarakAudit = hitungJarak($uLoc[0], $uLoc[1], $cp['latitude'], $cp['longitude']);
        }

        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $foto));
        if ($data === false) {
            respond_json(['status' => 'error', 'message' => 'Format foto bukti tidak valid.']);
        }
        $target_dir = 'assets/img/temuan/';
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $nama_file = 'temuan_' . time() . '_' . $user_id . '.jpg';
        $fullPath = $target_dir . $nama_file;
        file_put_contents($fullPath, $data);

        require_once 'helpers/WatermarkHelper.php';
        add_photo_watermark($fullPath, auth_current_name(), $coord, $cp['nama_lokasi'] ?? '');

        $deskripsi = trim("[{$judul}] [{$kategori}] {$ket}");
        $sql = "INSERT INTO patroli (user_id, checkpoint_id, waktu_scan, koordinat, jarak_meter, status_temuan, status_penanganan, keterangan, foto_bukti) 
                VALUES (?, ?, NOW(), ?, ?, 'Temuan', 'Baru', ?, ?)";
        $pdo->prepare($sql)->execute([
            $user_id,
            $cp_id,
            $coord,
            round($jarakAudit),
            $deskripsi,
            $nama_file
        ]);

        audit_log_event('patroli.lapor_temuan', [
            'checkpoint_id' => $cp_id,
            'kategori' => $kategori,
            'judul' => $judul,
            'waktu_foto_klien' => mb_substr($waktuFoto, 0, 30)
        ]);

        respond_json(['status' => 'success', 'message' => 'Laporan temuan berhasil dikirim.']);
    }

    if ($act == 'update_status_temuan') {
        $id = (int)($_POST['id'] ?? 0);
        $statusBaru = $_POST['status_baru'] ?? null;

        if ($id <= 0 || !in_array($statusBaru, ['Baru','Proses','Selesai'], true)) {
            respond_json(['status' => 'error', 'message' => 'Status tidak valid.']);
        }

        $stmt = $pdo->prepare("UPDATE patroli SET status_penanganan = ? WHERE id = ?");
        $stmt->execute([$statusBaru, $id]);

        audit_log_event('patroli.update_status_temuan', [
            'patroli_id' => $id,
            'status_baru' => $statusBaru
        ]);

        respond_json(['status' => 'success', 'message' => 'Status temuan berhasil diperbarui.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    respond_json(['status' => 'error', 'message' => 'Terjadi kesalahan sistem.']);
}