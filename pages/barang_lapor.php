<style>
    .avatar-circle { width: 40px; height: 40px; background-color: #fff1f2; color: #e11d48; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
    .badge-soft-danger { background-color: #fecdd3; color: #9f1239; border: 1px solid #fda4af; }
    .search-box { max-width: 250px; }
</style>

<?php
require_once 'config/database.php';
$all_barang = $pdo->query("SELECT id, nama_barang, kode_barang, jml_baik FROM barang ORDER BY nama_barang ASC")->fetchAll();
$sql = "SELECT bm.*, b.nama_barang, b.kode_barang, u.nama_lengkap 
        FROM barang_masalah bm
        JOIN barang b ON bm.barang_id = b.id
        JOIN users u ON bm.user_id = u.id
        ORDER BY bm.id DESC";
$data_lapor = $pdo->query($sql)->fetchAll();
?>

<div class="animate-fade-up">
    <div class="mb-1">
        <h6 class="text-uppercase fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 1px;">INVENTARIS</h6>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Kehilangan & Kerusakan</h3>
            <p class="text-secondary mb-0 small">Manajemen laporan barang inventaris yang rusak atau hilang.</p>
        </div>
        <button class="btn btn-danger rounded-3 px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalLaporBarang">
            <i class="fas fa-exclamation-triangle me-2"></i> Lapor Masalah Barang
        </button>
    </div>

    <div class="alert alert-danger bg-danger-subtle border-0 rounded-3 d-flex align-items-center mb-4">
        <div class="me-3 text-danger"><i class="fas fa-exclamation-circle fa-lg"></i></div>
        <div class="text-danger small fw-medium">
            <strong>Pusat Kendala:</strong> Daftar di bawah ini adalah barang yang sudah dikurangi dari stok operasional karena kendala teknis.
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom-0">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="fw-bold mb-0 text-dark">Data Laporan Barang Aktif</h6>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm search-box">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control bg-light border-0" placeholder="Cari barang...">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableLapor">
                <thead class="bg-light">
                    <tr class="text-secondary small text-uppercase fw-bold">
                        <th class="ps-4 py-3">Pelapor</th>
                        <th>Barang & Kode</th>
                        <th class="text-center">Jumlah</th>
                        <th>Kategori</th>
                        <th class="text-end pe-4">Waktu Lapor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_lapor)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted small">Belum ada laporan kehilangan atau kerusakan barang.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_lapor as $row): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3"><?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?></div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                <span class="badge bg-light text-secondary border rounded-pill small font-monospace"><?= $row['kode_barang'] ?></span>
                            </td>
                            <td class="text-center fw-bold"><?= $row['jumlah'] ?> Unit</td>
                            <td>
                                <span class="badge <?= $row['jenis_masalah'] == 'Hilang' ? 'bg-dark' : 'bg-danger' ?> rounded-pill px-3">
                                    <?= $row['jenis_masalah'] ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <small class="text-muted fw-bold"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLaporBarang" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="fas fa-bullhorn me-2"></i>Lapor Masalah Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_barang_lapor.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="lapor_masalah_barang">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Pilih Barang Bermasalah</label>
                        <select name="barang_id" class="form-select border-1" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($all_barang as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= $b['nama_barang'] ?> (Stok: <?= $b['jml_baik'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Jenis Kendala</label>
                            <select name="jenis_masalah" class="form-select border-1" required>
                                <option value="Rusak">Kerusakan</option>
                                <option value="Hilang">Kehilangan</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Jumlah Item</label>
                            <input type="number" name="jumlah" class="form-control" min="1" value="1" required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark">Kronologi / Detail Masalah</label>
                        <textarea name="detail_kejadian" class="form-control" rows="4" placeholder="Jelaskan kondisi barang atau kejadian hilangnya barang..." required></textarea>
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
        var modal = document.getElementById('modalLaporBarang');
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
