<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'config/csrf.php';
$csrf_token = csrf_token_value();

// 1. Proteksi Role
$roleUser = strtolower($uData['role'] ?? '');
if ($roleUser !== 'admin' && $roleUser !== 'administrator') {
    echo "<div class='alert alert-danger'>Akses Ditolak!</div>";
    exit;
}

// 2. Ambil Data
$stmt = $pdo->query("SELECT * FROM cabang ORDER BY nama_cabang ASC");
$cabangs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>window.APP_CSRF_TOKEN = <?= json_encode($csrf_token) ?>;</script>

<div class="container-fluid px-0 animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Master Data Cabang</h3>
            <p class="text-secondary small mb-0">Kelola lokasi penempatan personil secara terpusat.</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="btnTambahCabang">
            <i class="fas fa-plus-circle me-2"></i> TAMBAH CABANG
        </button>
    </div>

    <div class="card-master shadow-sm border-0 animate-fade-up">
        <div class="card-header-action d-flex flex-wrap gap-3 p-3 align-items-center">
            <div class="d-flex align-items-center gap-2 me-auto">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 fw-semibold small">
                    <?= count($cabangs) ?> Cabang
                </span>
            </div>
            <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Cari nama atau alamat cabang...">
            </div>
        </div>
        <div class="table-responsive">
                <table class="table-master">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 5%;">NO</th>
                            <th style="width: 14%;">KODE</th>
                            <th style="width: 22%;">NAMA CABANG</th>
                            <th>ALAMAT</th>
                            <th style="width: 16%;">KONTAK</th>
                            <th class="text-center" style="width: 12%;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($cabangs as $c): ?>
                        <tr>
                            <td class="ps-4 fw-bold small text-secondary"><?= $no++ ?></td>
                            <td><span class="badge bg-light text-secondary border rounded-pill small font-monospace"><?= htmlspecialchars($c['kode_cabang'] ?? ('CBG-' . str_pad($c['id'], 3, '0', STR_PAD_LEFT))) ?></span></td>
                            <td class="fw-bold text-uppercase"><?= htmlspecialchars($c['nama_cabang'] ?? '') ?></td>
                            <td class="small"><?= htmlspecialchars($c['alamat_cabang'] ?? '-') ?></td>
                            <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace"><?= htmlspecialchars($c['kontak_cabang'] ?? '-') ?></span></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 btn-actions">
                                    <button type="button" class="btn-action btn-outline-secondary btn-detail-cabang"
                                        title="Detail"
                                        data-kode="<?= htmlspecialchars($c['kode_cabang'] ?? ('CBG-' . str_pad($c['id'], 3, '0', STR_PAD_LEFT)), ENT_QUOTES, 'UTF-8') ?>"
                                        data-nama="<?= htmlspecialchars($c['nama_cabang'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-alamat="<?= htmlspecialchars($c['alamat_cabang'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-kontak="<?= htmlspecialchars($c['kontak_cabang'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <button type="button" class="btn-action btn-outline-primary btn-edit-cabang"
                                        title="Edit"
                                        data-id="<?= $c['id'] ?>"
                                        data-nama="<?= htmlspecialchars($c['nama_cabang'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-alamat="<?= htmlspecialchars($c['alamat_cabang'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-kontak="<?= htmlspecialchars($c['kontak_cabang'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button type="button" class="btn-action btn-outline-danger btn-delete-cabang"
                                        title="Hapus" 
                                        data-id="<?= $c['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahCabang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="proses_cabang.php" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i> Register Cabang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">NAMA CABANG / UNIT</label>
                    <input type="text" name="nama_cabang" class="form-control bg-light border-0 rounded-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">ALAMAT LENGKAP</label>
                    <textarea name="alamat_cabang" class="form-control bg-light border-0 rounded-3" rows="3"></textarea>
                </div>
                <div class="mb-0">
                    <label class="small fw-bold text-secondary mb-2">NO. TELEPON / KONTAK</label>
                    <input type="text" name="kontak_cabang" class="form-control bg-light border-0 rounded-3 py-2">
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="tambah" class="btn btn-primary w-100 rounded-pill fw-bold py-2">SIMPAN DATA CABANG</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditCabang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="proses_cabang.php" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="id" id="editCabangId">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold"><i class="fas fa-edit text-primary me-2"></i> Update Data Cabang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">NAMA CABANG</label>
                    <input type="text" name="nama_cabang" id="editCabangNama" class="form-control bg-light border-0 rounded-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">ALAMAT LENGKAP</label>
                    <textarea name="alamat_cabang" id="editCabangAlamat" class="form-control bg-light border-0 rounded-3" rows="3"></textarea>
                </div>
                <div class="mb-0">
                    <label class="small fw-bold text-secondary mb-2">KONTAK</label>
                    <input type="text" name="kontak_cabang" id="editCabangKontak" class="form-control bg-light border-0 rounded-3 py-2">
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="edit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">UPDATE DATA</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailCabang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold"><i class="fas fa-building text-info me-2"></i> Detail Cabang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="mx-auto bg-primary-subtle text-primary d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; border-radius: 20px;">
                        <i class="fas fa-map-marked-alt fa-3x"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0" id="detailCabangNama">-</h4>
                        <p class="text-secondary mb-0 small" id="detailCabangKode">-</p>
                </div>

                <div class="bg-light p-3 rounded-4">
                    <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.75rem;">KONTAK / TELEPON</small>
                    <span class="fw-bold text-primary"><i class="fas fa-phone-alt me-2"></i><span id="detailCabangKontak">-</span></span>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup Detail</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/master.js"></script>
