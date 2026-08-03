<link rel="stylesheet" href="assets/css/master.css">
<?php
if ($role !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location='index.php?page=dashboard';</script>";
    exit;
}
require_once 'config/database.php';
$stmt = $pdo->query("SELECT * FROM kendaraan ORDER BY jenis ASC, no_polisi ASC");
$dataKendaraan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Data Kendaraan</h3>
        <p class="text-secondary mb-0 small">Manajemen armada operasional patroli.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" id="btnTambahKendaraan">
        <i class="fas fa-plus me-1"></i> Tambah Kendaraan
    </button>
</div>

<div class="card-master">
    <div class="card-header-action d-flex flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 me-auto">
            <span class="badge bg-light text-secondary border rounded-pill px-2"><?= count($dataKendaraan) ?> Unit</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-filter text-secondary small"></i>
            <select id="filterStatus" class="form-select form-select-sm border-0 bg-light fw-bold text-secondary" style="width: 150px; cursor: pointer;">
                <option value="">Semua Status</option>
                <option value="siap">Siap</option>
                <option value="servis">Servis</option>
                <option value="rusak">Rusak</option>
            </select>
        </div>

        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-light border-end-0 ps-3"><i class="fas fa-search text-secondary"></i></span>
            <input type="text" class="form-control bg-light border-start-0 ps-2" id="searchInput" placeholder="Cari nopol atau merk...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-master" id="dataTable">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Kendaraan</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($dataKendaraan as $row): ?>
                <tr>
                    <td><span class="text-secondary fw-medium ps-2"><?= $no++ ?></span></td>
                    
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-3 position-relative" style="width: 50px; height: 50px;">
                                <?php if(!empty($row['foto_kendaraan'])): ?>
                                    <img src="assets/img/kendaraan/<?= $row['foto_kendaraan'] ?>" 
                                         class="rounded-3 border shadow-sm cursor-pointer zoom-trigger" 
                                         style="width: 100%; height: 100%; object-fit: cover;"
                                         onclick="lihatFoto(this.src, '<?= htmlspecialchars($row['merk_model'], ENT_QUOTES, 'UTF-8') ?>')">
                                <?php else: ?>
                                    <div class="rounded-3 bg-light border d-flex align-items-center justify-content-center text-secondary" style="width: 100%; height: 100%;">
                                        <i class="fas fa-car-side"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="d-block fw-bold text-dark filter-target"><?= htmlspecialchars($row['merk_model']) ?></span>
                                <span class="badge bg-dark font-monospace text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">
                                    <?= htmlspecialchars($row['no_polisi']) ?>
                                </span>
                            </div>
                        </div>
                    </td>

                    <td>
                        <?php if($row['jenis'] == 'Motor'): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">
                                <i class="fas fa-motorcycle me-1"></i> Motor
                            </span>
                        <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">
                                <i class="fas fa-car me-1"></i> Mobil
                            </span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php 
                        $bg = 'success'; $icon = 'check-circle';
                        if($row['status'] == 'Servis') { $bg = 'warning'; $icon = 'tools'; }
                        if($row['status'] == 'Rusak') { $bg = 'danger'; $icon = 'times-circle'; }
                        ?>
                        <span class="badge bg-<?= $bg ?>-subtle text-<?= $bg ?> border border-<?= $bg ?>-subtle rounded-pill px-3">
                            <i class="fas fa-<?= $icon ?> me-1"></i> <?= $row['status'] ?>
                        </span>
                        <span class="d-none filter-status"><?= strtolower($row['status']) ?></span>
                    </td>

                    <td class="text-secondary small"><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>

                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn-action btn-detail-kendaraan text-info" style="border:none; background:transparent;"
                                title="Detail"
                                data-nopol="<?= htmlspecialchars($row['no_polisi'], ENT_QUOTES, 'UTF-8') ?>"
                                data-jenis="<?= htmlspecialchars($row['jenis'], ENT_QUOTES, 'UTF-8') ?>"
                                data-merk="<?= htmlspecialchars($row['merk_model'], ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ket="<?= htmlspecialchars($row['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-foto="<?= htmlspecialchars($row['foto_kendaraan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-eye"></i>
                            </button>

                            <button type="button" class="btn-action btn-edit-kendaraan text-primary" style="border:none; background:transparent;"
                                title="Edit"
                                data-id="<?= $row['id'] ?>"
                                data-nopol="<?= htmlspecialchars($row['no_polisi'], ENT_QUOTES, 'UTF-8') ?>"
                                data-jenis="<?= htmlspecialchars($row['jenis'], ENT_QUOTES, 'UTF-8') ?>"
                                data-merk="<?= htmlspecialchars($row['merk_model'], ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ket="<?= htmlspecialchars($row['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-pen"></i>
                            </button>

                            <button type="button" class="btn-action btn-delete-kendaraan text-danger" style="border:none; background:transparent;"
                                title="Hapus" data-id="<?= $row['id'] ?>">
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

<div class="modal fade" id="modalKendaraan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitleKendaraan">Tambah Kendaraan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_kendaraan.php" method="POST" enctype="multipart/form-data" id="formKendaraan">
                <div class="modal-body">
                    <input type="hidden" name="id" id="inputIdKendaraan">
                    <input type="hidden" name="act" id="inputActKendaraan" value="tambah">
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">No. Polisi</label>
                            <input type="text" name="no_polisi" id="inputNopolKendaraan" class="form-control text-uppercase fw-bold" placeholder="B 1234 XX" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jenis</label>
                            <select name="jenis" id="inputJenisKendaraan" class="form-select">
                                <option value="Motor">Motor</option>
                                <option value="Mobil">Mobil</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Merk & Model</label>
                        <input type="text" name="merk_model" id="inputMerkKendaraan" class="form-control" placeholder="Contoh: Honda Vario 150" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status Operasional</label>
                        <select name="status" id="inputStatusKendaraan" class="form-select">
                            <option value="Siap">Siap Operasi</option>
                            <option value="Servis">Sedang Servis</option>
                            <option value="Rusak">Rusak / Tidak Layak</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Keterangan Tambahan</label>
                        <textarea name="keterangan" id="inputKetKendaraan" class="form-control" rows="2" placeholder="Cth: Pajak mati bulan depan..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Foto Kendaraan</label>
                        <input type="file" name="foto" class="form-control">
                        <div class="form-text text-secondary">* Kosongkan jika tidak ada foto.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailKendaraan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fas fa-truck text-info me-2"></i> Detail Armada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mx-auto mb-3" style="width: 150px; height: 100px; border-radius: 10px; overflow: hidden; border: 3px solid #f8f9fa; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <img id="detailKendaraanFoto" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    <div id="detailKendaraanInitials" class="w-100 h-100 d-flex align-items-center justify-content-center bg-primary text-white display-4 fw-bold" style="display: none;"></div>
                </div>
                
                <h4 class="fw-bold text-dark mb-1" id="detailKendaraanMerk">-</h4>
                <div class="d-inline-block bg-dark text-white px-3 py-1 rounded-2 mb-3 border border-2 border-light shadow-sm">
                    <span class="font-monospace fw-bold fs-5" id="detailKendaraanNopol">-</span>
                </div>

                <div class="row text-start g-3 mt-2 bg-light p-3 rounded-4">
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">JENIS ARMADA</small>
                        <span class="fw-bold text-dark" id="detailKendaraanJenis">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">STATUS OPERASIONAL</small>
                        <span class="badge" id="detailKendaraanStatus">-</span>
                    </div>
                    <div class="col-12 mt-3 pt-3 border-top">
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.75rem;">KETERANGAN TAMBAHAN</small>
                        <span class="text-dark small" id="detailKendaraanKet">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup Detail</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <button type="button" class="btn-close btn-close-white btn-close-custom" data-bs-dismiss="modal" onclick="resetZoom()"></button>
            <div class="zoom-container" id="zoomContainer">
                <img id="imgPreview" src="" alt="Preview">
            </div>
            <div class="preview-controls">
                <button class="btn-control" onclick="adjustZoom(-0.2)"><i class="fas fa-minus"></i></button>
                <button class="btn-control" onclick="resetZoom()"><i class="fas fa-compress"></i></button>
                <button class="btn-control" onclick="adjustZoom(0.2)"><i class="fas fa-plus"></i></button>
            </div>
            <div style="position: absolute; top: 30px; left: 30px; z-index: 1055;">
                <span id="captionPreview" class="text-white fw-bold fs-4 text-shadow"></span>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script src="assets/js/master.js"></script>