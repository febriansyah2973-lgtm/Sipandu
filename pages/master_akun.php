<?php
// Proteksi Role
if ($role !== 'admin') {
    echo "<div class='alert alert-danger'>Akses Ditolak! Hanya Administrator yang boleh masuk sini.</div>";
    exit;
}

require_once 'config/database.php';

// Ambil Data User
$stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, nama_lengkap ASC");
$dataUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="assets/css/master.css">

<div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Manajemen Akun Pengguna ???</h3>
        <p class="text-secondary mb-0 small">Kelola hak akses dan status aktif personil SIPANDU.</p>
    </div>
    <button type="button" class="btn btn-primary px-4 rounded-pill shadow" id="btnTambahAkun">
        <i class="fas fa-user-plus me-1"></i> Tambah Akun
    </button>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tableAkun">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">No</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($dataUsers as $row): ?>
                <tr>
                    <td class="ps-4"><?= $no++ ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                        <small class="text-muted">ID: #<?= $row['id'] ?></small>
                    </td>
                    <td class="font-monospace text-primary"><?= htmlspecialchars($row['username']) ?></td>
                    <td>
                        <?php if(strtolower($row['role']) == 'admin'): ?>
                            <span class="badge bg-primary rounded-pill px-3">Administrator</span>
                        <?php elseif(strtolower($row['role']) == 'pengawas'): ?>
                            <span class="badge bg-warning text-dark rounded-pill px-3">Pengawas</span>
                        <?php else: ?>
                            <span class="badge bg-secondary rounded-pill px-3">Satpam</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['status_akun'] == 'Aktif'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-light border btn-detail-akun"
                                data-nama="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>"
                                data-role="<?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?>"
                                data-jabatan="<?= htmlspecialchars($row['jabatan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= htmlspecialchars($row['status_akun'], ENT_QUOTES, 'UTF-8') ?>"
                                data-email="<?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-hp="<?= htmlspecialchars($row['no_hp'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-foto="<?= htmlspecialchars($row['foto_profil'] ?? $row['foto'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                title="Detail Akun">
                                <i class="fas fa-eye text-info"></i>
                            </button>

                            <button type="button" class="btn btn-sm btn-light border btn-edit-akun"
                                data-id="<?= $row['id'] ?>"
                                data-nama="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>"
                                data-role="<?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= htmlspecialchars($row['status_akun'], ENT_QUOTES, 'UTF-8') ?>"
                                title="Edit Akun">
                                <i class="fas fa-edit text-primary"></i>
                            </button>
                            
                            <?php if($row['username'] != 'admin'): ?>
                            <button type="button" class="btn btn-sm btn-light border btn-delete-akun" data-id="<?= $row['id'] ?>" title="Hapus Akun">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalAkun" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalLabel">Form Akun</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_akun.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="form-id">
                    <input type="hidden" name="act" id="form-act" value="tambah">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NAMA LENGKAP</label>
                        <input type="text" name="nama" id="form-nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">USERNAME</label>
                        <input type="text" name="username" id="form-username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">PASSWORD</label>
                        <input type="password" name="password" id="form-password" class="form-control" placeholder="Isi jika ingin ganti">
                        <small class="text-muted" id="pass-note">*Kosongkan jika tidak ingin ganti password</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">ROLE</label>
                            <select name="role" id="form-role" class="form-select">
                                <option value="admin">Admin</option>
                                <option value="pengawas">Pengawas</option>
                                <option value="satpam">Satpam</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">STATUS</label>
                            <select name="status" id="form-status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailAkun" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fas fa-id-badge text-info me-2"></i> Detail Akun Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mx-auto mb-3" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <img id="detailAkunFoto" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                    <div id="detailAkunInitials" class="w-100 h-100 d-flex align-items-center justify-content-center bg-primary text-white display-4 fw-bold" style="display: none;"></div>
                </div>
                
                <h4 class="fw-bold text-dark mb-1" id="detailAkunNama">-</h4>
                <p class="text-primary mb-3 font-monospace small" id="detailAkunUsername">-</p>

                <div class="row text-start g-3 mt-2 bg-light p-3 rounded-4">
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">ROLE / HAK AKSES</small>
                        <span class="fw-bold text-dark" id="detailAkunRole">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">STATUS AKUN</small>
                        <span class="badge" id="detailAkunStatus">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">EMAIL</small>
                        <span class="fw-bold text-dark" id="detailAkunEmail">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block fw-bold" style="font-size: 0.75rem;">NO. HP / WHATSAPP</small>
                        <span class="fw-bold text-dark" id="detailAkunHp">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" class="btn btn-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/master.js"></script>