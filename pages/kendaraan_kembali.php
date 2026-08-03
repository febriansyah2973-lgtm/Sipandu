<link rel="stylesheet" href="assets/css/app.css">
<style>
    .avatar-circle { width: 40px; height: 40px; background-color: #e3f2fd; color: #0d6efd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
    .badge-soft-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .search-box { max-width: 250px; }
</style>

<?php
require_once 'config/database.php';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        echo "<script>Swal.fire({icon: 'success', title: 'Selesai', text: 'Kendaraan telah kembali & KM diperbarui.', timer: 2000, showConfirmButton: false});</script>";
    } elseif ($_GET['status'] == 'error') {
        echo "<script>Swal.fire('Gagal', '" . ($_GET['msg'] ?? 'Error') . "', 'error');</script>";
    }
}
$sql = "SELECT p.*, k.merk_model, k.no_polisi, u.nama_lengkap 
        FROM pinjam_kendaraan p
        JOIN kendaraan k ON p.kendaraan_id = k.id
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'Dipakai'
        ORDER BY p.id ASC";
$data_kembali = $pdo->query($sql)->fetchAll();
?>

<div class="animate-fade-up">
    <div class="mb-1">
        <h6 class="text-uppercase fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 1px;">PENGEMBALIAN</h6>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Validasi Kendaraan Kembali</h3>
            <p class="text-secondary mb-0 small">Input KM akhir setelah kendaraan selesai digunakan.</p>
        </div>
    </div>

    <div class="alert alert-success bg-success-subtle border-0 rounded-3 d-flex align-items-center mb-4">
        <div class="me-3 text-success">
            <i class="fas fa-check-circle fa-lg"></i>
        </div>
        <div class="text-success small fw-medium">
            <strong>Menunggu Pengembalian:</strong> Ada <span class="fw-bold"><?= count($data_kembali) ?></span> kendaraan yang sedang beroperasi di luar.
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
        <div class="card-header bg-white py-3 border-bottom-0">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="fw-bold mb-0 text-dark">Daftar Kendaraan Aktif</h6>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm search-box">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control bg-light border-0" placeholder="Cari driver...">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableKembali">
                <thead class="bg-light">
                    <tr class="text-secondary small text-uppercase fw-bold">
                        <th class="ps-4 py-3">Driver</th>
                        <th>Kendaraan</th>
                        <th>Waktu Keluar</th>
                        <th>KM Awal</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_kembali)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted small">Semua kendaraan sudah di kandang (Pool).</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_kembali as $row): 
                            $tgl = $row['tgl_pinjam'] ?? $row['tgl_keluar'] ?? $row['created_at'];
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3"><?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                        <small class="text-muted">Driver</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['merk_model']) ?></div>
                                <span class="badge bg-light text-secondary border rounded-pill small"><?= htmlspecialchars($row['no_polisi']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= date('H:i', strtotime($tgl)) ?> WIB</div>
                                <small class="text-muted"><?= date('d M Y', strtotime($tgl)) ?></small>
                            </td>
                            <td>
                                <span class="font-monospace text-secondary fw-bold">
                                    <?= number_format($row['km_awal'], 0, ',', '.') ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-success btn-sm rounded-3 px-3 fw-bold shadow-sm" 
                                        onclick="prosesKembali(
                                            '<?= $row['id'] ?>', 
                                            '<?= $row['kendaraan_id'] ?>', 
                                            '<?= $row['nama_lengkap'] ?>', 
                                            '<?= $row['merk_model'] ?>', 
                                            '<?= $row['km_awal'] ?>'
                                        )">
                                    <i class="fas fa-check me-1"></i> Proses Kembali
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKembali" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Input Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_kendaraan.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="kembali">
                    <input type="hidden" name="id_pinjam" id="k_id_pinjam">
                    <input type="hidden" name="kendaraan_id" id="k_kendaraan_id">
                    
                    <div class="bg-light p-3 rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary small">Driver:</span>
                            <span class="fw-bold text-dark" id="t_driver"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary small">Unit:</span>
                            <span class="fw-bold text-dark" id="t_mobil"></span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">KM Awal</label>
                            <input type="number" id="k_km_awal" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">KM Pulang (Akhir)</label>
                            <input type="number" name="km_akhir" id="k_km_akhir" class="form-control" required onkeyup="hitungJarak()">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                         <div class="form-text mt-0 small fw-bold text-end" id="info_jarak">Jarak Tempuh: 0 km</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark">Kondisi Kendaraan</label>
                        <select name="kondisi_akhir" class="form-select" required>
                            <option value="Baik">Baik / Normal</option>
                            <option value="Perlu Service">Perlu Service (Lapor Bengkel)</option>
                            <option value="Rusak">Rusak / Insiden</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-3 px-4 fw-bold shadow-sm">Simpan & Selesai</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prosesKembali(id, k_id, driver, mobil, km_awal) {
    document.getElementById('k_id_pinjam').value = id;
    document.getElementById('k_kendaraan_id').value = k_id;
    document.getElementById('t_driver').innerText = driver;
    document.getElementById('t_mobil').innerText = mobil;
    document.getElementById('k_km_awal').value = km_awal;
    document.getElementById('k_km_akhir').value = "";
    document.getElementById('k_km_akhir').min = km_awal;
    document.getElementById('info_jarak').innerText = "Jarak Tempuh: 0 km";

    var myModal = new bootstrap.Modal(document.getElementById('modalKembali'));
    myModal.show();
}

function hitungJarak() {
    var awal = parseInt(document.getElementById('k_km_awal').value) || 0;
    var akhir = parseInt(document.getElementById('k_km_akhir').value) || 0;
    
    if (akhir >= awal) {
        var jarak = akhir - awal;
        document.getElementById('info_jarak').innerHTML = `<span class="text-success fw-bold">Jarak Tempuh: ${jarak} km</span>`;
    } else {
        document.getElementById('info_jarak').innerHTML = `<span class="text-danger fw-bold">Error: KM Akhir harus > KM Awal</span>`;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    var searchInput = document.getElementById('searchInput');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            var value = this.value.toLowerCase();
            var rows = document.querySelectorAll('#tableKembali tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(value) > -1 ? '' : 'none';
            });
        });
    }
    var modal = document.getElementById('modalKembali');
    if(modal) document.body.appendChild(modal);
});
</script>