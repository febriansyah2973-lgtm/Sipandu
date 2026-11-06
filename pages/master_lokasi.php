<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'config/csrf.php';
$csrf_token = csrf_token_value();

if ($role !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location='index.php?page=dashboard';</script>";
    exit;
}

require_once 'config/database.php';
$stmt = $pdo->query("SELECT cp.*, c.nama_cabang FROM checkpoints cp LEFT JOIN cabang c ON cp.cabang_id = c.id ORDER BY cp.id DESC");
$dataLokasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
$list_cabang = $pdo->query("SELECT id, nama_cabang FROM cabang ORDER BY nama_cabang ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<script>window.APP_CSRF_TOKEN = <?= json_encode($csrf_token) ?>;</script>

<div class="animate-fade-up">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Data Lokasi & QR</h3>
            <p class="text-secondary mb-0 small">Manajemen titik checkpoint dengan fitur Auto-GPS.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" id="btnTambahLokasi">
            <i class="fas fa-plus me-1"></i> Tambah Lokasi
        </button>
    </div>

    <div class="card-master shadow-sm border-0 animate-fade-up">
        <div class="card-header-action d-flex flex-wrap gap-3 p-3">
            <div class="d-flex align-items-center gap-2 me-auto">
                <div>
                    <h5 class="mb-0 fw-bold text-dark">Daftar Checkpoint</h5>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3 fw-semibold small">
                    <?= count($dataLokasi) ?> Titik
                </span>
            </div>
            <div class="input-group input-group-sm search-input-group" style="width: 250px;">
                <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control bg-light border-0 rounded-pill" id="searchInput" placeholder="Cari data...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-master w-100" id="dataTable">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Lokasi</th>
                        <th>Cabang</th>
                        <th>Koordinat</th>
                        <th>Kode QR</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($dataLokasi as $row): 
                        $qrUrl = "https://quickchart.io/qr?text=" . urlencode($row['kode_qr']) . "&size=200";
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-initial bg-success-subtle text-success me-3">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <span class="d-block fw-bold text-dark"><?= htmlspecialchars($row['nama_lokasi']) ?></span>
                                    <small class="text-muted small"><?= htmlspecialchars($row['keterangan'] ?: '-') ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2">
                                <?= htmlspecialchars($row['nama_cabang'] ?: 'Pusat') ?>
                            </span>
                        </td>
                        <td class="small font-monospace">
                            <div class="text-primary">Lat: <?= $row['latitude'] ?: '-' ?></div>
                            <div class="text-danger">Lon: <?= $row['longitude'] ?: '-' ?></div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= $qrUrl ?>" alt="QR" style="width: 40px; height: 40px;" class="border rounded shadow-sm">
                                <code class="small text-dark fw-bold"><?= htmlspecialchars($row['kode_qr']) ?></code>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1 btn-actions">
                                <a href="cetak_qr.php?id=<?= $row['id'] ?>" target="_blank" class="btn-action btn-outline-dark" title="Cetak QR">
                                    <i class="fas fa-print"></i>
                                </a>

                                <button type="button" class="btn-action btn-outline-info btn-detail-lokasi"
                                        title="Detail"
                                        data-nama="<?= htmlspecialchars($row['nama_lokasi'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-lat="<?= htmlspecialchars($row['latitude'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-long="<?= htmlspecialchars($row['longitude'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-qr="<?= htmlspecialchars($row['kode_qr'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-qrurl="<?= $qrUrl ?>"
                                        data-ket="<?= htmlspecialchars($row['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-cabang="<?= htmlspecialchars($row['nama_cabang'] ?: 'Pusat', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <button type="button" class="btn-action btn-outline-primary btn-edit-lokasi" 
                                        title="Edit"
                                        data-id="<?= $row['id'] ?>" 
                                        data-nama="<?= htmlspecialchars($row['nama_lokasi'], ENT_QUOTES, 'UTF-8') ?>" 
                                        data-cabang-id="<?= htmlspecialchars($row['cabang_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-lat="<?= htmlspecialchars($row['latitude'], ENT_QUOTES, 'UTF-8') ?>" 
                                        data-long="<?= htmlspecialchars($row['longitude'], ENT_QUOTES, 'UTF-8') ?>" 
                                        data-ket="<?= htmlspecialchars($row['keterangan'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-pen"></i>
                                </button>

                                <button type="button" class="btn-action btn-outline-danger btn-delete-lokasi" title="Hapus" data-id="<?= $row['id'] ?>">
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

<div class="modal fade" id="modalLokasi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="proses_master_lokasi.php" method="POST" id="formLokasi" class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="modal-header bg-light border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitleLokasi">Tambah Lokasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="inputIdLokasi">
                <input type="hidden" name="act" id="inputActLokasi" value="tambah">
                
                <div class="mb-3">
                    <label class="form-label fw-bold small">Nama Lokasi / Area</label>
                    <input type="text" name="nama_lokasi" id="inputNamaLokasi" class="form-control bg-light border-0 rounded-3 py-2" placeholder="Contoh: Ruang Server" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Cabang</label>
                    <select name="cabang_id" id="inputCabangLokasi" class="form-select bg-light border-0 rounded-3 py-2">
                        <option value="">Pusat / Tidak ada cabang</option>
                        <?php foreach ($list_cabang as $cab): ?>
                            <option value="<?= $cab['id'] ?>"><?= htmlspecialchars($cab['nama_cabang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 p-3 bg-light rounded-3 border border-dashed border-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold small mb-0">Titik Koordinat (Wajib)</label>
                        <button type="button" class="btn btn-success btn-sm rounded-pill shadow-sm fw-bold px-3" id="btnGetGPS">
                            <i class="fas fa-location-arrow me-1"></i> Dapatkan Lokasi
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" name="latitude" id="inputLatLokasi" class="form-control form-control-sm rounded-3 bg-white fw-bold text-primary" placeholder="Latitude" readonly required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="longitude" id="inputLongLokasi" class="form-control form-control-sm rounded-3 bg-white fw-bold text-danger" placeholder="Longitude" readonly required>
                        </div>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-bold small">Keterangan Tambahan</label>
                    <textarea name="keterangan" id="inputKetLokasi" class="form-control bg-light border-0 rounded-3" rows="2" placeholder="Detail lokasi..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-4 pt-0" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" class="btn btn-secondary px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalDetailLokasi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0 p-4">
                <h5 class="fw-bold"><i class="fas fa-map-marked-alt text-success me-2"></i> Detail Checkpoint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mx-auto mb-3 bg-white p-2 rounded-3 shadow-sm" style="width: 160px; height: 160px; border: 2px dashed #ddd;">
                    <img id="detailLokasiQrImage" src="" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                
                <h4 class="fw-bold text-dark mb-1" id="detailLokasiNama">-</h4>
                <div class="d-inline-block bg-dark text-white px-3 py-1 rounded-pill mb-3 shadow-sm">
                    <span class="font-monospace small fw-bold" id="detailLokasiQrCode">-</span>
                </div>

                <div class="row text-start g-3 mt-2 bg-light p-3 rounded-4">
                    <div class="col-6">
                        <small class="text-primary d-block fw-bold" style="font-size: 0.75rem;">LATITUDE</small>
                        <span class="fw-bold text-dark font-monospace" id="detailLokasiLat">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-danger d-block fw-bold" style="font-size: 0.75rem;">LONGITUDE</small>
                        <span class="fw-bold text-dark font-monospace" id="detailLokasiLong">-</span>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.75rem;">CABANG</small>
                        <span class="fw-bold text-dark small" id="detailLokasiCabang">Pusat</span>
                    </div>
                    <div class="col-12 mt-3 pt-3 border-top">
                        <small class="text-muted d-block fw-bold mb-1" style="font-size: 0.75rem;">KETERANGAN LOKASI</small>
                        <span class="text-dark small" id="detailLokasiKet">-</span>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="#" id="detailLokasiMapLink" target="_blank" class="btn btn-sm btn-outline-success w-100 rounded-pill fw-bold">
                        <i class="fas fa-external-link-alt me-1"></i> Buka di Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/master.js"></script>
