<style>
    .camera-box-recognition { 
        width: 100%; height: 320px; 
        background: #0f172a; border-radius: 20px; 
        overflow: hidden; position: relative;
        border: 2px solid rgba(239, 68, 68, 0.3);
    }
    
    video, #hasil_foto_pulang { width: 100%; height: 100%; object-fit: cover; }
    
    .face-oval-guide {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 180px; height: 230px;
        border: 3px dashed #ef4444;
        border-radius: 50%;
        box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.65);
        pointer-events: none;
        z-index: 2;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    
    .face-oval-guide.matched {
        border: 3px solid #10b981;
        box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.5), 0 0 25px rgba(16, 185, 129, 0.8);
    }
    
    .scan-laser-line {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, #ef4444, transparent);
        box-shadow: 0 0 15px #ef4444;
        animation: scanMove 2.2s infinite ease-in-out;
        z-index: 3;
    }
    
    @keyframes scanMove {
        0% { top: 10%; opacity: 0.2; }
        50% { top: 85%; opacity: 1; }
        100% { top: 10%; opacity: 0.2; }
    }
    
    .status-recognition-badge {
        position: absolute;
        top: 15px; left: 50%;
        transform: translateX(-50%);
        z-index: 4;
        font-size: 0.8rem;
        font-weight: 700;
        border-radius: 50px;
        padding: 6px 16px;
        background: rgba(15, 23, 42, 0.85);
        color: #ffffff;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .btn-main-clock-out {
        width: 180px; height: 180px;
        border-radius: 50%;
        border: 8px solid #f0f2f5;
        background: linear-gradient(145deg, #ef4444, #dc2626);
        color: white;
        font-weight: bold;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
        cursor: pointer;
    }
    .btn-main-clock-out:hover { transform: scale(1.04); box-shadow: 0 14px 35px rgba(239, 68, 68, 0.4); }
</style>

<?php
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');

$id_user = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? null;

if (!$id_user) {
    echo "<script>alert('Sesi habis, silakan login kembali'); window.location='login.php';</script>";
    exit;
}

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT jam_masuk, jam_pulang FROM presensi WHERE id_user = ? AND tgl_presensi = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$id_user, $today]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);

// Cek profil foto user
$uStmt = $pdo->prepare("SELECT foto_profil, foto FROM users WHERE id = ?");
$uStmt->execute([$id_user]);
$uData = $uStmt->fetch(PDO::FETCH_ASSOC);
$fotoProfil = $uData['foto_profil'] ?? $uData['foto'] ?? '';

$hasFotoProfil = false;
$searchDirs = ['uploads/profil/', 'assets/uploads/profil/', 'assets/img/users/'];
if (!empty($fotoProfil) && $fotoProfil !== 'default.png' && $fotoProfil !== 'default.jpg') {
    foreach ($searchDirs as $sDir) {
        if (file_exists($sDir . $fotoProfil)) {
            $hasFotoProfil = true;
            break;
        }
    }
}
?>

