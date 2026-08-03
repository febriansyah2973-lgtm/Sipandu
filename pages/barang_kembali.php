<link rel="stylesheet" href="assets/css/app.css">
<style>
    .avatar-circle { width: 40px; height: 40px; background-color: #e3f2fd; color: #0d6efd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
    .badge-soft-warning { background-color: #fff3cd; color: #ffc107; border: 1px solid #ffecb5; }
    .search-box { max-width: 250px; }
</style>

<?php
require_once 'config/database.php';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        echo "<script>Swal.fire({icon: 'success', title: 'Selesai', text: 'Barang telah dikembalikan ke stok.', timer: 2000, showConfirmButton: false});</script>";
    } elseif ($_GET['status'] == 'error') {
        echo "<script>Swal.fire('Gagal', '" . ($_GET['msg'] ?? 'Error') . "', 'error');</script>";
    }
}
$sql = "SELECT p.*, b.kode_barang, b.nama_barang, u.nama_lengkap 
        FROM pinjam_barang p
        JOIN barang b ON p.barang_id = b.id
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'Dipinjam'
        ORDER BY p.tgl_pinjam ASC";
$data_kembali = $pdo->query($sql)->fetchAll();
?>

<div class="animate-fade-up">
    <div class="mb-1">
        <h6 class="text-uppercase fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 1px;">PENGEMBALIAN BARANG</h6>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Validasi Barang Kembali</h3>
            <p class="text-secondary mb-0 small">Validasi pengembalian barang operasional oleh personil.</p>
        </div>
    </div>

    <div class="alert alert-success bg-success-subtle border-0 rounded-3 d-flex align-items-center mb-4">
        <div class="me-3 text-success">
            <i class="fas fa-check-circle fa-lg"></i>
        </div>
        <div class="text-success small fw-medium">
            <strong>Menunggu Pengembalian:</strong> Ada <span class="fw-bold"><?= count($data_kembali) ?></span> item barang yang sedang dipinjam personil.
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-5">
        <div class="card-header bg-white py-3 border-bottom-0">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="fw-bold mb-0 text-dark">Daftar Barang Dipinjam</h6>
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
            <table class="table table-hover align-middle mb-0" id="tableKembali">
                <thead class="bg-light">
                    <tr class="text-secondary small text-uppercase fw-bold">
                        <th class="ps-4 py-3">Peminjam</th>
                        <th>Produk & Kode</th>
                        <th class="text-center">Jumlah</th>
                        <th>Waktu Pinjam</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_kembali)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted small">Tidak ada barang yang sedang dipinjam.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($data_kembali as $row): 
                            $tgl = strtotime($row['tgl_pinjam']);
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3"><?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                        <small class="text-muted">Personil</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']) ?></div>
                                <span class="badge bg-light text-secondary border rounded-pill small font-monospace"><?= $row['kode_barang'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-warning px-3 py-2 rounded-pill fw-bold text-dark">
                                    <?= $row['jumlah'] ?> Unit
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= date('H:i', $tgl) ?> WIB</div>
                                <small class="text-muted"><?= date('d M Y', $tgl) ?></small>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fw-bold shadow-sm" 
                                        onclick="konfirmasiKembali(
                                            '<?= $row['id'] ?>', 
                                            '<?= $row['barang_id'] ?>', 
                                            '<?= $row['jumlah'] ?>', 
                                            '<?= htmlspecialchars($row['nama_barang']) ?>'
                                        )">
                                    <i class="fas fa-undo me-1"></i> Proses Kembali
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
                <h5 class="modal-title fw-bold">Konfirmasi Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_pengembalian.php" method="POST">
                <div class="modal-body text-center p-4">
                    <input type="hidden" name="act" value="kembali">
                    <input type="hidden" name="id_pinjam" id="id_pinjam">
                    <input type="hidden" name="barang_id" id="barang_id">
                    <input type="hidden" name="jumlah" id="jumlah">
                    
                    <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-box-open fa-3x"></i>
                    </div>
                    
                    <h5 class="fw-bold mb-2">Barang Sudah Kembali?</h5>
                    <p class="text-secondary small mb-0">
                        Anda akan mengembalikan stok <br>
                        "<strong class="text-dark" id="text_nama"></strong>" sebanyak <strong class="text-dark" id="text_jumlah"></strong> unit
                        ke gudang.
                    </p>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0 justify-content-center">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-3 px-4 fw-bold shadow-sm">Ya, Kembalikan Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function konfirmasiKembali(id, b_id, jml, nama) {
    document.getElementById('id_pinjam').value = id;
    document.getElementById('barang_id').value = b_id;
    document.getElementById('jumlah').value = jml;
    document.getElementById('text_nama').innerText = nama;
    document.getElementById('text_jumlah').innerText = jml;
    
    var myModal = new bootstrap.Modal(document.getElementById('modalKembali'));
    myModal.show();
}

document.addEventListener("DOMContentLoaded", function() {
    var modal = document.getElementById('modalKembali');
    if(modal) document.body.appendChild(modal);
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
});
</script>