<style>
    .talenta-card { border-radius: 24px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.05); }
    
    .btn-main-clock { 
        width: 200px; height: 200px; border-radius: 50%; 
        border: 8px solid #f0f2f5; 
        background: linear-gradient(145deg, #2563eb, #1d4ed8);
        color: white; cursor: pointer;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
    }
    .btn-main-clock:hover { transform: scale(1.03); box-shadow: 0 14px 35px rgba(37, 99, 235, 0.4); }
    .btn-main-clock:active { transform: scale(0.95); }
    
    .camera-box-recognition { 
        width: 100%; height: 340px; 
        background: #0f172a; border-radius: 20px; 
        overflow: hidden; position: relative;
        border: 2px solid rgba(59, 130, 246, 0.3);
    }
    
    video, #hasil_foto { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
    
    .face-oval-guide {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 180px; height: 230px;
        border: 3px dashed #3b82f6;
        border-radius: 50%;
        box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.65);
        pointer-events: none;
        z-index: 2;
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
</style>

<?php
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');

$id_user = $_SESSION['id_user'] ?? null;
$today   = date('Y-m-d');

// Cek profil foto user
$uStmt = $pdo->prepare("SELECT foto_profil, foto FROM users WHERE id = ?");
$uStmt->execute([$id_user]);
$uData = $uStmt->fetch(PDO::FETCH_ASSOC);
$fotoProfil = $uData['foto_profil'] ?? $uData['foto'] ?? '';

// Validasi fisik foto profil untuk syarat AI
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

// Cek status absen hari ini
$stmt = $pdo->prepare("SELECT jam_masuk FROM presensi WHERE id_user = ? AND tgl_presensi = ?");
$stmt->execute([$id_user, $today]);
$d = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="animate-fade-up px-2">
    <div class="mb-4">
        <h5 class="text-secondary mb-1">Halo, <strong><?= htmlspecialchars(explode(' ', $_SESSION['nama_lengkap'] ?? 'User')[0]) ?></strong> 👋</h5>
        <h3 class="fw-bold text-dark"><i class="fas fa-user-check text-primary me-2"></i>Presensi Face Recognition</h3>
    </div>

    <div class="card card-master p-5 text-center bg-white mb-4 shadow-sm" style="border-radius: 20px;">
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
                <!-- Tombol langsung bisa diklik tanpa nunggu loading AI -->
                <button type="button" class="btn-main-clock" onclick="bukaModalKamera()">
                    <i class="fas fa-camera-retro fa-3x mb-2"></i>
                    <span class="fw-bold fs-5">PINDAI WAJAH</span>
                    <small style="font-size: 0.75rem; opacity: 0.85;">Absen Masuk Tugas</small>
                </button>
            <?php else: ?>
                <div class="py-4 text-center">
                    <div class="bg-success-subtle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px;">
                        <i class="fas fa-check fa-4x text-success"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Berhasil Presensi Masuk!</h4>
                    <p class="text-muted small">Waktu Masuk: <strong><?= date('H:i', strtotime($d['jam_masuk'])) ?> WIB</strong></p>
                    <a href="index.php?page=dashboard" class="btn btn-primary rounded-pill px-4 mt-2">Kembali ke Dashboard</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div class="modal fade" id="modalPresensi" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-id-badge text-primary me-2"></i>Ambil Foto Selfie Validasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="tutupKamera()"></button>
            </div>
            
            <form action="proses_presensi.php" method="POST" enctype="multipart/form-data" id="formPresensi" onsubmit="return handleFormPresensiSubmit(event)">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="clock_in">
                    <input type="hidden" name="latlong" id="latlong">
                    <input type="file" name="foto_presensi" id="file_foto_input" class="d-none">

                    <div class="row g-4">
                        <div class="col-md-6 text-center">
                            <div class="camera-box-recognition mb-2">
                                <span class="status-recognition-badge">
                                    <i class="fas fa-camera me-1"></i> Posisikan Wajah Anda
                                </span>
                                
                                <div class="face-oval-guide"></div>

                                <video id="video" autoplay playsinline></video>
                                <canvas id="canvas" style="display:none;"></canvas>
                                <img id="hasil_foto" src="#" style="display:none;">
                            </div>

                            <button type="button" id="btnUlang" class="btn btn-warning btn-sm rounded-pill mb-2 fw-bold" style="display:none;" onclick="ulangKamera()">
                                <i class="fas fa-redo me-1"></i> Ulangi Foto
                            </button>
                            <div id="status_lokasi" class="small fw-bold text-muted bg-light p-2 rounded-3 border">
                                <i class="fas fa-spinner fa-spin text-primary me-1"></i> Mengunci Lokasi GPS...
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="alert alert-primary bg-primary-subtle border-0 small mb-3 rounded-3 text-primary">
                                <i class="fas fa-brain me-1"></i> <strong>Peringatan AI Server:</strong> Pastikan wajah terlihat jelas tanpa penutup. Server akan memblokir presensi otomatis jika wajah tidak cocok dengan profil!
                            </div>
                            <label class="form-label small fw-bold text-dark d-block text-center mb-2">Laporan Ringkasan Tugas / Shift Masuk</label>
                            <textarea name="keterangan" class="form-control bg-light border-0 py-3" rows="5" style="border-radius: 14px; text-align: center; font-size: 0.95rem;" placeholder="Tuliskan catatan shift atau lokasi jaga pos Anda..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" id="btnJepret" onclick="ambilFoto()" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-camera me-2"></i> AMBIL FOTO SEKARANG
                    </button>
                    <button type="submit" id="btnKirim" class="btn btn-success w-100 py-3 rounded-pill fw-bold shadow-sm" style="display:none;">
                        <i class="fas fa-paper-plane me-2"></i> KIRIM KE SERVER AI & ABSEN
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

    function bukaModalKamera() {
        // Ambil GPS
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    lokasiInput.value = pos.coords.latitude + "," + pos.coords.longitude;
                    statusLokasi.innerHTML = "<i class='fas fa-check-circle text-success me-1'></i> Lokasi Terkunci";
                },
                function(err) {
                    statusLokasi.innerHTML = "<i class='fas fa-exclamation-circle text-danger me-1'></i> GPS Mati!";
                }
            );
        }

        // Buka Kamera HP langsung tanpa nunggu AI
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
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        
        let ctx = canvas.getContext('2d');
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1); // Efek cermin
        ctx.drawImage(video, 0, 0);

        video.style.display = 'none';
        fotoPreview.src = canvas.toDataURL('image/jpeg');
        fotoPreview.style.display = 'block';

        canvas.toBlob(function(blob) {
            let file = new File([blob], "selfie_face.jpg", { type: "image/jpeg" });
            let container = new DataTransfer();
            container.items.add(file);
            fileInput.files = container.files;
        }, 'image/jpeg');

        btnJepret.style.display = 'none';
        btnKirim.style.display = 'inline-flex';
        btnUlang.style.display = 'inline-flex';
    }

    function ulangKamera() {
        video.style.display = 'block';
        fotoPreview.style.display = 'none';
        
        btnJepret.style.display = 'inline-flex';
        btnKirim.style.display = 'none';
        btnUlang.style.display = 'none';
        fileInput.value = "";
    }

    function tutupKamera() {
        if (streamKamera) streamKamera.getTracks().forEach(track => track.stop());
    }

    function handleFormPresensiSubmit(e) {
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Foto Belum Diambil', text: 'Silakan ambil foto selfie terlebih dahulu!' });
            return false;
        }
        btnKirim.disabled = true;
        btnKirim.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Menganalisa Wajah di Server AI...';
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
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'error') {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: msg ? decodeURIComponent(msg) : 'Terjadi kesalahan sistem presensi.',
                confirmButtonColor: '#2563eb'
            });
        }
        if (status) {
            window.history.replaceState({}, document.title, window.location.pathname + "?page=presensi_masuk");
        }
    });
</script>