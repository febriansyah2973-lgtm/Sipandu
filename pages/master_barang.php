<link rel="stylesheet" href="assets/css/master.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
if ($role !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location='index.php?page=dashboard';</script>";
    exit;
}
?>
<?php
require_once 'config/database.php';
$query = $pdo->query("SELECT kode_barang FROM barang ORDER BY id DESC LIMIT 1");
$data  = $query->fetch();
$nextNo = $data ? (int)substr($data['kode_barang'], 4) + 1 : 1;
$nextKode = "BRG-" . str_pad($nextNo, 3, "0", STR_PAD_LEFT);
$sql = "SELECT b.*, 
        (SELECT IFNULL(SUM(p.jumlah), 0) FROM pinjam_barang p WHERE p.barang_id = b.id AND p.status = 'Dipinjam') AS jml_dipinjam
        FROM barang b 
        ORDER BY b.nama_barang ASC";
$stmt = $pdo->query($sql);
$dataBarang = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Data Barang & Aset</h3>
        <p class="text-secondary mb-0 small">Inventaris peralatan operasional keamanan.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" id="btnTambah" data-next-kode="<?= $nextKode ?>">
        <i class="fas fa-plus me-1"></i> Tambah Barang
    </button>
</div>

<div class="card-master">
    <div class="card-header-action d-flex flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 me-auto">
            <span class="badge bg-light text-secondary border rounded-pill px-2"><?= count($dataBarang) ?> Item</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-filter text-secondary small"></i>
            <select id="filterKondisi" class="form-select form-select-sm border-0 bg-light fw-bold text-secondary" style="width: 160px; cursor: pointer;">
                <option value="">Semua Kondisi</option>
                <option value="baik">Baik</option>
                <option value="dipinjam">Dipinjam</option>
                <option value="rusak">Rusak</option>
                <option value="hilang">Hilang</option>
            </select>
        </div>

        <div class="input-group input-group-sm" style="width: 250px;">
            <span class="input-group-text bg-light border-end-0 ps-3"><i class="fas fa-search text-secondary"></i></span>
            <input type="text" class="form-control bg-light border-start-0 ps-2" id="searchInput" placeholder="Cari nama atau kode...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-master" id="dataTable">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Produk & Kode</th>
                    <th>Rincian Stok (Kondisi)</th>
                    <th>Lokasi</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($dataBarang as $row): 
                    $total = $row['jml_baik'] + $row['jml_rusak'] + $row['jml_hilang'] + $row['jml_dipinjam'];
                ?>
                <tr>
                    <td><span class="text-secondary fw-medium ps-2"><?= $no++ ?></span></td>
                    
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-3 position-relative" style="width: 50px; height: 50px;">
                                <?php if(!empty($row['foto_barang'])): ?>
                                    <img src="assets/img/barang/<?= $row['foto_barang'] ?>" 
                                         class="rounded-3 border shadow-sm cursor-pointer zoom-trigger" 
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-3 bg-light border d-flex align-items-center justify-content-center text-secondary" style="width: 100%; height: 100%;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="d-block fw-bold text-dark filter-target"><?= htmlspecialchars($row['nama_barang']) ?></span>
                                <small class="text-secondary font-monospace"><?= htmlspecialchars($row['kode_barang']) ?></small>
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="mb-1">
                            <span class="fw-bold text-dark">Total: <?= $total ?> Unit</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap filter-kondisi-wrapper">
                            <?php if($row['jml_baik'] > 0): ?>
                            <span class="stok-badge bg-success-subtle text-success border border-success-subtle">
                                <i class="fas fa-check-circle"></i> <?= $row['jml_baik'] ?> Baik
                            </span>
                            <?php endif; ?>

                            <?php if($row['jml_dipinjam'] > 0): ?>
                            <span class="stok-badge bg-warning-subtle text-warning border border-warning-subtle">
                                <i class="fas fa-hand-holding"></i> <?= $row['jml_dipinjam'] ?> Dipinjam
                            </span>
                            <?php endif; ?>

                            <?php if($row['jml_rusak'] > 0): ?>
                            <span class="stok-badge bg-danger-subtle text-danger border border-danger-subtle">
                                <i class="fas fa-tools"></i> <?= $row['jml_rusak'] ?> Rusak
                            </span>
                            <?php endif; ?>

                            <?php if($row['jml_hilang'] > 0): ?>
                            <span class="stok-badge bg-dark-subtle text-dark border border-dark-subtle">
                                <i class="fas fa-search"></i> <?= $row['jml_hilang'] ?> Hilang
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="text-secondary small"><?= htmlspecialchars($row['lokasi']) ?></td>

                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn-action btn-detail-barang text-info" style="border:none; background:transparent;"
                                title="Detail"
                                data-kode="<?= htmlspecialchars($row['kode_barang'], ENT_QUOTES, 'UTF-8') ?>"
                                data-nama="<?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?>"
                                data-total="<?= $total ?>"
                                data-baik="<?= $row['jml_baik'] ?>"
                                data-dipinjam="<?= $row['jml_dipinjam'] ?>"
                                data-rusak="<?= $row['jml_rusak'] ?>"
                                data-hilang="<?= $row['jml_hilang'] ?>"
                                data-lokasi="<?= htmlspecialchars($row['lokasi'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-foto="<?= htmlspecialchars($row['foto_barang'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-eye"></i>
                            </button>

                            <button type="button" class="btn-action btn-edit text-primary" style="border:none; background:transparent;"
                                title="Edit"
                                data-id="<?= $row['id'] ?>"
                                data-kode="<?= htmlspecialchars($row['kode_barang'], ENT_QUOTES, 'UTF-8') ?>"
                                data-nama="<?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?>"
                                data-baik="<?= $row['jml_baik'] ?>"
                                data-rusak="<?= $row['jml_rusak'] ?>"
                                data-hilang="<?= $row['jml_hilang'] ?>"
                                data-lokasi="<?= htmlspecialchars($row['lokasi'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-pen"></i>
                            </button>

                            <button type="button" class="btn-action btn-delete text-danger" style="border:none; background:transparent;"
                                title="Hapus" 
                                data-id="<?= $row['id'] ?>">
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

<div class="modal fade" id="modalBarang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_barang.php" method="POST" enctype="multipart/form-data" id="formBarang">
                <div class="modal-body">
                    <input type="hidden" name="id" id="inputId">
                    <input type="hidden" name="act" id="inputAct" value="tambah">
                    
                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-bold">Kode</label>
                            <input type="text" name="kode_barang" id="inputKode" class="form-control bg-light fw-bold" readonly>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-bold">Nama Barang</label>
                            <input type="text" name="nama_barang" id="inputNama" class="form-control" required>
                        </div>
                    </div>

                    <label class="form-label small fw-bold mb-1">Rincian Stok Kondisi</label>
                    <div class="card bg-light border-0 p-3 mb-3">
                        <div class="row g-2">
                            <div class="col-4">
                                <label class="small text-success fw-bold">Baik</label>
                                <input type="number" name="jml_baik" id="inputBaik" class="form-control form-control-sm text-center fw-bold" min="0" value="0" required>
                            </div>
                            <div class="col-4">
                                <label class="small text-danger fw-bold">Rusak</label>
                                <input type="number" name="jml_rusak" id="inputRusak" class="form-control form-control-sm text-center fw-bold" min="0" value="0">
                            </div>
                            <div class="col-4">
                                <label class="small text-dark fw-bold">Hilang</label>
                                <input type="number" name="jml_hilang" id="inputHilang" class="form-control form-control-sm text-center fw-bold" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Lokasi Penyimpanan</label>
                        <input type="text" name="lokasi" id="inputLokasi" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Foto Barang</label>
                        <input type="file" name="foto" class="form-control">
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

<div class="modal fade" id="modalDetailBarang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fas fa-box-open text-info me-2"></i> Detail Barang & Aset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mx-auto mb-3" style="width: 140px; height: 140px; border-radius: 15px; overflow: hidden; border: 4px solid #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <img id="detailBarangFoto" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    <div id="detailBarangInitials" class="w-100 h-100 d-flex align-items-center justify-content-center bg-primary text-white display-1 fw-bold" style="display: none;"></div>
                </div>
                
                <h4 class="fw-bold text-dark mb-1" id="detailBarangNama">-</h4>
                <p class="text-primary mb-3 font-monospace small" id="detailBarangKode">-</p>

                <div class="row text-start g-3 mt-2 bg-light p-3 rounded-4">
                    <div class="col-12 text-center border-bottom pb-3 mb-1">
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.75rem;">TOTAL KESELURUHAN STOK</small>
                        <span class="fw-bold text-dark" style="font-size: 1.5rem;" id="detailBarangTotal">-</span> <span class="fw-bold text-secondary">Unit</span>
                    </div>
                    <div class="col-6">
                        <small class="text-success d-block fw-bold" style="font-size: 0.75rem;">JUMLAH BAIK</small>
                        <span class="fw-bold text-dark"><i class="fas fa-check-circle text-success me-1"></i> <span id="detailBarangBaik">-</span></span>
                    </div>
                    <div class="col-6">
                        <small class="text-warning d-block fw-bold" style="font-size: 0.75rem;">SEDANG DIPINJAM</small>
                        <span class="fw-bold text-dark"><i class="fas fa-hand-holding text-warning me-1"></i> <span id="detailBarangDipinjam">-</span></span>
                    </div>
                    <div class="col-6">
                        <small class="text-danger d-block fw-bold" style="font-size: 0.75rem;">KONDISI RUSAK</small>
                        <span class="fw-bold text-dark"><i class="fas fa-tools text-danger me-1"></i> <span id="detailBarangRusak">-</span></span>
                    </div>
                    <div class="col-6">
                        <small class="text-dark d-block fw-bold" style="font-size: 0.75rem;">BARANG HILANG</small>
                        <span class="fw-bold text-dark"><i class="fas fa-search text-dark me-1"></i> <span id="detailBarangHilang">-</span></span>
                    </div>
                    <div class="col-12 mt-3 pt-3 border-top">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">LOKASI PENYIMPANAN</small>
                        <span class="fw-bold text-dark"><i class="fas fa-map-marker-alt text-secondary me-1"></i> <span id="detailBarangLokasi">-</span></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup Detail</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/master.js"></script>