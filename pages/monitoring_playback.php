<?php
require_once 'helpers/OnvifHelper.php';
$config_file = __DIR__ . '/../config/cctv_config.json';
$cfg = [];
if (file_exists($config_file)) {
    $raw = file_get_contents($config_file);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $cfg = $decoded;
    }
}

$nvr_ip   = trim((string)($cfg['ip'] ?? ''));
$nvr_user = trim((string)($cfg['user'] ?? ''));
$nvr_pass = (string)($cfg['pass'] ?? '');
$streamLink = "";
$pesanError = "";

if (isset($_POST['btn_play'])) {
    if ($nvr_ip === '' || $nvr_user === '' || $nvr_pass === '') {
        $pesanError = "Konfigurasi NVR belum lengkap di config/cctv_config.json.";
    }

    $tgl = $_POST['tanggal'];
    $jam = $_POST['waktu'];
    if (empty($pesanError)) {
        $onvif = new OnvifHelper($nvr_ip, $nvr_user, $nvr_pass);
        $token = $onvif->getProfileToken();

        if ($token) {
            $baseRtsp = $onvif->getReplayUri($token);
        
            if ($baseRtsp) {
                $waktuLokal = strtotime("$tgl $jam");
                $waktuUTC   = gmdate('Ymd\THis\Z', $waktuLokal);
                $finalRtsp = $baseRtsp . "&starttime=" . $waktuUTC;
                $go2rtc_api = "http://localhost:1984/api/streams?src=" . urlencode($finalRtsp) . "&name=playback_cctv1";
                $streamCreated = false;

                if (function_exists('curl_init')) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $go2rtc_api);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $result = curl_exec($ch);
                    $streamCreated = ($result !== false);
                    curl_close($ch);
                } else {
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'PUT',
                            'timeout' => 10
                        ]
                    ]);
                    $result = @file_get_contents($go2rtc_api, false, $context);
                    $streamCreated = ($result !== false);
                }

                if ($streamCreated) {
                    $streamLink = "http://localhost:1984/stream.html?src=playback_cctv1";
                } else {
                    $pesanError = "Gagal menyiapkan stream playback ke go2rtc.";
                }
            } else {
                $pesanError = "Gagal mendapatkan Link RTSP dari NVR.";
            }
        } else {
            $pesanError = "Gagal koneksi ke ONVIF NVR. Cek konfigurasi IP/User/Pass.";
        }
    }
}
?>

<div class="card shadow">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-history"></i> Putar Rekaman (Playback ONVIF)</h5>
    </div>
    <div class="card-body">
        
        <form method="POST" class="row g-3 mb-4 align-items-end">
            <div class="col-md-3">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= $_POST['tanggal'] ?? date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
                <label>Jam Mulai</label>
                <input type="time" name="waktu" class="form-control" value="<?= $_POST['waktu'] ?? '08:00' ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" name="btn_play" class="btn btn-primary w-100">
                    <i class="fas fa-play"></i> Putar
                </button>
            </div>
        </form>

        <hr>

        <?php if ($pesanError): ?>
            <div class="alert alert-danger"><?= $pesanError ?></div>
        <?php endif; ?>

        <?php if ($streamLink): ?>
            <div class="ratio ratio-16x9 bg-black">
                <iframe src="<?= $streamLink ?>" allowfullscreen></iframe>
            </div>
            <div class="mt-2 text-center text-muted small">
                Sedang memutar rekaman: <b><?= $_POST['tanggal'] ?> Pukul <?= $_POST['waktu'] ?></b>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted bg-light border rounded">
                <i class="fas fa-video fa-3x mb-3"></i><br>
                Silakan pilih tanggal dan jam untuk memutar rekaman.
            </div>
        <?php endif; ?>

    </div>
</div>