<div class="animate-fade-up px-2">
    <div class="mb-4">
        <h5 class="text-secondary mb-1">Halo, <strong><?= htmlspecialchars(explode(' ', $_SESSION['nama_lengkap'] ?? 'User')[0]) ?></strong> 👋</h5>
        <h3 class="fw-bold text-dark"><i class="fas fa-sign-out-alt text-danger me-2"></i>Presensi Pulang Kerja</h3>
    </div>

    <div class="card card-master p-5 text-center mb-4 bg-white shadow-sm" style="border-radius: 20px;">
        <div class="d-flex justify-content-center mb-4">
            <?php if (!$hasFotoProfil): ?>
                <div class="alert alert-warning border-0 p-4 rounded-4 shadow-sm text-center" style="max-width: 540px;">
                    <div class="mb-3 text-warning"><i class="fas fa-id-card-alt fa-3x"></i></div>
                    <h5 class="fw-bold text-dark mb-2">Foto Profil Belum Terdaftar</h5>
                    <p class="text-secondary small mb-3">
                        Sistem <b>Face Recognition AI</b> mewajibkan Anda memiliki foto profil asli sebagai acuan verifikasi wajah di server.
                    </p>
                    <a href="index.php?page=akun_saya" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                        <i class="fas fa-upload me-2"></i> Upload Foto Profil Sekarang
                    </a>
                </div>
            <?php elseif (!$d || !$d['jam_masuk']): ?>
                <div class="alert alert-warning border-0 small py-4 px-5 rounded-4 shadow-sm" style="max-width: 540px;">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3 d-block text-warning"></i>
                    <h5 class="fw-bold text-dark">Belum Absen Masuk</h5>
                    <p class="mb-3 text-secondary">Anda belum melakukan <b>Absen Masuk</b> hari ini. Silakan absen masuk terlebih dahulu sebelum absen pulang.</p>
                    <a href="index.php?page=presensi_masuk" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-camera me-2"></i> Ke Halaman Presensi Masuk
                    </a>
                </div>
            <?php elseif ($d['jam_masuk'] && !$d['jam_pulang']): ?>
                <button type="button" class="btn-main-clock-out" onclick="openPresensiModal()">
                    <i class="fas fa-camera-retro fa-3x mb-2"></i>
                    <span class="fs-5">PINDAI PULANG</span>
                    <small style="font-size: 0.75rem; opacity: 0.85;">Absen Pulang Tugas</small>
                </button>
            <?php else: ?>
                <div class="py-4 text-center">
                    <div class="bg-success-subtle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px;">
                        <i class="fas fa-check-double fa-4x text-success"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Tugas Selesai!</h4>
                    <p class="text-muted small">Anda telah absen pulang pada pukul <b><?= date('H:i', strtotime($d['jam_pulang'])) ?> WIB</b>.<br>Sampai jumpa besok!</p>
                    <a href="index.php?page=dashboard" class="btn btn-primary rounded-pill px-4 mt-2">Kembali ke Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPulang" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-user-check text-danger me-2"></i>Verifikasi Face Recognition Absen Pulang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopWebcam()"></button>
            </div>
            <form action="proses_presensi.php" method="POST" id="formPulang" enctype="multipart/form-data" onsubmit="return handleFormPulangSubmit(event)">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="clock_out">
                    <input type="hidden" name="latlong" id="lokasi_val">
                    <input type="file" name="foto_presensi" id="file_foto_input_pulang" class="d-none">

                    <div class="row g-4">
                        <div class="col-md-6 text-center">
                            <div class="camera-box-recognition mb-2">
                                <span id="recogStatusPulang" class="status-recognition-badge">
                                    <i class="fas fa-camera me-1"></i> Posisikan Wajah di Lingkaran
                                </span>
                                
                                <div class="face-oval-guide" id="faceOvalGuidePulang">
                                    <div class="scan-laser-line"></div>
                                </div>

                                <video id="videoPulang" autoplay playsinline></video>
                                <canvas id="canvasPulang" style="display:none;"></canvas>
                                <img id="hasil_foto_pulang" src="#" style="display:none;">
                            </div>

                            <button type="button" id="btnUlangPulang" class="btn btn-warning btn-sm rounded-pill mb-2 fw-bold" style="display:none;" onclick="ulangKameraPulang()">
                                <i class="fas fa-redo me-1"></i> Ulangi Foto
                            </button>

                            <div id="location-display" class="small fw-bold text-muted bg-light p-2 rounded-3 border text-center">
                                <i class="fas fa-spinner fa-spin text-danger me-1"></i> Mengunci Lokasi GPS...
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-danger bg-danger-subtle border-0 small mb-3 rounded-3 text-danger">
                                <i class="fas fa-brain me-1"></i> <strong>Peringatan AI Server:</strong> Pastikan pencahayaan cukup dan wajah menghadap lurus ke kamera. Server AI akan mencocokkan wajah Anda secara otomatis!
                            </div>
                            <label class="form-label small fw-bold text-dark d-block text-center mb-2">Laporan Ringkasan Tugas Selesai Hari Ini</label>
                            <textarea name="keterangan" class="form-control bg-light border-0 py-3" rows="5" style="border-radius: 14px; text-align: center; font-size: 0.95rem;" placeholder="Tuliskan catatan shift akhir atau laporan serah terima tugas Anda..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" id="btnJepretPulang" onclick="ambilFotoPulang()" class="btn btn-danger w-100 py-3 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-camera me-2"></i> AMBIL FOTO & VERIFIKASI SEKARANG
                    </button>
                    <button type="submit" id="btnKirimPulang" class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow-sm" style="display:none;">
                        <i class="fas fa-paper-plane me-2"></i> KIRIM KE SERVER AI & ABSEN PULANG
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let videoPulang = document.getElementById('videoPulang');
    let canvasPulang = document.getElementById('canvasPulang');
    let fotoPreviewPulang = document.getElementById('hasil_foto_pulang');
    let fileInputPulang = document.getElementById('file_foto_input_pulang');
    let recogStatusPulang = document.getElementById('recogStatusPulang');
    let faceOvalGuidePulang = document.getElementById('faceOvalGuidePulang');
    let btnJepretPulang = document.getElementById('btnJepretPulang');
    let btnKirimPulang = document.getElementById('btnKirimPulang');
    let btnUlangPulang = document.getElementById('btnUlangPulang');
    let streamPulang = null;

    function openPresensiModal() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    document.getElementById('lokasi_val').value = pos.coords.latitude + "," + pos.coords.longitude;
                    document.getElementById('location-display').innerHTML = "<i class='fas fa-check-circle text-success me-1'></i> Lokasi Terkunci";
                },
                function(err) {
                    document.getElementById('location-display').innerHTML = "<i class='fas fa-exclamation-circle text-danger me-1'></i> GPS Mati!";
                }
            );
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
            .then(function(stream) {
                streamPulang = stream;
                videoPulang.srcObject = stream;
                new bootstrap.Modal(document.getElementById('modalPulang')).show();
            })
            .catch(function(err) { alert("Kamera error: " + err); });
    }

    function stopWebcam() { 
        if (streamPulang) streamPulang.getTracks().forEach(track => track.stop()); 
    }

    function ambilFotoPulang() {
        canvasPulang.width = videoPulang.videoWidth || 640;
        canvasPulang.height = videoPulang.videoHeight || 480;
        
        let ctx = canvasPulang.getContext('2d');
        ctx.translate(canvasPulang.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(videoPulang, 0, 0);

        videoPulang.style.display = 'none';
        fotoPreviewPulang.src = canvasPulang.toDataURL('image/jpeg');
        fotoPreviewPulang.style.display = 'block';

        canvasPulang.toBlob(function(blob) {
            let file = new File([blob], "selfie_pulang.jpg", { type: "image/jpeg" });
            let container = new DataTransfer();
            container.items.add(file);
            fileInputPulang.files = container.files;
        }, 'image/jpeg');

        btnJepretPulang.style.display = 'none';
        btnKirimPulang.style.display = 'inline-flex';
        btnUlangPulang.style.display = 'inline-flex';
    }

    function ulangKameraPulang() {
        videoPulang.style.display = 'block';
        fotoPreviewPulang.style.display = 'none';

        btnJepretPulang.style.display = 'inline-flex';
        btnKirimPulang.style.display = 'none';
        btnUlangPulang.style.display = 'none';
        fileInputPulang.value = "";
    }

    function handleFormPulangSubmit(e) {
        if (!fileInputPulang.files || fileInputPulang.files.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Foto Belum Diambil', text: 'Silakan ambil foto selfie terlebih dahulu!' });
            return false;
        }
        btnKirimPulang.disabled = true;
        btnKirimPulang.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Menganalisa Wajah di Server AI...';
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');
        const sim = urlParams.get('sim');

        if (status === 'face_mismatch') {
            Swal.fire({
                icon: 'error',
                title: 'Verifikasi Wajah Gagal!',
                html: (msg ? decodeURIComponent(msg) : 'Wajah tidak sesuai dengan foto profil!') + (sim ? `<br><br><span class="badge bg-danger fs-6 p-2">Skor Kemiripan: ${sim}%</span>` : ''),
                confirmButtonText: 'Coba Lagi',
                confirmButtonColor: '#ef4444'
            });
        } else if (status === 'error') {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: msg ? decodeURIComponent(msg) : 'Terjadi kesalahan sistem presensi.',
                confirmButtonColor: '#ef4444'
            });
        }
        if (status) {
            window.history.replaceState({}, document.title, window.location.pathname + "?page=presensi_pulang");
        }
    });
</script>
