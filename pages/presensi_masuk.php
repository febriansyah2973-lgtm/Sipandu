<link rel="stylesheet" href="assets/css/app.css">

<style>
    /* Styling Kartu & Tombol Baru */
    .talenta-card { border-radius: 24px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.05); }
    
    .btn-main-clock { 
        width: 200px; height: 200px; border-radius: 50%; 
        border: 8px solid #f0f2f5; 
        background: linear-gradient(145deg, #0d6efd, #0a58ca);
        color: white; cursor: pointer;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        transition: transform 0.2s;
        box-shadow: 0 10px 25px rgba(13, 110, 253, 0.3);
    }
    .btn-main-clock:active { transform: scale(0.95); }
    
    /* Camera Box */
    .camera-box { 
        width: 100%; height: 320px; 
        background: #000; border-radius: 15px; 
        overflow: hidden; position: relative;
    }
    video, #hasil_foto { width: 100%; height: 100%; object-fit: cover; }
</style>

<?php
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');

$id_user = $_SESSION['id_user'] ?? null;
$today   = date('Y-m-d');

// Cek status absen hari ini
$stmt = $pdo->prepare("SELECT jam_masuk FROM presensi WHERE id_user = ? AND tgl_presensi = ?");
$stmt->execute([$id_user, $today]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="animate-fade-up px-2">
    <div class="mb-4">
        <h5 class="text-secondary mb-1">Halo, <strong><?= explode(' ', $_SESSION['nama_lengkap'] ?? 'User')[0] ?></strong> ??</h5>
        <h3 class="fw-bold text-dark">Presensi Wajah</h3>
    </div>

    <div class="card talenta-card p-5 text-center bg-white">
        <div class="d-flex justify-content-center mb-4">
            
            <?php if (!$d || !$d['jam_masuk']): ?>
                <button type="button" class="btn-main-clock" onclick="bukaModalKamera()">
                    <i class="fas fa-camera-retro fa-3x mb-2"></i>
                    <span class="fw-bold fs-5">AMBIL FOTO</span>
                    <small style="font-size: 0.7rem; opacity: 0.8;">Absen Masuk</small>
                </button>
            <?php else: ?>
                <div class="py-4 text-center">
                    <div class="bg-success-subtle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px;">
                        <i class="fas fa-check fa-4x text-success"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Berhasil Masuk!</h4>
                    <p class="text-muted small">Pukul: <strong><?= date('H:i', strtotime($d['jam_masuk'])) ?> WIB</strong></p>
                    <a href="index.php?page=dashboard" class="btn btn-outline-primary rounded-pill px-4 mt-2">Kembali</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div class="modal fade" id="modalPresensi" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-check me-2 text-primary"></i>Verifikasi Wajah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="tutupKamera()"></button>
            </div>
            
            <form action="proses_presensi.php" method="POST" enctype="multipart/form-data" id="formPresensi">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="clock_in">
                    <input type="hidden" name="latlong" id="latlong">
                    <input type="file" name="foto_presensi" id="file_foto_input" class="d-none">

                    <div class="row g-4">
                        <div class="col-md-6 text-center">
                            <div class="camera-box bg-dark mb-2">
                                <video id="video" autoplay playsinline></video>
                                <canvas id="canvas" style="display:none;"></canvas>
                                <img id="hasil_foto" src="#" style="display:none;">
                            </div>
                            <button type="button" id="btnUlang" class="btn btn-sm btn-warning rounded-pill px-3 shadow-sm mb-2" style="display:none;" onclick="ulangKamera()">
                                <i class="fas fa-redo me-1"></i> Foto Ulang
                            </button>
                            <div id="status_lokasi" class="small fw-bold text-muted bg-light p-2 rounded border">
                                <i class="fas fa-spinner fa-spin text-primary me-1"></i> Mencari GPS...
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="alert alert-primary border-0 bg-primary-subtle small mb-3">
                                <i class="fas fa-info-circle me-1"></i> Pastikan wajah terlihat jelas tanpa masker.
                            </div>
                            <label class="form-label small fw-bold text-dark">Catatan (Opsional)</label>
                            <textarea name="keterangan" class="form-control border-0 bg-light" rows="5" placeholder="Contoh: Datang lebih awal..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" id="btnJepret" onclick="ambilFoto()" class="btn btn-primary rounded-pill px-5 fw-bold w-100 py-3">
                        <i class="fas fa-camera me-2"></i> JEPRET FOTO
                    </button>
                    <button type="submit" id="btnKirim" class="btn btn-success rounded-pill px-5 fw-bold w-100 py-3" style="display:none;">
                        <i class="fas fa-paper-plane me-2"></i> KIRIM ABSEN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const fotoPreview = document.getElementById('hasil_foto');
    const fileInput = document.getElementById('file_foto_input');
    const lokasiInput = document.getElementById('latlong');
    const statusLokasi = document.getElementById('status_lokasi');
    const btnJepret = document.getElementById('btnJepret');
    const btnKirim = document.getElementById('btnKirim');
    const btnUlang = document.getElementById('btnUlang');
    let streamKamera = null;

    // Fix Backdoor Modal
    document.addEventListener("DOMContentLoaded", function() {
        const modalEl = document.getElementById('modalPresensi');
        document.body.appendChild(modalEl);
    });

    function bukaModalKamera() {
        // 1. Ambil Lokasi
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    lokasiInput.value = pos.coords.latitude + "," + pos.coords.longitude;
                    statusLokasi.innerHTML = "<i class='fas fa-map-marker-alt text-success me-1'></i> Lokasi Terkunci";
                },
                function(err) {
                    statusLokasi.innerHTML = "<i class='fas fa-exclamation-circle text-danger me-1'></i> GPS Mati!";
                }
            );
        }

        // 2. Nyalakan Kamera
        navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
            .then(function(stream) {
                streamKamera = stream;
                video.srcObject = stream;
                var myModal = new bootstrap.Modal(document.getElementById('modalPresensi'));
                myModal.show();
            })
            .catch(function(err) { alert("Kamera error: " + err); });
    }

    function ambilFoto() {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        video.style.display = 'none';
        fotoPreview.src = canvas.toDataURL('image/jpeg');
        fotoPreview.style.display = 'block';

        canvas.toBlob(function(blob) {
            let file = new File([blob], "selfie.jpg", { type: "image/jpeg" });
            let container = new DataTransfer();
            container.items.add(file);
            fileInput.files = container.files;
        }, 'image/jpeg');

        btnJepret.style.display = 'none';
        btnKirim.style.display = 'block';
        btnUlang.style.display = 'inline-block';
    }

    function ulangKamera() {
        video.style.display = 'block';
        fotoPreview.style.display = 'none';
        btnJepret.style.display = 'block';
        btnKirim.style.display = 'none';
        btnUlang.style.display = 'none';
        fileInput.value = "";
    }

    function tutupKamera() {
        if (streamKamera) streamKamera.getTracks().forEach(track => track.stop());
    }
</script>