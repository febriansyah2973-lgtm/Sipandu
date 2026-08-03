<link rel="stylesheet" href="assets/css/master.css">
<?php
if ($role !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location='index.php?page=dashboard';</script>";
    exit;
}
require_once 'config/database.php';
$stmt = $pdo->query("SELECT * FROM shifts ORDER BY jam_masuk ASC");
$dataShift = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Data Shift Kerja</h3>
        <p class="text-secondary mb-0 small">Aturan jam kerja untuk pembagian jadwal patroli.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" id="btnTambahShift">
        <i class="fas fa-clock me-1"></i> Tambah Shift
    </button>
</div>

<div class="card-master">
    <div class="card-header-action">
        <div class="d-flex align-items-center gap-2 me-auto">
            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2 fw-bold"><?= count($dataShift) ?> Shift Terdaftar</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-master" id="dataTable">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Shift</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Durasi</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($dataShift as $row): 
                    $masuk  = strtotime($row['jam_masuk']);
                    $pulang = strtotime($row['jam_pulang']);
                    if ($pulang < $masuk) $pulang += 24 * 3600;
                    $durasi = ($pulang - $masuk) / 3600;
                    
                    $icon = 'sun'; $color = 'warning';
                    if (stripos($row['nama_shift'], 'malam') !== false) { $icon = 'moon'; $color = 'dark'; }
                    if (stripos($row['nama_shift'], 'siang') !== false) { $icon = 'cloud-sun'; $color = 'info'; }
                ?>
                <tr>
                    <td><span class="text-secondary fw-medium ps-2"><?= $no++ ?></span></td>
                    
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-initial bg-<?= $color ?>-subtle text-<?= $color ?> border border-<?= $color ?>-subtle" style="width: 40px; height: 40px; border-radius: 10px;">
                                <i class="fas fa-<?= $icon ?>"></i>
                            </div>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($row['nama_shift']) ?></span>
                        </div>
                    </td>

                    <td>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                            <i class="fas fa-sign-in-alt me-1"></i> <?= date('H:i', strtotime($row['jam_masuk'])) ?>
                        </span>
                    </td>

                    <td>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                            <i class="fas fa-sign-out-alt me-1"></i> <?= date('H:i', strtotime($row['jam_pulang'])) ?>
                        </span>
                    </td>

                    <td>
                        <span class="fw-bold text-secondary"><?= $durasi ?> Jam</span>
                    </td>

                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn-action btn-detail-shift text-info" style="border:none; background:transparent;"
                                title="Detail"
                                data-nama="<?= htmlspecialchars($row['nama_shift'], ENT_QUOTES, 'UTF-8') ?>"
                                data-masuk="<?= date('H:i', strtotime($row['jam_masuk'])) ?>"
                                data-pulang="<?= date('H:i', strtotime($row['jam_pulang'])) ?>"
                                data-durasi="<?= $durasi ?>"
                                data-icon="<?= $icon ?>"
                                data-color="<?= $color ?>">
                                <i class="fas fa-eye"></i>
                            </button>

                            <button type="button" class="btn-action btn-edit-shift text-primary" style="border:none; background:transparent;"
                                title="Edit"
                                data-id="<?= $row['id'] ?>"
                                data-nama="<?= htmlspecialchars($row['nama_shift'], ENT_QUOTES, 'UTF-8') ?>"
                                data-masuk="<?= $row['jam_masuk'] ?>"
                                data-pulang="<?= $row['jam_pulang'] ?>">
                                <i class="fas fa-pen"></i>
                            </button>
                            
                            <button type="button" class="btn-action btn-delete-shift text-danger" style="border:none; background:transparent;"
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

<div class="modal fade" id="modalShift" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0 p-4">
                <h5 class="modal-title fw-bold" id="modalTitleShift">Shift Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_shift.php" method="POST" id="formShift">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="inputIdShift">
                    <input type="hidden" name="act" id="inputActShift" value="tambah">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Shift</label>
                        <input type="text" name="nama_shift" id="inputNamaShift" class="form-control bg-light border-0 py-2" placeholder="Contoh: Shift Pagi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-success">Jam Masuk</label>
                        <input type="time" name="jam_masuk" id="inputMasukShift" class="form-control bg-light border-0 py-2 fw-bold" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-danger">Jam Pulang</label>
                        <input type="time" name="jam_pulang" id="inputPulangShift" class="form-control bg-light border-0 py-2 fw-bold" required>
                        <div class="form-text text-secondary small mt-2">* Jika lewat tengah malam, sistem otomatis menghitung sebagai hari esok.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light p-3" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailShift" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-body text-center p-4">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" id="detailShiftIconBox" style="width: 80px; height: 80px; border-radius: 50%; font-size: 2rem;">
                    <i id="detailShiftIcon" class="fas fa-clock"></i>
                </div>
                
                <h4 class="fw-bold text-dark mb-3" id="detailShiftNama">-</h4>

                <div class="bg-light rounded-4 p-3 text-start mb-3">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <span class="small fw-bold text-secondary">Masuk</span>
                        <span class="badge bg-success rounded-pill" id="detailShiftMasuk">-</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-secondary">Pulang</span>
                        <span class="badge bg-danger rounded-pill" id="detailShiftPulang">-</span>
                    </div>
                </div>

                <div class="bg-primary-subtle text-primary rounded-pill py-2 fw-bold">
                    <i class="fas fa-hourglass-half me-1"></i> Durasi: <span id="detailShiftDurasi">-</span> Jam
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script src="assets/js/master.js"></script>