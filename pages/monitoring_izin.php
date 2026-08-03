<?php
require_once 'config/database.php';

// NORMALISASI ROLE & AMBIL ID USER
$role_asli    = $_SESSION['role'] ?? ''; 
$role_check   = strtolower($role_asli); 
$id_user_aktif = $_SESSION['id_user'] ?? 0;

// LOGIKA QUERY BERDASARKAN ROLE (ISOLASI DATA)
if ($role_check == 'administrator' || $role_check == 'admin') {
    // 1. Admin: Melihat Semua (Prioritas yang butuh ACC Final di atas)
    $sql = "SELECT i.*, u.nama_lengkap, u.role 
            FROM pengajuan_izin i 
            JOIN users u ON i.user_id = u.id 
            ORDER BY 
                CASE 
                    WHEN i.status_approval = 'Disetujui Pengawas' THEN 1 
                    WHEN i.status_approval = 'Pending' THEN 2 
                    ELSE 3 
                END, i.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
} elseif ($role_check == 'pengawas') {
    // 2. Pengawas: Melihat pengajuan 'Pending' milik bawahan ATAU riwayat miliknya sendiri
    $sql = "SELECT i.*, u.nama_lengkap, u.role 
            FROM pengajuan_izin i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.status_approval = 'Pending' OR i.user_id = :id_user
            ORDER BY i.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_user' => $id_user_aktif]);
} else {
    // 3. Petugas Lapangan / Satpam: HANYA melihat data miliknya sendiri
    $sql = "SELECT i.*, u.nama_lengkap, u.role 
            FROM pengajuan_izin i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.user_id = :id_user
            ORDER BY i.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_user' => $id_user_aktif]);
}

$data_izin = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    echo "<script>Swal.fire({icon: 'success', title: 'Berhasil', text: 'Status pengajuan telah diperbarui!', timer: 2000, showConfirmButton: false});</script>";
}
?>

<div class="animate-fade-up px-2 mt-3">
    <div class="mb-4">
        <h5 class="text-secondary mb-1">Monitoring (Role: <?= htmlspecialchars($role_asli) ?>)</h5>
        <h3 class="fw-bold text-dark">
            <?= ($role_check == 'admin' || $role_check == 'administrator' || $role_check == 'pengawas') ? 'Approval Izin & Cuti' : 'Riwayat Izin & Cuti Saya' ?>
        </h3>
        <p class="text-muted small">
            <?= ($role_check == 'admin' || $role_check == 'administrator' || $role_check == 'pengawas') ? 'Alur Approval: Pengawas (Tahap 1) &rarr; Admin (Tahap 2/Final).' : 'Pantau status pengajuan izin dan cuti Anda di sini.' ?>
        </p>
    </div>

    <div class="card talenta-card overflow-hidden shadow-sm border-0 rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-4 py-3">Personil</th>
                        <th>Jenis & Alasan</th>
                        <th>Tanggal</th>
                        <th>Bukti</th>
                        <th class="pe-4 text-end">Status / Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_izin)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted small">Tidak ada data pengajuan izin/cuti.</td></tr>
                    <?php else: ?>
                        <?php foreach($data_izin as $row): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 fw-bold bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
                                        <?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?= htmlspecialchars($row['nama_lengkap']) ?>
                                            <?= ($row['user_id'] == $id_user_aktif) ? '<span class="text-primary ms-1" style="font-size:10px;">(SAYA)</span>' : '' ?>
                                        </div>
                                        <small class="text-muted text-uppercase" style="font-size: 11px;"><?= $row['role'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border mb-1"><?= $row['jenis_izin'] ?></span>
                                <div class="small text-muted text-truncate" style="max-width: 200px;"><?= htmlspecialchars($row['alasan']) ?></div>
                            </td>
                            <td>
                                <div class="small fw-bold text-dark"><?= date('d M Y', strtotime($row['tgl_mulai'])) ?></div>
                                <div class="small text-muted">s/d <?= date('d M Y', strtotime($row['tgl_selesai'])) ?></div>
                            </td>
                            <td>
                                <?php if($row['lampiran']): ?>
                                    <a href="assets/img/izin/<?= $row['lampiran'] ?>" target="_blank" class="btn btn-sm btn-light border text-primary" title="Lihat Lampiran">
                                        <i class="fas fa-paperclip"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small italic">No File</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-end">
                                
                                <?php if($row['status_approval'] == 'Pending'): ?>
                                    
                                    <?php if($role_check == 'pengawas' && $row['user_id'] != $id_user_aktif): ?>
                                        <div class="btn-group shadow-sm">
                                            <a href="proses_izin.php?act=acc_pengawas&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm px-3 fw-bold" onclick="return confirm('Validasi izin ini (Tahap 1)?')">
                                                <i class="fas fa-check me-1"></i> Validasi
                                            </a>
                                            <a href="proses_izin.php?act=reject&id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tolak izin ini?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2 border border-warning border-opacity-25">Menunggu Pengawas</span>
                                    <?php endif; ?>

                                <?php elseif($row['status_approval'] == 'Disetujui Pengawas'): ?>
                                    
                                    <?php if($role_check == 'admin' || $role_check == 'administrator'): ?>
                                        <div class="btn-group shadow-sm">
                                            <a href="proses_izin.php?act=acc_final&id=<?= $row['id'] ?>" class="btn btn-success btn-sm px-3 fw-bold" onclick="return confirm('Berikan persetujuan final (Tahap 2)?')">
                                                <i class="fas fa-check-double me-1"></i> Final ACC
                                            </a>
                                            <a href="proses_izin.php?act=reject&id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tolak izin ini?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-info-subtle text-info rounded-pill px-3 py-2 border border-info border-opacity-25">Menunggu Admin</span>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <?php 
                                        $badge = ($row['status_approval'] == 'Final Disetujui') ? 'bg-success-subtle text-success border-success' : 'bg-danger-subtle text-danger border-danger';
                                    ?>
                                    <span class="badge <?= $badge ?> rounded-pill px-3 py-2 border border-opacity-25">
                                        <?= $row['status_approval'] ?>
                                    </span>
                                <?php endif; ?>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>