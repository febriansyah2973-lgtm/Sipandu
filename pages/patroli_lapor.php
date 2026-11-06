<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
$csrf_token = csrf_token_value();

$cp_id = $_GET['checkpoint_id'] ?? '';
$cp_nama = '';
$cp_kode = '';

if($cp_id) {
    $stmt = $pdo->prepare("SELECT nama_lokasi, kode_lokasi FROM checkpoints WHERE id = ?");
    $stmt->execute([$cp_id]);
    $row = $stmt->fetch();
    $cp_nama = $row['nama_lokasi'] ?? '';
    $cp_kode = $row['kode_lokasi'] ?? '';
}
$locations = $pdo->query("SELECT * FROM checkpoints ORDER BY nama_lokasi ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Patroli Lapor</h3>
        <p class="text-secondary mb-0 small">Laporkan kejadian atau kondisi janggal di lokasi.</p>
    </div>
    <a href="index.php?page=patroli_scan" class="btn btn-outline-secondary btn-sm rounded-pill shadow-sm">
        <i class="fas fa-arrow-left me-1"></i> Kembali Scan
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card-master shadow-sm border-0">
            <div class="card-header-action bg-danger-subtle text-danger">
                <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Form Laporan Patroli</h6>
            </div>
            
            <form id="formPatroliLapor" enctype="multipart/form-data">
                <input type="hidden" name="act" value="patroli_lapor">
                <input type="hidden" name="koordinat" id="inputKoordinat">
                <input type="hidden" name="waktu_foto" id="inputWaktu">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-2">Lokasi Kejadian</label>
                        <?php if($cp_id): ?>
                            <input type="hidden" name="checkpoint_id" value="<?= $cp_id ?>">
                            <div class="input-group">
                                <input type="text" class="form-control bg-light border-0 rounded-start-3 py-2" value="<?= $cp_nama ?>" readonly>
                                <span class="input-group-text bg-white border-0 rounded-end-3 text-primary font-monospace"><?= htmlspecialchars($cp_kode) ?></span>
                            </div>
                        <?php else: ?>
                            <select name="checkpoint_id" class="form-select bg-light border-0 rounded-3 py-2" required>
                                <option value="">-- Pilih Lokasi --</option>
                                <?php foreach($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['nama_lokasi'] ?? '') ?> (<?= htmlspecialchars($loc['kode_lokasi'] ?? '-') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-2">Subjek Laporan</label>
                        <input type="text" name="judul" class="form-control bg-light border-0 rounded-3 py-2" placeholder="Contoh: Pintu Gudang Terbuka" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-2">Kategori Temuan</label>
                        <select name="kategori" class="form-select bg-light border-0 rounded-3 py-2" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Gangguan">Gangguan</option>
                            <option value="Kebocoran">Kebocoran</option>
                            <option value="Kerusakan">Kerusakan</option>
                            <option value="Kecurangan">Kecurangan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-2">Deskripsi Laporan</label>
                        <textarea name="keterangan" class="form-control bg-light border-0 rounded-3 py-2" rows="3" placeholder="Jelaskan detail temuan Anda..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small mb-2">Foto Bukti (Wajib Ambil Langsung)</label>
                        
                        <div id="camera-box" class="patrol-camera-box bg-dark rounded overflow-hidden position-relative text-center d-none">
                            <video id="video" autoplay playsinline class="patrol-video"></video>
                            <button type="button" id="switchCam" class="btn btn-light btn-sm rounded-circle position-absolute top-0 end-0 m-2 shadow" title="Balik Kamera">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>

                        <div id="result-box" class="patrol-result-box text-center bg-light rounded border p-3 d-none">
                            <img id="photo-preview" src="" class="img-fluid rounded shadow-sm patrol-photo-preview">
                            <div class="mt-3 text-success fw-semibold d-none" id="photoPreviewLabel">Foto bukti siap dikirim. Pastikan jelas dan fokus.</div>
                        </div>

                        <input type="hidden" name="foto_base64" id="inputFoto">

                        <div class="mt-2 d-grid gap-2 d-md-flex justify-content-md-center patrol-action-group">
                            <button type="button" class="btn btn-primary btn-patroli rounded-pill px-4 shadow-sm" id="startCam">
                                <i class="fas fa-camera me-1"></i> Buka Kamera
                            </button>
                            <button type="button" class="btn btn-warning btn-patroli rounded-pill px-4 shadow-sm d-none" id="takeSnap">
                                <i class="fas fa-dot-circle me-1"></i> Ambil Foto
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-patroli rounded-pill px-4 shadow-sm d-none" id="retake">
                                <i class="fas fa-redo me-1"></i> Foto Ulang
                            </button>
                        </div>
                    </div>

                    <hr>

                    <button type="submit" id="submitReport" class="btn btn-danger btn-patroli-submit w-100 py-3 fw-bold shadow-sm" disabled>
                        <i class="fas fa-paper-plane me-2"></i> Kirim Laporan Patroli
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const video = document.getElementById('video');
    const canvas = document.createElement('canvas');
    const btnStart = document.getElementById('startCam');
    const btnSnap = document.getElementById('takeSnap');
    const btnRetake = document.getElementById('retake');
    const cameraBox = document.getElementById('camera-box');
    const resultBox = document.getElementById('result-box');
    const preview = document.getElementById('photo-preview');
    const photoPreviewLabel = document.getElementById('photoPreviewLabel');
    const inputFoto = document.getElementById('inputFoto');
    const btnSubmit = document.getElementById('submitReport');
    const inputWaktu = document.getElementById('inputWaktu');
    const inputKoordinat = document.getElementById('inputKoordinat');
    const btnSwitch = document.getElementById('switchCam');
    let stream = null;
    let facingMode = 'environment';
    let gpsPromise = null;
    let capturedAt = null;

    function stopStream() {
        if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    }

    async function openCamera() {
        stopStream();
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: facingMode } },
            audio: false
        });
        video.srcObject = stream;
        video.style.transform = facingMode === 'user' ? 'scaleX(-1)' : 'none';
    }

    function getPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) return reject(new Error('Browser tidak mendukung lokasi.'));
            navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        });
    }

    btnSwitch.addEventListener('click', async () => {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        try { await openCamera(); }
        catch (err) {
            facingMode = facingMode === 'environment' ? 'user' : 'environment';
            Swal.fire('Error', 'Gagal mengganti kamera: ' + err.message, 'error');
            try { await openCamera(); } catch (_) {}
        }
    });

    btnStart.addEventListener('click', async () => {
        try {
            if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                Swal.fire('Kamera Tidak Bisa Diakses', 'Akses kamera hanya diizinkan browser lewat koneksi HTTPS. Silakan buka halaman ini melalui alamat HTTPS.', 'error');
                return;
            }
            await openCamera();
            cameraBox.classList.remove('d-none');
            resultBox.classList.add('d-none');
            btnStart.classList.add('d-none');
            btnSnap.classList.remove('d-none');
            btnRetake.classList.add('d-none');
        } catch (err) {
            Swal.fire('Error', 'Gagal akses kamera: ' + err.message, 'error');
        }
    });

    btnSnap.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const dataURL = canvas.toDataURL('image/jpeg', 0.8);
        capturedAt = new Date();
        inputWaktu.value = capturedAt.toISOString();
        gpsPromise = getPosition();
        gpsPromise.then(pos => {
            inputKoordinat.value = pos.coords.latitude + ',' + pos.coords.longitude;
        }).catch(() => { inputKoordinat.value = ''; });

        inputFoto.value = dataURL;
        preview.src = dataURL;
        stopStream();

        cameraBox.classList.add('d-none');
        resultBox.classList.remove('d-none');
        btnSnap.classList.add('d-none');
        btnRetake.classList.remove('d-none');
        if (photoPreviewLabel) photoPreviewLabel.classList.remove('d-none');
        if (btnSubmit) btnSubmit.disabled = false;
    });

    if (btnSubmit) {
        btnSubmit.disabled = true;
    }

    btnRetake.addEventListener('click', () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        inputFoto.value = '';
        inputWaktu.value = '';
        inputKoordinat.value = '';
        gpsPromise = null;
        preview.src = '';
        cameraBox.classList.add('d-none');
        resultBox.classList.add('d-none');
        btnStart.classList.remove('d-none');
        btnSnap.classList.add('d-none');
        btnRetake.classList.add('d-none');
        if (photoPreviewLabel) photoPreviewLabel.classList.add('d-none');
        if (btnSubmit) btnSubmit.disabled = true;
    });

    document.getElementById('formPatroliLapor').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!inputFoto.value) {
            Swal.fire('Peringatan', 'Bukti foto wajib diambil!', 'warning');
            return;
        }

        Swal.fire({ title: 'Mengirim...', didOpen: () => Swal.showLoading() });
        const form = this;
        // Koordinat diambil saat jepret; jika belum selesai, ditunggu di sini.
        (gpsPromise || getPosition()).then(pos => {
            inputKoordinat.value = pos.coords.latitude + ',' + pos.coords.longitude;
            return fetch('proses_patroli.php', { method: 'POST', body: new FormData(form) });
        }).then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('Berhasil', data.message, 'success').then(() => window.location.href = 'index.php?page=patroli_scan');
            } else {
                Swal.fire('Gagal', data.message, 'error');
            }
        }).catch(err => {
            const gpsErr = err && typeof err.code === 'number';
            Swal.fire(gpsErr ? 'GPS Error' : 'Gagal', gpsErr ? 'Wajib aktifkan lokasi! (' + err.message + ')' : 'Terjadi kesalahan saat mengirim data.', 'error');
        });
    });
</script>