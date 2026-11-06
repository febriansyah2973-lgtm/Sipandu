<?php
// Menu Data Akun digabung ke Data Personil agar tidak duplikat data.
header('Location: index.php?page=master_personil');
exit;

// Ambil Data User (sumber sama dengan Master Personil, plus join cabang & shift)
$stmt = $pdo->query("SELECT u.*, c.nama_cabang, s.nama_shift
                     FROM users u
                     LEFT JOIN cabang c ON u.cabang_id = c.id
                     LEFT JOIN shifts s ON u.shift_id = s.id
                     ORDER BY u.role ASC, u.nama_lengkap ASC");
$dataUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$list_cabang = $pdo->query("SELECT id, nama_cabang FROM cabang ORDER BY nama_cabang ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<script>window.APP_CSRF_TOKEN = <?= json_encode($csrf_token) ?>;</script>
<div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Manajemen Akun Pengguna ???</h3>
        <p class="text-secondary mb-0 small">Kelola hak akses dan status aktif personil SIPANDU.</p>
    </div>
    <button type="button" class="btn btn-primary px-4 rounded-pill shadow" id="btnTambahAkun">
        <i class="fas fa-user-plus me-1"></i> Tambah Akun
    </button>
</div>

<div class="card-master shadow-sm border-0 animate-fade-up">
    <div class="card-header-action d-flex flex-wrap gap-3 p-3">
        <div class="d-flex align-items-center gap-2 me-auto">
            <span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3 fw-semibold small">
                <?= count($dataUsers) ?> Akun
            </span>
        </div>
        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
            <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control bg-light border-0 rounded-pill" id="searchInput" placeholder="Cari nama atau username...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-master" id="tableAkun">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">No</th>
                    <th>Kode</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Penempatan</th>
                    <th>Shift</th>
                    <th>Status</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($dataUsers as $row):
                    $cabangNama = $row['nama_cabang'] ?? 'Pusat';
                    $shiftNama = $row['nama_shift'] ?? 'Non-Shift';
                ?>
                <tr>
                    <td class="ps-4"><?= $no++ ?></td>
                    <td><span class="badge bg-light text-secondary border rounded-pill small font-monospace"><?= htmlspecialchars($row['kode_personil'] ?? ('USR-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT))) ?></span></td>
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
                    <td><span class="text-secondary small fw-medium"><i class="fas fa-building me-1 opacity-50"></i> <?= htmlspecialchars($cabangNama) ?></span></td>
                    <td><span class="text-secondary small fw-medium"><i class="fas fa-clock me-1 opacity-50"></i> <?= htmlspecialchars($shiftNama) ?></span></td>
                    <td>
                        <?php if($row['status_akun'] == 'Aktif'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-center gap-1 btn-actions">
                            <button type="button" class="btn-action btn-detail-akun text-info"
                                data-kode="<?= htmlspecialchars($row['kode_personil'] ?? ('USR-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT)), ENT_QUOTES, 'UTF-8') ?>"
                                data-nama="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>"
                                data-role="<?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?>"
                                data-jabatan="<?= htmlspecialchars($row['jabatan'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= htmlspecialchars($row['status_akun'], ENT_QUOTES, 'UTF-8') ?>"
                                data-email="<?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-hp="<?= htmlspecialchars($row['no_hp'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-foto="<?= htmlspecialchars($row['foto_profil'] ?? $row['foto'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                title="Detail Akun">
                                <i class="fas fa-eye"></i>
                            </button>

                            <button type="button" class="btn-action btn-edit-akun text-primary"
                                data-id="<?= $row['id'] ?>"
                                data-nama="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>"
                                data-role="<?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= htmlspecialchars($row['status_akun'], ENT_QUOTES, 'UTF-8') ?>"
                                title="Edit Akun">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <button type="button" class="btn-action btn-mutasi-personil text-warning"
                                data-id="<?= $row['id'] ?>"
                                data-nama="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                data-cabang_asal_id="<?= htmlspecialchars($row['cabang_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-cabang_asal_nama="<?= htmlspecialchars($cabangNama, ENT_QUOTES, 'UTF-8') ?>"
                                title="Mutasi Area">
                                <i class="fas fa-exchange-alt"></i>
                            </button>

                            <?php if($row['username'] != 'admin'): ?>
                            <button type="button" class="btn-action btn-delete-akun text-danger" data-id="<?= $row['id'] ?>" title="Hapus Akun">
                                <i class="fas fa-trash"></i>
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
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalLabel">Form Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_akun.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="id" id="form-id">
                    <input type="hidden" name="act" id="form-act" value="tambah">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NAMA LENGKAP</label>
                        <input type="text" name="nama" id="form-nama" class="form-control rounded-3 bg-light border-0 py-2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">USERNAME</label>
                        <input type="text" name="username" id="form-username" class="form-control rounded-3 bg-light border-0 py-2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">PASSWORD</label>
                        <input type="password" name="password" id="form-password" class="form-control rounded-3 bg-light border-0 py-2" placeholder="Isi jika ingin ganti">
                        <small class="text-muted" id="pass-note">*Kosongkan jika tidak ingin ganti password</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">ROLE</label>
                            <select name="role" id="form-role" class="form-select rounded-3 bg-light border-0 py-2">
                                <option value="admin">Admin</option>
                                <option value="pengawas">Pengawas</option>
                                <option value="satpam">Satpam</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">STATUS</label>
                            <select name="status" id="form-status" class="form-select rounded-3 bg-light border-0 py-2">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Simpan Data</button>
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
                <p class="text-primary mb-1 font-monospace small" id="detailAkunUsername">-</p>
                <p class="text-secondary mb-3 small" id="detailAkunKode">-</p>

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

<div class="modal fade" id="modalMutasiPersonil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold text-dark"><i class="fas fa-exchange-alt text-warning me-2"></i> Form Mutasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_master_personil.php" method="POST">
                <div class="modal-body p-4 pt-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="act" value="mutasi">
                    <input type="hidden" name="redirect_page" value="master_akun">
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
                        <label class="form-label small fw-bold">Keterangan (Opsional)</label>
                        <textarea name="keterangan" class="form-control bg-light border-0 py-2 rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white px-4 fw-semibold rounded-pill">Proses Mutasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/master.js"></script>
