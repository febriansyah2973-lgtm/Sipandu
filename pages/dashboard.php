<link rel="stylesheet" href="assets/css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
$role = strtolower($_SESSION['role']);
$uid  = $_SESSION['id_user'];
$today = date('Y-m-d');

// 1. CEK LOCK STATUS
$is_locked = (isset($_SESSION['perlu_ganti_pass']) && $_SESSION['perlu_ganti_pass'] == 1);

if (!$is_locked) {
    if ($role === 'admin' || $role === 'pengawas') {
        // --- LOGIKA DATA ADMIN ---
        $total_personil = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_pos = $pdo->query("SELECT COUNT(*) FROM checkpoints")->fetchColumn();
        
        $q_patroli = $pdo->prepare("SELECT COUNT(*) FROM patroli WHERE DATE(waktu_scan) = ?");
        $q_patroli->execute([$today]);
        $patroli_hari_ini = $q_patroli->fetchColumn();

        $insiden = $pdo->query("SELECT COUNT(*) FROM patroli WHERE status_temuan != 'Aman'")->fetchColumn();
        $coverage = ($total_pos > 0) ? round(($patroli_hari_ini / $total_pos) * 100) : 0;

        $hadir_today = $pdo->prepare("SELECT COUNT(*) FROM presensi WHERE tgl_presensi = ? AND status = 'Hadir'");
        $hadir_today->execute([$today]);
        $count_hadir = $hadir_today->fetchColumn();

        $sqlSOS = "SELECT n.*, u.nama_lengkap FROM notifications n 
                   JOIN users u ON n.sender_id = u.id 
                   WHERE n.category = 'alert' AND n.is_read = 0";
        $live_sos = $pdo->query($sqlSOS)->fetchAll();

        $chart_labels = []; $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chart_labels[] = date('d M', strtotime($date));
            $q = $pdo->prepare("SELECT COUNT(*) FROM patroli WHERE DATE(waktu_scan) = ?");
            $q->execute([$date]);
            $chart_data[] = $q->fetchColumn();
        }
    } else {
        // --- LOGIKA DATA PETUGAS ---
        $q_pres = $pdo->prepare("SELECT * FROM presensi WHERE id_user = ? AND tgl_presensi = ?");
        $q_pres->execute([$uid, $today]);
        $my_presensi = $q_pres->fetch();

        $q_my_scan = $pdo->prepare("SELECT COUNT(*) FROM patroli WHERE user_id = ? AND DATE(waktu_scan) = ?");
        $q_my_scan->execute([$uid, $today]);
        $my_scans_count = $q_my_scan->fetchColumn();
    }
}
?>

