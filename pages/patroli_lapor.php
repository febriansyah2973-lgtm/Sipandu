<link rel="stylesheet" href="assets/css/master.css">

<?php
require_once 'config/database.php';
$cp_id = $_GET['checkpoint_id'] ?? '';
$cp_nama = '';

if($cp_id) {
    $stmt = $pdo->prepare("SELECT nama_lokasi FROM checkpoints WHERE id = ?");
    $stmt->execute([$cp_id]);
    $row = $stmt->fetch();
    $cp_nama = $row['nama_lokasi'] ?? '';
}
$locations = $pdo->query("SELECT * FROM checkpoints ORDER BY nama_lokasi ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Patroli Lapor</h3>
        <p class="text-secondary mb-0 small">Laporkan kejadian atau kondisi janggal di lokasi.</p>
    </div>
    <a href="index.php?page=patroli_scan" class="btn btn-outline-secondary btn-sm rounded-pill">
        <i class="fas fa-arrow-left me-1"></i> Kembali Scan
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card-master shadow-sm">
            <div class="card-header-action bg-danger-subtle text-danger">
                <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Form Laporan Patroli</h6>
            </div>
            
            <form id="formPatroliLapor" enctype="multipart/form-data">
                <input type="hidden" name="act" value="patroli_lapor">
                <input type="hidden" name="koordinat" id="inputKoordinat">

                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Lokasi Kejadian</label>
                        <?php if($cp_id): ?>
                            <input type="hidden" name="checkpoint_id" value="<?= $cp_id ?>">
                            <input type="text" class="form-control bg-light" value="<?= $cp_nama ?>" readonly>
                        <?php else: ?>
                            <select name="checkpoint_id" class="form-select" required>
                                <option value="">-- Pilih Lokasi --</option>
                                <?php foreach($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= $loc['nama_lokasi'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Subjek Laporan</label>
                        <input type="text" name="judul" class="form-control" placeholder="Contoh: Pintu Gudang Terbuka" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Deskripsi Laporan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Jelaskan detail temuan Anda..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">Foto Bukti (Wajib Ambil Langsung)</label>
                        
                        <div id="camera-box" class="bg-dark rounded overflow-hidden position-relative text-center" style="height: 300px; display: none;">
                            <video id="video" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                        </div>

                        <div id="result-box" class="text-center bg-light rounded border p-3" style="display: none;">
                            <img id="photo-preview" src="" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                        </div>

                        <input type="hidden" name="foto_base64" id="inputFoto">

                        <div class="mt-2 d-grid gap-2 d-md-flex justify-content-md-center">
                            <button type="button" class="btn btn-primary rounded-pill px-4" id="startCam">
                                <i class="fas fa-camera me-1"></i> Buka Kamera
                            </button>
                            <button type="button" class="btn btn-warning text-white rounded-pill px-4 d-none" id="takeSnap">
                                <i class="fas fa-dot-circle me-1"></i> Ambil Foto
                            </button>
                            <button type="button" class="btn btn-secondary rounded-pill px-4 d-none" id="retake">
                                <i class="fas fa-redo me-1"></i> Foto Ulang
                            </button>
                        </div>
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-danger w-100 py-3 fw-bold shadow-sm">
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
    const inputFoto = document.getElementById('inputFoto');
    let stream = null;

    btnStart.addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            cameraBox.style.display = 'block';
            resultBox.style.display = 'none';
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
        
        inputFoto.value = dataURL;
        preview.src = dataURL;
        stream.getTracks().forEach(track => track.stop());

        cameraBox.style.display = 'none';
        resultBox.style.display = 'block';
        btnSnap.classList.add('d-none');
        btnRetake.classList.remove('d-none');
    });

    btnRetake.addEventListener('click', () => btnStart.click());
    document.getElementById('formPatroliLapor').addEventListener('submit', function(e) {
        e.preventDefault();
        if(!inputFoto.value) {
            Swal.fire('Peringatan', 'Bukti foto wajib diambil!', 'warning');
            return;
        }

        if (navigator.geolocation) {
            Swal.fire({ title: 'Mengirim...', didOpen: () => Swal.showLoading() });
            navigator.geolocation.getCurrentPosition((pos) => {
                document.getElementById('inputKoordinat').value = pos.coords.latitude + "," + pos.coords.longitude;
                const formData = new FormData(this);
                fetch('proses_patroli.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire('Berhasil', data.message, 'success').then(() => window.location.href = 'index.php?page=patroli_scan');
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                });
            }, () => Swal.fire('GPS Error', 'Wajib aktifkan lokasi!', 'error'));
        }
    });
</script>