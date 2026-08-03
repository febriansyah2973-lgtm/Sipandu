<link rel="stylesheet" href="assets/css/app.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>

<style>
    /* CSS Validasi User - Pojok Kanan Atas sesuai request Bapak */
    .user-validation-top {
        position: fixed;
        top: 20px;
        right: 25px;
        z-index: 1060;
        background: #ffffff;
        padding: 10px 20px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 2px solid #28a745;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideInRight 0.5s ease-out;
    }
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .modal { z-index: 9999 !important; }
    .modal-backdrop { z-index: 9998 !important; }
    .talenta-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .camera-box { width: 100%; max-width: 320px; height: 240px; background: #000; border-radius: 15px; overflow: hidden; margin: 0 auto; border: 3px solid #f8f9fc; }
    .btn-main-clock { width: 180px; height: 180px; border-radius: 50%; border: 8px solid #f8f9fc; font-weight: bold; transition: all 0.3s; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .btn-main-clock:hover { transform: scale(1.05); }
</style>

<?php
/**
 * BACKEND LOGIC - SINKRONISASI DATABASE RAD
 */
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');

// SINKRONISASI SESSION: Menggunakan id_user sesuai database Bapak
$id_user = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? null;

if (!$id_user) {
    echo "<script>alert('Sesi habis, silakan login kembali'); window.location='login.php';</script>";
    exit;
}

$today = date('Y-m-d');

/** * SINKRONISASI QUERY: 
 * Ganti user_id -> id_user & tanggal -> tgl_presensi
 */
$stmt = $pdo->prepare("SELECT jam_masuk, jam_pulang FROM presensi WHERE id_user = ? AND tgl_presensi = ?");
$stmt->execute([$id_user, $today]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="animate-fade-up px-2">
    <div class="mb-4">
        <h5 class="text-secondary mb-1">Halo, <strong><?= explode(' ', $_SESSION['nama_lengkap'] ?? 'User')[0] ?></strong> ??</h5>
        <h3 class="fw-bold text-dark">Absen Pulang Kerja</h3>
    </div>

    <div class="card talenta-card p-5 text-center mb-4 bg-white">
        <div class="d-flex justify-content-center mb-4">
            <?php if (!$d || !$d['jam_masuk']): ?>
                <div class="alert alert-warning border-0 small py-4 px-5 rounded-4 shadow-sm">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3 d-block text-warning"></i>
                    <h5 class="fw-bold">Akses Ditolak</h5>
                    <p class="mb-0">Anda belum melakukan <b>Absen Masuk</b> hari ini. Silakan absen masuk terlebih dahulu.</p>
                </div>
            <?php elseif ($d['jam_masuk'] && !$d['jam_pulang']): ?>
                <button type="button" class="btn btn-danger btn-main-clock shadow-danger" onclick="openPresensiModal()">
                    <i class="fas fa-sign-out-alt fa-3x mb-2"></i>
                    <span>CLOCK OUT</span>
                </button>
            <?php else: ?>
                <div class="py-4 text-center">
                    <div class="bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px; border: 5px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                        <i class="fas fa-check-double fa-4x text-primary"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Tugas Selesai</h4>
                    <p class="text-muted small">Anda sudah absen pulang pada pukul <b><?= date('H:i', strtotime($d['jam_pulang'])) ?> WIB</b>.<br>Sampai jumpa besok!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPulang" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="fas fa-stopwatch me-2"></i>Konfirmasi Absen Pulang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopWebcam()"></button>
            </div>
            <form action="proses_presensi.php" method="POST" id="formPulang">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="pulang">
                    <input type="hidden" name="image" id="image_tag">
                    <input type="hidden" name="lokasi_val" id="lokasi_val">
                    <div class="row g-4">
                        <div class="col-md-6 text-center">
                            <div id="my_camera" class="camera-box"></div>
                            <div id="location-display" class="mt-3 small text-muted bg-light p-2 rounded text-start">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>Mencari lokasi...
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Laporan Pekerjaan Hari Ini</label>
                            <textarea name="keterangan" class="form-control border-0 bg-light" rows="6" style="border-radius: 12px;" placeholder="Tuliskan ringkasan tugas yang telah diselesaikan hari ini..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" onclick="take_snapshot()" class="btn btn-danger rounded-pill px-5 fw-bold shadow-sm w-100 py-3">KIRIM ABSEN PULANG <i class="fas fa-paper-plane ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.body.appendChild(document.getElementById('modalPulang'));
    });

    function openPresensiModal() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('lokasi_val').value = position.coords.latitude + "," + position.coords.longitude;
                document.getElementById('location-display').innerHTML = "<i class='fas fa-check-circle text-success me-2'></i>Lokasi Berhasil Terkunci";
            });
        }
        Webcam.set({ 
            width: 320, 
            height: 240, 
            image_format: 'jpeg', 
            jpeg_quality: 90,
            constraints: { facingMode: 'user' }
        });
        Webcam.attach('#my_camera');
        new bootstrap.Modal(document.getElementById('modalPulang')).show();
    }

    function stopWebcam() { Webcam.reset(); }

    function take_snapshot() {
        Webcam.snap(function(data_uri) {
            document.getElementById('image_tag').value = data_uri;
            document.getElementById('formPulang').submit();
        });
    }
</script>