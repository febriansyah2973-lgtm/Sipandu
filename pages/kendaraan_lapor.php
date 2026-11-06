<style>
    .avatar-circle { width: 40px; height: 40px; background-color: #fff1f2; color: #e11d48; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
    .search-box { max-width: 250px; }
</style>

<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
$csrf_token = csrf_token_value();

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        echo "<script>Swal.fire({icon: 'success', title: 'Laporan Terkirim', text: 'Status kendaraan telah diperbarui.', timer: 2000, showConfirmButton: false});</script>";
    } elseif ($_GET['status'] == 'error') {
        echo "<script>Swal.fire('Gagal', '" . ($_GET['msg'] ?? 'Error') . "', 'error');</script>";
    }
}
$all_kendaraan = $pdo->query("SELECT id, merk_model, no_polisi FROM kendaraan ORDER BY merk_model ASC")->fetchAll();
$sql = "SELECT k.* FROM kendaraan k WHERE k.status IN ('Rusak', 'Hilang', 'Service') ORDER BY k.id DESC";
$data_lapor = $pdo->query($sql)->fetchAll();
?>

<div class="animate-fade-up">
    <div class="mb-1">
        <h6 class="text-uppercase fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 1px;">KENDARAAN</h6>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Kehilangan & Kerusakan</h3>
            <p class="text-secondary mb-0 small">Manajemen data unit yang mengalami kendala teknis atau kehilangan.</p>
        </div>
        <button class="btn btn-danger rounded-3 px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalLapor">
            <i class="fas fa-exclamation-triangle me-2"></i> Lapor Masalah Kendaraan
        </button>
    </div>

    <div class="alert alert-danger bg-danger-subtle border-0 rounded-3 d-flex align-items-center mb-4">
        <div class="me-3 text-danger"><i class="fas fa-shield-alt fa-lg"></i></div>
        <div class="text-danger small fw-medium">
            <strong>Pusat Kendala:</strong> Daftar di bawah ini adalah unit yang sedang tidak dapat digunakan untuk operasional.
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom-0">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="fw-bold mb-0 text-dark">Data Laporan Aktif</h6>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm search-box">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control bg-light border-0" placeholder="Cari unit...">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableLapor">
                <thead class="bg-light">
                    <tr class="text-secondary small text-uppercase fw-bold">
                        <th class="ps-4 py-3">Unit</th>
                        <th>Plat Nomor</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_lapor)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted small">Belum ada laporan kerusakan atau kehilangan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_lapor as $row): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3"><i class="fas fa-tools"></i></div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['merk_model']) ?></div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border font-monospace"><?= $row['no_polisi'] ?></span></td>
                            <td>
                                <span class="badge <?= $row['status'] == 'Hilang' ? 'bg-dark' : 'bg-danger' ?> rounded-pill px-3">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td><small class="text-muted">Menunggu Investigasi</small></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-outline-secondary btn-sm rounded-3">Lihat Detail</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLapor" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Laporan Masalah Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_kendaraan.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="lapor_masalah">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Unit Kendaraan</label>
                        <select name="kendaraan_id" class="form-select border-1" required>
                            <option value="">-- Pilih Kendaraan --</option>
                            <?php foreach ($all_kendaraan as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= $k['merk_model'] ?> (<?= $k['no_polisi'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Kategori Kejadian</label>
                        <select name="jenis_masalah" class="form-select border-1" required>
                            <option value="Rusak">Kerusakan Berat / Mogok</option>
                            <option value="Hilang">Kehilangan Unit</option>
                            <option value="Service">Perlu Perbaikan (Service)</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark">Keterangan / Kronologi</label>
                        <textarea name="detail_kejadian" class="form-control" rows="4" placeholder="Jelaskan kondisi atau kejadian secara singkat..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4 fw-bold shadow-sm">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var modal = document.getElementById('modalLapor');
        if(modal) document.body.appendChild(modal);

        var searchInput = document.getElementById('searchInput');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                var value = this.value.toLowerCase();
                var rows = document.querySelectorAll('#tableLapor tbody tr');
                rows.forEach(function(row) {
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(value) > -1 ? '' : 'none';
                });
            });
        }
    });
</script>
