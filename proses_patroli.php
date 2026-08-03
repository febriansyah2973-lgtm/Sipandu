<?php
session_start();
require_once 'config/database.php';
require_once 'config/helper_notif.php'; 
header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

function hitungJarak($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // dalam meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 1;
$act = $_POST['act'] ?? '';

try {
    if ($act == 'scan') {
        $qr_content = trim($_POST['qr_content']); 
        $koordinat  = $_POST['koordinat'] ?? '';
        
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
            echo json_encode(['status' => 'error', 'message' => "Kode tidak dikenal!"]); exit;
        }

        if (!empty($koordinat)) {
            $uLoc = explode(',', $koordinat);
            $userLat = $uLoc[0]; $userLong = $uLoc[1];

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
                        echo json_encode(['status' => 'error', 'message' => "Terdeteksi Perpindahan Terlalu Cepat!"]); exit;
                    }
                }
            }

            // 3. Cek Jarak terhadap Patokan (Geofence 100m)
            $jarakKePatokan = hitungJarak($userLat, $userLong, $dataCP['latitude'], $dataCP['longitude']);
            if ($jarakKePatokan > 100) {
                echo json_encode(['status' => 'error', 'message' => "Jarak Terlalu Jauh (".round($jarakKePatokan)."m). Anda harus di lokasi!"]);
                exit;
            }
        }

        echo json_encode(['status' => 'confirm_condition', 'checkpoint_id' => $dataCP['id'], 'nama_lokasi' => $dataCP['nama_lokasi']]);
        exit;
    }

    if ($act == 'simpan_aman') {
        $cp_id = $_POST['checkpoint_id'];
        $coord = $_POST['koordinat'] ?? '-';
        $selfie = $_POST['foto_selfie'] ?? null;
        
        // Ambil data patokan untuk menghitung jarak audit yang akan disimpan di laporan
        $st = $pdo->prepare("SELECT latitude, longitude FROM checkpoints WHERE id = ?");
        $st->execute([$cp_id]);
        $cp = $st->fetch();

        $uLoc = explode(',', $coord);
        $jarakAudit = hitungJarak($uLoc[0], $uLoc[1], $cp['latitude'], $cp['longitude']);

        // Simpan Foto Selfie
        $nama_file = null;
        if ($selfie) {
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $selfie));
            $target_dir = 'assets/img/patroli/';
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $nama_file = 'selfie_' . time() . '_' . $user_id . '.jpg';
            file_put_contents($target_dir . $nama_file, $data);
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

        echo json_encode(['status' => 'success', 'message' => 'Laporan Berhasil Disimpan!']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}