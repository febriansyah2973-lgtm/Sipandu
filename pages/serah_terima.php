<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
$uid_skrg = $_SESSION['id_user'] ?? 0;
$csrf_token = csrf_token_value();

// 1. QUERY BARANG (Status: Dipinjam)
$q_barang = "SELECT pb.*, b.nama_barang, u.nama_lengkap 
             FROM pinjam_barang pb 
             JOIN barang b ON pb.barang_id = b.id 
             JOIN users u ON pb.user_id = u.id 
             WHERE pb.status = 'Dipinjam'";
$list_barang = $pdo->query($q_barang)->fetchAll();

// 2. QUERY KENDARAAN (Status: Dipakai)
$q_mobil = "SELECT pk.*, k.merk_model, k.no_polisi, u.nama_lengkap 
            FROM pinjam_kendaraan pk 
            JOIN kendaraan k ON pk.kendaraan_id = k.id 
            JOIN users u ON pk.user_id = u.id 
            WHERE pk.status = 'Dipakai'";
$list_mobil = $pdo->query($q_mobil)->fetchAll();
?>

<div class="container-fluid animate-fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-file-signature text-primary me-2"></i>Serah Terima Shift (Digital BAST)</h3>
            <p class="text-secondary small mb-0">Integrasi aset kendaraan & barang inventaris secara real-time dengan verifikasi tanda tangan digital.</p>
        </div>
        <div>
            <button class="btn btn-sipandu btn-sipandu-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalHandover">
                <i class="fas fa-plus-circle me-1"></i> Buat Laporan Serah Terima
            </button>
        </div>
    </div>

    <div class="card card-sipandu p-4 p-md-5 text-center mb-4">
        <div class="mb-3">
            <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>
        <h4 class="fw-bold text-dark mb-2">Laporan Pergantian Shift & Handover Aset</h4>
        <p class="text-secondary max-w-400 mx-auto mb-4 small">
            Pastikan Anda memverifikasi seluruh aset inventaris yang sedang dipinjam sebelum menyerahkan tugas shift ke petugas penerima.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <button class="btn btn-sipandu btn-sipandu-primary px-5 py-3" data-bs-toggle="modal" data-bs-target="#modalHandover">
                <i class="fas fa-pen-nib me-2"></i> KISAHKAN & PROSES SERAH TERIMA
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHandover" tabindex="-1" aria-labelledby="labelModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="proses_serah_terima.php" method="POST" enctype="multipart/form-data" class="modal-content card-sipandu border-0 shadow-lg" onsubmit="saveSignatureData()">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="signature_data" id="signature_data">

            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="fw-bold text-dark mb-0" id="labelModal"><i class="fas fa-shield-alt text-primary me-2"></i>Laporan Digital Serah Terima</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 pt-3">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="small fw-bold text-secondary mb-2">Petugas Menyerahkan (Shift Keluar)</label>
                        <input type="text" class="form-control-sipandu bg-light" value="<?= htmlspecialchars($_SESSION['nama_lengkap']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-secondary mb-2">Petugas Penerima (Shift Masuk)</label>
                        <select name="user_id_masuk" class="form-select-sipandu" required>
                            <option value="">-- Pilih Petugas Penerima --</option>
                            <?php
                            $st_u = $pdo->query("SELECT id, nama_lengkap FROM users WHERE status='Aktif' AND id != $uid_skrg ORDER BY nama_lengkap ASC");
                            while($u = $st_u->fetch()) echo "<option value='{$u['id']}'>{$u['nama_lengkap']}</option>";
                            ?>
                        </select>
                    </div>
                </div>

                <div class="card card-sipandu mb-4 p-3 border">
                    <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-boxes me-2"></i>Daftar Peminjaman Aset Aktif</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-sipandu align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ASET INVENTARIS</th>
                                    <th>PEMEGANG SAAT INI</th>
                                    <th>WAKTU PINJAM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($list_barang as $b): ?>
                                <tr>
                                    <td><span class="badge badge-sipandu badge-sipandu-info me-1">BARANG</span> <strong><?= htmlspecialchars($b['nama_barang']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['nama_lengkap']) ?></td>
                                    <td><?= date('d/m H:i', strtotime($b['tgl_pinjam'])) ?> WIB</td>
                                </tr>
                                <?php endforeach; ?>

                                <?php foreach($list_mobil as $k): ?>
                                <tr>
                                    <td><span class="badge badge-sipandu badge-sipandu-warning me-1">KENDARAAN</span> <strong><?= htmlspecialchars($k['merk_model']) ?></strong> <small>(<?= htmlspecialchars($k['no_polisi']) ?>)</small></td>
                                    <td><?= htmlspecialchars($k['nama_lengkap']) ?></td>
                                    <td><?= date('d/m H:i', strtotime($k['tgl_ambil'])) ?> WIB</td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if(empty($list_barang) && empty($list_mobil)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted"><em>Tidak ada peminjaman aktif saat ini.</em></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">Catatan Situasi Terakhir & Instuksi Shift</label>
                    <textarea name="catatan" class="form-control-sipandu" rows="3" placeholder="Contoh: Situasi area kondusif, kuncil gerbang lengkap, CCTV CAM 02 perlu pengecekan..." required></textarea>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">Tanda Tangan Digital Verifikasi (Petugas)</label>
                    <div class="border rounded-4 bg-light p-2 position-relative">
                        <canvas id="sigCanvas" width="700" height="150" class="w-100 rounded-3 bg-white border cursor-crosshair"></canvas>
                        <button type="button" class="btn btn-sm btn-outline-secondary position-absolute bottom-0 end-0 m-3 rounded-pill px-3" onclick="clearSignature()">
                            <i class="fas fa-eraser me-1"></i> Bersihkan
                        </button>
                    </div>
                    <small class="text-muted fst-italic">Gunakan mouse / layar sentuh HP untuk membubuhkan tanda tangan.</small>
                </div>
            </div>

            <div class="modal-footer border-top-0 p-4 pt-0">
                <button type="submit" class="btn btn-sipandu btn-sipandu-primary w-100 py-3">
                    <i class="fas fa-check-circle me-2"></i> KIRIM LAPORAN SERAH TERIMA & TRANSAKSI ASET
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let sigCanvas, sigCtx, isDrawing = false;

document.addEventListener('DOMContentLoaded', function() {
    var mHandover = document.getElementById('modalHandover');
    if (mHandover) {
        document.body.appendChild(mHandover);
        mHandover.addEventListener('hidden.bs.modal', function () {
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.style.overflow = 'auto';
            document.body.classList.remove('modal-open');
        });
    }

    sigCanvas = document.getElementById('sigCanvas');
    if (sigCanvas) {
        sigCtx = sigCanvas.getContext('2d');
        sigCtx.strokeStyle = "#1d4ed8";
        sigCtx.lineWidth = 3;
        sigCtx.lineCap = "round";

        function getPos(e) {
            const rect = sigCanvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        function startDrawing(e) { isDrawing = true; const pos = getPos(e); sigCtx.beginPath(); sigCtx.moveTo(pos.x, pos.y); }
        function draw(e) { if (!isDrawing) return; const pos = getPos(e); sigCtx.lineTo(pos.x, pos.y); sigCtx.stroke(); }
        function stopDrawing() { isDrawing = false; }

        sigCanvas.addEventListener('mousedown', startDrawing);
        sigCanvas.addEventListener('mousemove', draw);
        sigCanvas.addEventListener('mouseup', stopDrawing);
        sigCanvas.addEventListener('mouseleave', stopDrawing);

        sigCanvas.addEventListener('touchstart', (e) => { e.preventDefault(); startDrawing(e); });
        sigCanvas.addEventListener('touchmove', (e) => { e.preventDefault(); draw(e); });
        sigCanvas.addEventListener('touchend', stopDrawing);
    }
});

function clearSignature() {
    if (sigCtx && sigCanvas) {
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
    }
}

function saveSignatureData() {
    if (sigCanvas) {
        const dataUrl = sigCanvas.toDataURL('image/png');
        document.getElementById('signature_data').value = dataUrl;
    }
}
</script>
