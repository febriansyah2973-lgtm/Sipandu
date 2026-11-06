<style>
    .avatar-circle { width: 40px; height: 40px; background-color: #e3f2fd; color: #0d6efd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
    .badge-soft-primary { background-color: #cff4fc; color: #055160; border: 1px solid #b6effb; }
    .search-box { max-width: 250px; }
</style>

<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
$csrf_token = csrf_token_value();

$data_pakai = []; 
$list_kendaraan = [];

try {
    $list_kendaraan = $pdo->query("SELECT * FROM kendaraan WHERE status IN ('Siap', 'Tersedia') ORDER BY merk_model ASC")->fetchAll(PDO::FETCH_ASSOC);
    $sql = "SELECT p.*, k.merk_model, k.no_polisi, u.nama_lengkap 
            FROM pinjam_kendaraan p
            JOIN kendaraan k ON p.kendaraan_id = k.id
            JOIN users u ON p.user_id = u.id
            WHERE p.status = 'Dipakai'
            ORDER BY p.id DESC";
    $data_pakai = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = $e->getMessage();
}
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        echo "<script>Swal.fire({icon: 'success', title: 'Berhasil', text: 'Kendaraan berhasil operasional.', timer: 2000, showConfirmButton: false});</script>";
    } elseif ($_GET['status'] == 'error') {
        $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : 'Gagal memproses data';
        echo "<script>Swal.fire('Gagal', '$msg', 'error');</script>";
    }
}
?>

<div class="animate-fade-up">
    <div class="mb-1">
        <h6 class="text-uppercase fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 1px;">KENDARAAN DINAS</h6>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Penggunaan Kendaraan</h3>
            <p class="text-secondary mb-0 small">Monitoring operasional kendaraan dinas oleh personil.</p>
        </div>
        <button class="btn btn-primary rounded-3 px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalPakai">
            <i class="fas fa-plus me-2"></i> Pakai Kendaraan
        </button>
    </div>

    <div class="alert alert-primary bg-primary-subtle border-0 rounded-3 d-flex align-items-center mb-4">
        <div class="me-3 text-primary">
            <i class="fas fa-info-circle fa-lg"></i>
        </div>
        <div class="text-primary small fw-medium">
            <strong>Status Kendaraan:</strong> Halaman ini menampilkan unit yang sedang <span class="badge bg-primary text-white border-0">Beroperasi</span> di lapangan.
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
        <div class="card-header bg-white py-3 border-bottom-0">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="fw-bold mb-0 text-dark">Unit Keluar <span class="badge bg-light text-primary ms-2"><?= count($data_pakai) ?> aktif</span></h6>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm search-box">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control bg-light border-0" placeholder="Cari driver/unit...">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablePakai">
                <thead class="bg-light">
                    <tr class="text-secondary small text-uppercase fw-bold">
                        <th class="ps-4 py-3">Driver</th>
                        <th>Kendaraan</th>
                        <th>KM Awal</th>
                        <th>Waktu Keluar</th>
                        <th>Keperluan</th>
                        <th class="text-end pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_pakai)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted small">Semua unit tersedia di kantor.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_pakai as $row): 
                            $tgl = isset($row['created_at']) ? strtotime($row['created_at']) : time();
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
                                <span class="badge bg-light text-secondary border rounded-pill small font-monospace"><?= $row['no_polisi'] ?></span>
                            </td>
                            <td>
                                <span class="font-monospace fw-bold text-secondary"><?= number_format($row['km_awal'] ?? 0) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= date('H:i', $tgl) ?> WIB</div>
                                <small class="text-muted"><?= date('d/m/Y', $tgl) ?></small>
                            </td>
                            <td>
                                <small class="text-secondary d-inline-block text-truncate" style="max-width: 150px;"><?= htmlspecialchars($row['keperluan']) ?></small>
                            </td>
                            <td class="text-end pe-4">
                                <span class="badge badge-soft-primary px-3 py-2 rounded-pill">
                                    <i class="fas fa-road me-1"></i> Operasional
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPakai" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Input Penggunaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="proses_kendaraan.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="pakai">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? '' ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Nama Driver</label>
                        <input type="text" class="form-control bg-light fw-bold text-dark border-0" 
                               value="<?= $_SESSION['nama_lengkap'] ?? 'User' ?>" 
                               readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Pilih Unit Kendaraan</label>
                        <select name="kendaraan_id" class="form-select border-1" required>
                            <option value="">-- Pilih Kendaraan Tersedia --</option>
                            <?php foreach ($list_kendaraan as $k): ?>
                                <option value="<?= $k['id'] ?>">
                                    <?= $k['merk_model'] ?> - [ <?= $k['no_polisi'] ?> ]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">KM Awal</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-tachometer-alt"></i></span>
                                <input type="number" name="km_awal" class="form-control border-1" required placeholder="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Waktu</label>
                            <input type="text" class="form-control bg-light border-0" value="<?= date('H:i') ?> WIB" readonly>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark">Tujuan / Keperluan</label>
                        <textarea name="keperluan" class="form-control" rows="3" placeholder="Contoh: Antar tamu ke Bandara..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm">Jalan Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var modal = document.getElementById('modalPakai');
        if(modal) document.body.appendChild(modal);
        var searchInput = document.getElementById('searchInput');
        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                var value = this.value.toLowerCase();
                var rows = document.querySelectorAll('#tablePakai tbody tr');
                rows.forEach(function(row) {
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(value) > -1 ? '' : 'none';
                });
            });
        }
    });
</script>
