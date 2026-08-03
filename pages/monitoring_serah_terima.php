<link rel="stylesheet" href="assets/css/dashboard.css">
<?php
require_once 'config/database.php';

// Proteksi: Admin, Pengawas, dan Petugas Lapangan
$role_admin = strtolower($_SESSION['role'] ?? '');
if (!in_array($role_admin, ['admin', 'pengawas', 'petugas', 'petugas lapangan', 'satpam'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Ambil Role dan ID User aktif
$id_user_aktif = $_SESSION['id_user'] ?? 0;
$is_admin = ($role_admin === 'admin' || $role_admin === 'administrator');

// Ambil parameter filter tanggal
$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

// Query SQL Dasar
$query_str = "SELECT st.*, u1.nama_lengkap AS petugas_keluar, u2.nama_lengkap AS petugas_masuk
              FROM serah_terima st
              JOIN users u1 ON st.user_id_keluar = u1.id
              JOIN users u2 ON st.user_id_masuk = u2.id";

$conditions = [];
$params = [];

// Logika Filter Tanggal
if ($tgl_mulai && $tgl_akhir) {
    $conditions[] = "DATE(st.waktu_serah_terima) BETWEEN :tgl_mulai AND :tgl_akhir";
    $params['tgl_mulai'] = $tgl_mulai;
    $params['tgl_akhir'] = $tgl_akhir;
}

// LOGIKA ISOLASI DATA (Kacamata Kuda)
// Jika bukan admin, pastikan ID-nya ada di kolom petugas_keluar ATAU petugas_masuk
if (!$is_admin) {
    $conditions[] = "(st.user_id_keluar = :id_user OR st.user_id_masuk = :id_user)";
    $params['id_user'] = $id_user_aktif;
}

// Gabungkan kondisi WHERE jika ada
if (count($conditions) > 0) {
    $query_str .= " WHERE " . implode(" AND ", $conditions);
}

$query_str .= " ORDER BY st.waktu_serah_terima DESC";

// Eksekusi Query dengan aman
$stmt = $pdo->prepare($query_str);
$stmt->execute($params);
$data_rekap = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid animate-fade-up">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <?= $is_admin ? 'Monitoring Serah Terima' : 'Riwayat Serah Terima Saya' ?>
            </h3>
            <p class="text-secondary small">
                <?= $is_admin ? 'Data riwayat pergantian shift personil security.' : 'Data riwayat pergantian shift yang melibatkan Anda.' ?>
            </p>
        </div>
        
        <?php if($is_admin): ?>
        <div class="dropdown">
            <button class="btn btn-primary rounded-pill px-4 dropdown-toggle fw-bold shadow-sm" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-file-export me-2"></i> Export Laporan
            </button>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                <li><a class="dropdown-item py-2" href="export_serah_terima.php?type=pdf&tgl_mulai=<?= $tgl_mulai ?>&tgl_akhir=<?= $tgl_akhir ?>" target="_blank"><i class="fas fa-file-pdf text-danger me-2"></i> Simpan ke PDF</a></li>
                <li><a class="dropdown-item py-2" href="export_serah_terima.php?type=excel&tgl_mulai=<?= $tgl_mulai ?>&tgl_akhir=<?= $tgl_akhir ?>"><i class="fas fa-file-excel text-success me-2"></i> Simpan ke Excel</a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="monitoring_serah_terima">
                <div class="col-md-4">
                    <label class="small fw-bold text-secondary mb-2">Dari Tanggal</label>
                    <input type="date" name="tgl_mulai" class="form-control border-0 bg-light rounded-3" value="<?= $tgl_mulai ?>">
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-secondary mb-2">Sampai Tanggal</label>
                    <input type="date" name="tgl_akhir" class="form-control border-0 bg-light rounded-3" value="<?= $tgl_akhir ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-dark rounded-pill px-4 w-100">Cari</button>
                    <a href="index.php?page=monitoring_serah_terima" class="btn btn-outline-secondary rounded-pill px-4 w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3">Waktu</th>
                        <th>Petugas Keluar</th>
                        <th>Petugas Masuk</th>
                        <th>Aset</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data_rekap)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted small italic">Tidak ada data ditemukan.</td></tr>
                    <?php else: foreach ($data_rekap as $r): ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold d-block"><?= date('d/m/Y', strtotime($r['waktu_serah_terima'])) ?></span>
                            <small class="text-muted"><?= date('H:i', strtotime($r['waktu_serah_terima'])) ?> WIB</small>
                        </td>
                        <td><span class="badge bg-danger-subtle text-danger rounded-pill px-3"><?= htmlspecialchars($r['petugas_keluar']) ?></span></td>
                        <td><span class="badge bg-success-subtle text-success rounded-pill px-3"><?= htmlspecialchars($r['petugas_masuk']) ?></span></td>
                        <td>
                            <?php if($r['status_inventaris'] == 'Lengkap'): ?>
                                <span class="text-success small fw-bold"><i class="fas fa-check-circle"></i> Oke</span>
                            <?php else: ?>
                                <span class="text-danger small fw-bold"><i class="fas fa-exclamation-triangle"></i> Cek</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-secondary"><?= htmlspecialchars($r['catatan_situasi']) ?></small></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>