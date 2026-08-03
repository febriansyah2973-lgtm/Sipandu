<?php
require_once 'helpers/OnvifHelper.php';
$nvr_ip   = '192.168.1.100';
$nvr_user = 'admin';
$nvr_pass = 'password123';
$streamLink = "";
$pesanError = "";

if (isset($_POST['btn_play'])) {
    $tgl = $_POST['tanggal'];
    $jam = $_POST['waktu'];
    $onvif = new OnvifHelper($nvr_ip, $nvr_user, $nvr_pass);
    $token = $onvif->getProfileToken();

    if ($token) {
        $baseRtsp = $onvif->getReplayUri($token);
        
        if ($baseRtsp) {
            $waktuLokal = strtotime("$tgl $jam");
            $waktuUTC   = gmdate('Ymd\THis\Z', $waktuLokal);
            $finalRtsp = $baseRtsp . "&starttime=" . $waktuUTC;
            $go2rtc_api = "http://localhost:1984/api/streams?src=" . urlencode($finalRtsp) . "&name=playback_cctv1";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $go2rtc_api);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); 
            curl_exec($ch);
            curl_close($ch);
            $streamLink = "http://localhost:1984/stream.html?src=playback_cctv1";

        } else {
            $pesanError = "Gagal mendapatkan Link RTSP dari NVR.";
        }
    } else {
        $pesanError = "Gagal koneksi ke ONVIF NVR. Cek IP/User/Pass.";
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