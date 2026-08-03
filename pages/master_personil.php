<link rel="stylesheet" href="assets/css/master.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
if ($role !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location='index.php?page=dashboard';</script>";
    exit;
}
require_once 'config/database.php';

$stmt = $pdo->query("SELECT u.*, c.nama_cabang, s.nama_shift 
                     FROM users u 
                     LEFT JOIN cabang c ON u.cabang_id = c.id 
                     LEFT JOIN shifts s ON u.shift_id = s.id 
                     ORDER BY u.role ASC, u.nama_lengkap ASC");
$dataPersonil = $stmt->fetchAll(PDO::FETCH_ASSOC);

$list_cabang = $pdo->query("SELECT id, nama_cabang FROM cabang ORDER BY nama_cabang ASC")->fetchAll(PDO::FETCH_ASSOC);
$list_shift = $pdo->query("SELECT id, nama_shift FROM shifts ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Data Personil</h3>
        <p class="text-secondary mb-0 small">Manajemen akun petugas keamanan dan administrator.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" id="btnTambahPersonil">
        <i class="fas fa-plus me-1"></i> Tambah Personil
    </button>
</div>

<div class="card-master shadow-sm border-0 animate-fade-up">
    <div class="card-header-action d-flex flex-wrap gap-3 p-3">
        <div class="d-flex align-items-center gap-2 me-auto">
            <span class="badge bg-light text-secondary border rounded-pill px-3 fw-normal small">
                <?= count($dataPersonil) ?> Total
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-master" id="dataTable">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th>PENGGUNA</th>
                    <th>USERNAME</th>
                    <th>JABATAN</th>
                    <th>PENEMPATAN</th>
                    <th>SHIFT</th>
                    <th>STATUS</th>
                    <th width="15%" class="text-center">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($dataPersonil as $row): 
                    $nama_foto = $row['foto_profil'] ?? $row['foto'] ?? '';
                    $fotoPath = (!empty($nama_foto) && $nama_foto !== 'default.png') ? 'uploads/profil/' . $nama_foto : '';
                    $cabangNama = $row['nama_cabang'] ?? 'Pusat';
                    $shiftNama = $row['nama_shift'] ?? 'Non-Shift';
                ?>
                <tr>
                    <td><span class="text-secondary fw-medium ps-2"><?= $no++ ?></span></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-initial me-3 bg-light border" style="width: 35px; height: 35px; overflow: hidden; border-radius: 8px;">
                                <?php if($fotoPath && file_exists($fotoPath)): ?>
                                    <img src="<?= $fotoPath ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-primary-subtle text-primary fw-bold small">
                                        <?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                        </div>
                    </td>
                    <td class="font-monospace text-primary small"><?= htmlspecialchars($row['username']) ?></td>
                    <td><span class="badge bg-light text-secondary border rounded-pill px-3 fw-normal filter-role"><?= ucfirst($row['role']) ?></span></td>
                    <td><span class="text-secondary small fw-medium"><i class="fas fa-building me-1 opacity-50"></i> <?= htmlspecialchars($cabangNama) ?></span></td>
                    <td><span class="text-secondary small fw-medium"><i class="fas fa-clock me-1 opacity-50"></i> <?= htmlspecialchars($shiftNama) ?></span></td>
                    <td>
                        <span class="badge <?= ($row['status'] == 'Aktif') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> border rounded-pill px-3">
                            <i class="fas fa-circle small me-1" style="font-size: 8px;"></i> <?= $row['status'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn-action btn-detail-personil text-info" style="border:none; background:transparent;"
                                data-nama="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>"
                                data-role="<?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>"
                                data-cabang="<?= htmlspecialchars($cabangNama, ENT_QUOTES, 'UTF-8') ?>"
                                data-shift="<?= htmlspecialchars($shiftNama, ENT_QUOTES, 'UTF-8') ?>"
                                data-foto="<?= htmlspecialchars($fotoPath, ENT_QUOTES, 'UTF-8') ?>"
                                title="Detail Personil">
                                <i class="fas fa-eye"></i>
                            </button>

                            <button type="button" class="btn-action btn-edit-personil text-primary" style="border:none; background:transparent;"
                                data-id="<?= $row['id'] ?>"
                                data-nama="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>"
                                data-role="<?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>"
                                data-cabang="<?= htmlspecialchars($row['cabang_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-shift="<?= htmlspecialchars($row['shift_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                title="Edit Data">
                                <i class="fas fa-pen"></i>
                            </button>

                            <button type="button" class="btn-action btn-mutasi-personil text-warning" style="border:none; background:transparent;"
                                data-id="<?= $row['id'] ?>"
                                data-nama="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                data-cabang_asal_id="<?= htmlspecialchars($row['cabang_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-cabang_asal_nama="<?= htmlspecialchars($cabangNama, ENT_QUOTES, 'UTF-8') ?>"
                                title="Mutasi Area">
                                <i class="fas fa-exchange-alt"></i>
                            </button>

                            <button type="button" class="btn-action btn-delete-personil text-danger" style="border:none; background:transparent;" data-id="<?= $row['id'] ?>" title="Hapus Data">
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

<div class="modal fade" id="modalPersonil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitlePersonil">Tambah Personil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_personil.php" method="POST" enctype="multipart/form-data" id="formPersonil">
                <div class="modal-body">
                    <input type="hidden" name="id" id="inputIdPersonil">
                    <input type="hidden" name="act" id="inputActPersonil" value="tambah">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="inputNamaPersonil" class="form-control rounded-3" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="username" id="inputUsernamePersonil" class="form-control rounded-3" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" id="inputPasswordPersonil" class="form-control rounded-3" placeholder="******">
                        <div id="passwordHelpPersonil" class="form-text text-muted d-none small">Kosongkan jika tidak ingin mengubah password.</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jabatan</label>
                            <select name="role" id="inputRolePersonil" class="form-select rounded-3">
                                <option value="admin">Administrator</option>
                                <option value="pengawas">Pengawas</option>
                                <option value="satpam">Satpam</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Status Akun</label>
                            <select name="status" id="inputStatusPersonil" class="form-select rounded-3">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Penempatan</label>
                            <select name="cabang_id" id="inputCabangPersonil" class="form-select rounded-3">
                                <option value="">-- Pusat/Default --</option>
                                <?php foreach($list_cabang as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_cabang']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jadwal Shift</label>
                            <select name="shift_id" id="inputShiftPersonil" class="form-select rounded-3">
                                <option value="">-- Non-Shift --</option>
                                <?php foreach($list_shift as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_shift']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Foto Profil</label>
                        <input type="file" name="foto" class="form-control rounded-3" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailPersonil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fas fa-id-badge text-info me-2"></i> Profil Personil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mx-auto mb-3" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <img id="detailPersonilFoto" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    <div id="detailPersonilInitials" class="w-100 h-100 d-flex align-items-center justify-content-center bg-primary text-white display-4 fw-bold" style="display: none;"></div>
                </div>
                
                <h4 class="fw-bold text-dark mb-1" id="detailPersonilNama">-</h4>
                <p class="text-primary mb-3 font-monospace small" id="detailPersonilUsername">-</p>

                <div class="row text-start g-3 mt-2 bg-light p-3 rounded-4">
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">JABATAN</small>
                        <span class="fw-bold text-dark" id="detailPersonilRole">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">STATUS</small>
                        <span class="badge" id="detailPersonilStatus">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">PENEMPATAN</small>
                        <span class="fw-bold text-dark"><i class="fas fa-building text-secondary me-1"></i> <span id="detailPersonilCabang">-</span></span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">JADWAL SHIFT</small>
                        <span class="fw-bold text-dark"><i class="fas fa-clock text-secondary me-1"></i> <span id="detailPersonilShift">-</span></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup Profil</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMutasiPersonil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold text-dark"><i class="fas fa-exchange-alt text-warning me-2"></i> Form Mutasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_personil.php" method="POST">
                <div class="modal-body p-4 pt-3">
                    <input type="hidden" name="act" value="mutasi">
                    <input type="hidden" name="id" id="mutasiIdUser">
                    <input type="hidden" name="cabang_asal_id" id="mutasiCabangAsalId">
                    
                    <div class="alert alert-warning border-warning border-dashed rounded-3 p-3 mb-4">
                        <div class="small text-dark mb-1">Nama Personil:</div>
                        <h6 class="fw-bold text-dark mb-2" id="mutasiNamaPersonil">-</h6>
                        <div class="small text-dark mb-1">Lokasi Saat Ini:</div>
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-map-marker-alt text-danger me-1"></i> <span id="mutasiCabangAsalNama">-</span></h6>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pindah Ke Cabang Tujuan <span class="text-danger">*</span></label>
                        <select name="cabang_tujuan_id" id="mutasiCabangTujuan" class="form-select bg-light border-0 py-2 rounded-3" required>
                            <option value="">-- Pilih Cabang --</option>
                            <?php foreach($list_cabang as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_cabang']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal Efektif Mutasi</label>
                        <input type="date" name="tanggal_efektif" class="form-control bg-light border-0 py-2 rounded-3" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold">Alasan Pindah</label>
                        <textarea name="keterangan" class="form-control bg-light border-0 rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold py-2 shadow-sm text-dark">PROSES MUTASI</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/master.js"></script>