<style>
    .avatar-circle { width: 40px; height: 40px; background-color: #e3f2fd; color: #0d6efd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
    .badge-soft-warning { background-color: #fff3cd; color: #ffc107; border: 1px solid #ffecb5; }
    .search-box { max-width: 250px; }
</style>

<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$csrf_token = csrf_token_value();
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        echo "<script>Swal.fire({icon: 'success', title: 'Berhasil', text: 'Peminjaman berhasil dicatat.', timer: 2000, showConfirmButton: false});</script>";
    } elseif ($_GET['status'] == 'error') {
        echo "<script>Swal.fire('Gagal', '" . ($_GET['msg'] ?? 'Terjadi kesalahan') . "', 'error');</script>";
    }
}
$list_barang = $pdo->query("SELECT id, kode_barang, nama_barang, jml_baik FROM barang WHERE jml_baik > 0 ORDER BY nama_barang ASC")->fetchAll();
$sql = "SELECT p.*, b.kode_barang, b.nama_barang, u.nama_lengkap 
        FROM pinjam_barang p
        JOIN barang b ON p.barang_id = b.id
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'Dipinjam'
        ORDER BY p.tgl_pinjam DESC";
$data_pinjam = $pdo->query($sql)->fetchAll();
?>

<div class="animate-fade-up">
    <div class="mb-1">
        <h6 class="text-uppercase fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 1px;">BARANG PINJAM</h6>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Transaksi Peminjaman</h3>
            <p class="text-secondary mb-0 small">Monitoring pengeluaran barang inventaris oleh personil.</p>
        </div>
        <button class="btn btn-primary rounded-3 px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalPinjam">
            <i class="fas fa-plus me-2"></i> Tambah Peminjaman
        </button>
    </div>

    <div class="alert alert-primary bg-primary-subtle border-0 rounded-3 d-flex align-items-center mb-4">
        <div class="me-3 text-primary">
            <i class="fas fa-info-circle fa-lg"></i>
        </div>
        <div class="text-primary small fw-medium">
            <strong>Status Peminjaman:</strong> Halaman ini hanya menampilkan barang yang <span class="badge bg-warning text-dark border-0">Sedang Dipinjam</span>.
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
        <div class="card-header bg-white py-3 border-bottom-0">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="fw-bold mb-0 text-dark">Daftar Barang Keluar <span class="badge bg-light text-primary ms-2"><?= count($data_pinjam) ?> aktif</span></h6>
                </div>
                <div class="col-auto">
                    <div class="input-group input-group-sm search-box">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control bg-light border-0" placeholder="Cari peminjam...">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablePinjam">
                <thead class="bg-light">
                    <tr class="text-secondary small text-uppercase fw-bold">
                        <th class="ps-4 py-3">Peminjam</th>
                        <th>Barang</th>
                        <th class="text-center">Jumlah</th>
                        <th>Waktu Pinjam</th>
                        <th>Keterangan</th>
                        <th class="text-end pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($data_pinjam) > 0): ?>
                        <?php foreach ($data_pinjam as $row): 
                            $tgl = strtotime($row['tgl_pinjam']);
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3"><?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                        <small class="text-muted">Petugas</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                <span class="badge bg-light text-secondary border rounded-pill small font-monospace"><?= $row['kode_barang'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-white text-dark border px-3 rounded-pill fw-bold"><?= $row['jumlah'] ?> Unit</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= date('H:i', $tgl) ?> WIB</div>
                                <small class="text-muted"><?= date('d/m/Y', $tgl) ?></small>
                            </td>
                            <td>
                                <small class="text-secondary"><?= htmlspecialchars($row['keterangan_pinjam']) ?></small>
                            </td>
                            <td class="text-end pe-4">
                                <span class="badge badge-soft-warning px-3 py-2 rounded-pill">
                                    <i class="fas fa-clock me-1"></i> Dipinjam
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted small">Belum ada data peminjaman.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPinjam" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Tambah Peminjaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="proses_peminjaman.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="act" value="pinjam">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Nama Peminjam</label>
                        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?? '' ?>">
                        <input type="text" class="form-control bg-light fw-bold text-dark border-0" 
                               value="<?= $_SESSION['nama_lengkap'] ?? 'User' ?>" 
                               readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">Pilih Barang</label>
                        <select name="barang_id" id="pilih_barang" class="form-select border-1" required onchange="cekStok()">
                            <option value="" data-stok="0">-- Pilih Barang --</option>
                            <?php foreach ($list_barang as $b): ?>
                                <option value="<?= $b['id'] ?>" data-stok="<?= $b['jml_baik'] ?>">
                                    <?= $b['nama_barang'] ?> (Stok: <?= $b['jml_baik'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Jumlah</label>
                            <input type="number" name="jumlah" id="jumlah_pinjam" class="form-control" value="1" min="1" required disabled>
                            <div id="stok_info" class="form-text mt-1 small fw-bold text-success"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-dark">Tanggal Keluar</label>
                            <input type="text" class="form-control bg-light border-0" value="<?= date('d M Y') ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-dark">Keperluan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Patroli malam..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitPinjam" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm" disabled>Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var modal = document.getElementById('modalPinjam');
    if(modal) document.body.appendChild(modal);

    var searchInput = document.getElementById('searchInput');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            var value = this.value.toLowerCase();
            var rows = document.querySelectorAll('#tablePinjam tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(value) > -1 ? '' : 'none';
            });
        });
    }
});
function cekStok() {
    var select = document.getElementById('pilih_barang');
    var selectedOption = select.options[select.selectedIndex];
    var stok = parseInt(selectedOption.getAttribute('data-stok')) || 0;
    
    var inputJml = document.getElementById('jumlah_pinjam');
    var msg = document.getElementById('stok_info');
    var btn = document.getElementById('btnSubmitPinjam');

    if (select.value === "" || stok <= 0) {
        inputJml.disabled = true;
        btn.disabled = true;
        msg.innerText = "";
        inputJml.value = 1;
    } else {
        inputJml.disabled = false;
        inputJml.max = stok;
        btn.disabled = false;
        msg.innerHTML = `<i class="fas fa-check-circle"></i> Sisa ${stok} Unit`;
    }
}
</script>