<div class="animate-fade-up">
    <?php if ($is_locked): ?>
        <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
            <div class="col-md-6 text-center">
                <div class="card border-0 shadow-lg p-5" style="border-radius: 30px;">
                    <div class="mb-4"><i class="fas fa-user-shield text-danger fa-5x"></i></div>
                    <h2 class="fw-bold">Akses Terbatas</h2>
                    <p class="text-secondary">Silakan perbarui password default Anda untuk melihat statistik.</p>
                    <a href="index.php?page=ganti_password&status=force" class="btn btn-primary rounded-pill px-5 fw-bold shadow">GANTI PASSWORD SEKARANG</a>
                </div>
            </div>
        </div>

    <?php elseif ($role === 'admin' || $role === 'pengawas'): ?>
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-1">Command Center</h3>
                <p class="text-secondary small mb-0">Overview Keamanan & Aktivitas Personil</p>
            </div>
            <div class="text-end">
                <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2">
                    <i class="fas fa-shield-alt me-1"></i> SISTEM ONLINE
                </span>
            </div>
        </div>

        <?php if(!empty($live_sos)): ?>
        <div class="alert bg-danger text-white border-0 shadow-lg rounded-4 p-4 mb-4 animate-pulse">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>NOTIFIKASI SOS AKTIF!</h5>
                    <span>Ada <b><?= count($live_sos) ?> personil</b> membutuhkan bantuan segera.</span>
                </div>
                <a href="index.php?page=laporan_sos" class="btn btn-light text-danger fw-bold rounded-pill px-4">LIHAT LOKASI</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card-dash p-4 bg-white border-0 shadow-sm h-100">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold">PERSONIL</span>
                            <h3 class="fw-bold mb-0 mt-1"><?= $total_personil ?></h3>
                            <small class="text-success fw-bold"><?= $count_hadir ?> Hadir</small>
                        </div>
                        <div class="icon-shape bg-primary-subtle text-primary rounded-3"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-dash p-4 bg-white border-0 shadow-sm h-100">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold">PATROL COVERAGE</span>
                            <h3 class="fw-bold mb-0 mt-1"><?= $coverage ?>%</h3>
                            <div class="progress mt-2" style="height: 4px; width: 60px;">
                                <div class="progress-bar bg-success" style="width: <?= $coverage ?>%"></div>
                            </div>
                        </div>
                        <div class="icon-shape bg-success-subtle text-success rounded-3"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-dash p-4 bg-white border-0 shadow-sm h-100">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold">TOTAL POS</span>
                            <h3 class="fw-bold mb-0 mt-1"><?= $total_pos ?></h3>
                            <small class="text-muted small">Titik Terdaftar</small>
                        </div>
                        <div class="icon-shape bg-info-subtle text-info rounded-3"><i class="fas fa-map-marker-alt"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-dash p-4 bg-white border-0 shadow-sm h-100">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold">TEMUAN</span>
                            <h3 class="fw-bold mb-0 mt-1 text-danger"><?= $insiden ?></h3>
                            <small class="text-danger fw-bold">Butuh Tindakan</small>
                        </div>
                        <div class="icon-shape bg-danger-subtle text-danger rounded-3"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-dash p-4 h-100 bg-white border-0 shadow-sm">
                    <h5 class="fw-bold mb-4">Tren Aktivitas Patroli (7 Hari)</h5>
                    <div style="height: 300px;"><canvas id="patrolChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-dash h-100 p-0 overflow-hidden bg-white border-0 shadow-sm">
                    <div class="p-4 border-bottom"><h5 class="fw-bold mb-0 small">Log Patroli Terkini</h5></div>
                    <div class="list-group list-group-flush">
                        <?php
                        $recent = $pdo->query("SELECT p.*, u.nama_lengkap, c.nama_lokasi FROM patroli p 
                                               JOIN users u ON p.user_id = u.id 
                                               JOIN checkpoints c ON p.checkpoint_id = c.id
                                               ORDER BY p.waktu_scan DESC LIMIT 5");
                        while($r = $recent->fetch()): ?>
                            <div class="list-group-item p-3 border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold small"><?= htmlspecialchars($r['nama_lengkap']) ?></div>
                                        <div class="text-muted" style="font-size: 10px;">Di: <?= $r['nama_lokasi'] ?></div>
                                    </div>
                                    <span class="badge <?= ($r['status_temuan']=='Aman') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill" style="font-size: 9px;">
                                        <?= $r['status_temuan'] ?>
                                    </span>
                                </div>
                                <div class="text-muted text-end mt-1" style="font-size: 9px;"><?= date('H:i', strtotime($r['waktu_scan'])) ?> WIB</div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">Selamat Bertugas, <?= explode(' ', $_SESSION['nama_lengkap'])[0] ?>!</h2>
            <p class="text-secondary small">Utamakan keselamatan diri dan kewaspadaan.</p>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card-dash p-4 bg-primary text-white border-0 shadow-sm overflow-hidden position-relative" style="border-radius: 20px;">
                    <div class="position-absolute end-0 top-0 p-4 opacity-25" style="font-size: 5rem; transform: rotate(15deg);">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h5 class="fw-bold mb-1">Target Patroli</h5>
                            <p class="small opacity-75 mb-3">Selesaikan semua titik hari ini.</p>
                            <h1 class="fw-bold mb-0" style="font-size: 3rem;"><?= $my_scans_count ?> <small class="fs-6 fw-normal opacity-75">Titik Selesai</small></h1>
                            <a href="index.php?page=patroli_scan" class="btn btn-light rounded-pill px-4 fw-bold text-primary shadow-sm mt-3">
                                <i class="fas fa-camera me-2"></i> SCAN QR SEKARANG
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card-dash p-4 bg-white border-0 shadow-sm" style="border-radius: 20px;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-id-card me-2 text-primary"></i>Presensi & Jam Kerja</h5>
                        <span class="badge rounded-pill <?= $my_presensi ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                            <?= $my_presensi ? 'Sudah Absen' : 'Belum Absen' ?>
                        </span>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="index.php?page=presensi_masuk" class="btn w-100 py-3 rounded-4 fw-bold shadow-sm <?= $my_presensi ? 'btn-light disabled border' : 'btn-primary' ?>">
                                <i class="fas fa-sign-in-alt d-block mb-1 fs-4"></i> MASUK
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="index.php?page=presensi_pulang" class="btn w-100 py-3 rounded-4 fw-bold shadow-sm <?= (!$my_presensi || $my_presensi['jam_pulang']) ? 'btn-light disabled border' : 'btn-success' ?>">
                                <i class="fas fa-sign-out-alt d-block mb-1 fs-4"></i> PULANG
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-4">
                <a href="index.php?page=presensi_izin" class="card border-0 shadow-sm rounded-4 p-3 text-center text-decoration-none text-dark d-block bg-white h-100">
                    <div class="bg-info-subtle text-info rounded-3 py-2 mb-2">
                        <i class="fas fa-envelope-open-text fa-lg"></i>
                    </div>
                    <div class="small fw-bold">Izin</div>
                </a>
            </div>
            <div class="col-4">
                <a href="index.php?page=presensi_lembur" class="card border-0 shadow-sm rounded-4 p-3 text-center text-decoration-none text-dark d-block bg-white h-100">
                    <div class="bg-warning-subtle text-warning rounded-3 py-2 mb-2">
                        <i class="fas fa-moon fa-lg"></i>
                    </div>
                    <div class="small fw-bold">Lembur</div>
                </a>
            </div>
            <div class="col-4">
                <a href="index.php?page=jadwal_patroli" class="card border-0 shadow-sm rounded-4 p-3 text-center text-decoration-none text-dark d-block bg-white h-100">
                    <div class="bg-secondary-subtle text-secondary rounded-3 py-2 mb-2">
                        <i class="fas fa-calendar-alt fa-lg"></i>
                    </div>
                    <div class="small fw-bold">Shift</div>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    <?php if ($role === 'admin' || $role === 'pengawas'): ?>
    const ctx = document.getElementById('patrolChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Titik Patroli',
                data: <?= json_encode($chart_data) ?>,
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true, tension: 0.4,
                pointRadius: 4, pointBackgroundColor: '#4e73df'
            }]
        },
        options: { 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { color: '#f8f9fa' } },
                x: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
</script>