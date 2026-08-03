<style>
    .bg-gradient-primary-soft { background: linear-gradient(135deg, #eef2f7 0%, #ffffff 100%); }
    .card-hover:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important; transition: all .2s; }
    .avatar-initial-md { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #fff; border-radius: 50%; background-color: #4e73df; }
    .date-nav-btn { background: #fff; border: 1px solid #e3e6f0; color: #5a5c69; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%; text-decoration: none; }
    .badge-ovt { background-color: #fff4e6; color: #d9480f; border: 1px solid #ffd8a8; font-size: 10px; }
    
    /* Tambahan Style Validasi User Pojok Kanan Atas */
    .validation-badge-top {
        position: fixed;
        top: 20px;
        right: 25px;
        z-index: 1060;
        background: #ffffff;
        padding: 8px 16px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border: 2px solid #28a745;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>

<?php
/**
 * MONITORING PRESENSI & LEMBUR
 * Fitur Isolasi Data: Hanya Admin yang bisa lihat semua data.
 */
require_once 'config/database.php';

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$search  = $_GET['q'] ?? '';
$prevDay = date('Y-m-d', strtotime($tanggal . ' -1 day'));
$nextDay = date('Y-m-d', strtotime($tanggal . ' +1 day'));
$isToday = ($tanggal == date('Y-m-d'));

// AMBIL ROLE DAN ID USER YANG LOGIN
$role_aktif = strtolower($_SESSION['role'] ?? '');
$id_user_aktif = $_SESSION['id_user'] ?? 0;

// CEK APAKAH DIA ADMIN / ADMINISTRATOR
$is_admin = ($role_aktif === 'admin' || $role_aktif === 'administrator');

try {
    // 1. Hitung Total Personil untuk Kotak Summary
    if ($is_admin) {
        // Admin melihat total semua personil
        $totalPersonil = $pdo->query("SELECT COUNT(*) FROM users WHERE role NOT IN ('Admin', 'Administrator')")->fetchColumn();
    } else {
        // Pengawas, Petugas, Satpam HANYA dihitung 1 (dirinya sendiri)
        $totalPersonil = 1;
    }
    
    // 2. QUERY UTAMA
    $sql = "SELECT p.*, u.nama_lengkap, u.id as user_id_display 
            FROM presensi p 
            JOIN users u ON p.id_user = u.id 
            WHERE p.tgl_presensi = :tgl AND u.nama_lengkap LIKE :q";
    
    $params = [
        'tgl' => $tanggal, 
        'q' => "%$search%"
    ];

    // 3. LOGIKA ISOLASI DATA (KUNCI UTAMA)
    // Jika BUKAN Admin, maka paksa query hanya mencari ID user miliknya sendiri
    if (!$is_admin) {
        $sql .= " AND p.id_user = :id_user_aktif";
        $params['id_user_aktif'] = $id_user_aktif;
    }

    $sql .= " ORDER BY p.jam_masuk DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hadir = 0; $telat = 0; $izin = 0; $lembur = 0;
    
    foreach($data as $r) {
        // Logika hitung ringkasan
        if(($r['status'] ?? 'Hadir') == 'Hadir') $hadir++;
        if(($r['status'] ?? '') == 'Terlambat') { $hadir++; $telat++; }
        if(in_array(($r['status'] ?? ''), ['Izin','Sakit'])) $izin++;
        if(isset($r['jam_lembur_mulai']) && $r['jam_lembur_mulai']) $lembur++;
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Penyakit Database: " . $e->getMessage() . "</div>";
    $data = [];
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 animate-fade-up">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <?= $is_admin ? 'Monitoring Presensi & Lembur' : 'Riwayat Presensi Saya' ?>
        </h4>
        <p class="text-secondary small mb-0">
            <?= $is_admin ? 'Rekapitulasi kehadiran seluruh personil PT TARGET.' : 'Data rekaman kehadiran Anda pada sistem.' ?>
        </p>
    </div>
    <div class="bg-white p-1 rounded-pill shadow-sm border d-flex align-items-center mt-3 mt-md-0">
        <a href="index.php?page=monitoring_presensi&tanggal=<?= $prevDay ?>" class="date-nav-btn mx-1"><i class="fas fa-chevron-left"></i></a>
        <input type="date" class="form-control border-0 bg-transparent text-center fw-bold" style="width: 150px;" value="<?= $tanggal ?>" onchange="window.location.href='index.php?page=monitoring_presensi&tanggal='+this.value">
        <a href="index.php?page=monitoring_presensi&tanggal=<?= $nextDay ?>" class="date-nav-btn mx-1 <?= $isToday ? 'disabled opacity-50' : '' ?>"><i class="fas fa-chevron-right"></i></a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card border-0 shadow-sm h-100 card-hover"><div class="card-body p-3 d-flex align-items-center gap-3"><div class="bg-success-subtle text-success rounded-3 p-3"><i class="fas fa-user-check fa-lg"></i></div><div><h4 class="mb-0 fw-bold"><?= $hadir ?></h4><small class="text-secondary">Hadir</small></div></div></div></div>
    <div class="col-md-3 col-6"><div class="card border-0 shadow-sm h-100 card-hover"><div class="card-body p-3 d-flex align-items-center gap-3"><div class="bg-warning-subtle text-warning rounded-3 p-3"><i class="fas fa-moon fa-lg"></i></div><div><h4 class="mb-0 fw-bold"><?= $lembur ?></h4><small class="text-secondary">Lembur</small></div></div></div></div>
    <div class="col-md-3 col-6"><div class="card border-0 shadow-sm h-100 card-hover"><div class="card-body p-3 d-flex align-items-center gap-3"><div class="bg-info-subtle text-info rounded-3 p-3"><i class="fas fa-file-medical fa-lg"></i></div><div><h4 class="mb-0 fw-bold"><?= $izin ?></h4><small class="text-secondary">Izin/Sakit</small></div></div></div></div>
    <div class="col-md-3 col-6"><div class="card border-0 shadow-sm h-100 card-hover"><div class="card-body p-3 d-flex align-items-center gap-3"><div class="bg-danger-subtle text-danger rounded-3 p-3"><i class="fas fa-user-times fa-lg"></i></div><div><h4 class="mb-0 fw-bold"><?= max(0, $totalPersonil - $hadir - $izin) ?></h4><small class="text-secondary">Belum Absen</small></div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Detail Log Presensi Harian</h6>
        
        <?php if($is_admin): ?>
        <form action="" method="GET" class="d-flex gap-2">
            <input type="hidden" name="page" value="monitoring_presensi">
            <input type="text" name="q" class="form-control form-control-sm bg-light border-0" placeholder="Cari personil..." value="<?= htmlspecialchars($search) ?>">
        </form>
        <?php endif; ?>
        
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-secondary small text-uppercase fw-bold">
                <tr>
                    <th class="ps-4">Personil</th>
                    <th>Presensi (In/Out)</th>
                    <th>Lembur (OVT)</th>
                    <th class="text-center">Bukti & Catatan</th>
                    <th class="pe-4 text-end">Status & Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Data presensi tidak ditemukan pada tanggal ini.</td></tr>
                <?php else: ?>
                    <?php foreach($data as $row): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-initial-md me-3"><?= substr($row['nama_lengkap'], 0, 1) ?></div>
                                <div><h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></h6><small class="text-muted">ID: PEG-<?= sprintf("%03d", $row['user_id_display']) ?></small></div>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-bold"><i class="fas fa-sign-in-alt text-success me-1"></i><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '--:--' ?></div>
                            <div class="small fw-bold"><i class="fas fa-sign-out-alt text-danger me-1"></i><?= $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '--:--' ?></div>
                        </td>
                        <td>
                            <?php if(isset($row['jam_lembur_mulai']) && $row['jam_lembur_mulai']): ?>
                                <div class="small"><span class="badge badge-ovt">OVT START: <?= date('H:i', strtotime($row['jam_lembur_mulai'])) ?></span></div>
                                <div class="small mt-1"><span class="badge badge-ovt">OVT END: <?= (isset($row['jam_lembur_selesai']) && $row['jam_lembur_selesai']) ? date('H:i', strtotime($row['jam_lembur_selesai'])) : '--:--' ?></span></div>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <?php if($row['lokasi_masuk']): ?>
                                    <button class="btn btn-light border text-danger" onclick="window.open('https://www.google.com/maps?q=<?= $row['lokasi_masuk'] ?>', '_blank')" title="Lokasi"><i class="fas fa-map-marker-alt"></i></button>
                                <?php endif; ?>
                                <?php if($row['foto']): ?>
                                    <button class="btn btn-light border text-primary" onclick="Swal.fire({imageUrl: 'assets/img/presensi/<?= $row['foto'] ?>', imageWidth: 400, title: 'Bukti Foto'})"><i class="fas fa-camera"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-light border text-secondary" onclick="Swal.fire({title: 'Catatan Personil', text: '<?= htmlspecialchars($row['keterangan'] ?? 'Tidak ada catatan') ?>', icon: 'info'})"><i class="fas fa-sticky-note"></i></button>
                            </div>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="mb-1">
                                <?php 
                                $status_val = $row['status'] ?? 'Hadir';
                                $b = match($status_val) { 'Hadir' => 'bg-success', 'Terlambat' => 'bg-warning', 'Izin', 'Sakit' => 'bg-info', default => 'bg-danger' }; 
                                ?>
                                <span class="badge <?= $b ?>-subtle text-<?= explode('-', $b)[1] ?> rounded-pill px-3"><?= $status_val ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>