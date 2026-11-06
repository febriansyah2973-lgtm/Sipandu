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

		// Query SOS Aktif (Dikelompokkan agar broadcast tidak dobel)
        $sqlSOS = "SELECT n.message, MAX(n.created_at) AS created_at
                   FROM notifications n 
                   WHERE n.category IN ('alert', 'broadcast_alert') AND n.is_read = 0
                   GROUP BY n.message, UNIX_TIMESTAMP(n.created_at) DIV 60";
        $stmtSOS = $pdo->query($sqlSOS);
        $total_sos_unik = $stmtSOS->rowCount();

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
        <div class="row justify-content-center align-items-center min-vh-75">
            <div class="col-md-6 text-center">
                <div class="card border-0 shadow-lg p-5 rounded-4 locked-card">
                    <h2 class="fw-bold">Akses Terbatas</h2>
                    <p class="text-secondary">Silakan perbarui password default Anda untuk melihat statistik.</p>
                    <a href="index.php?page=ganti_password&status=force" class="btn btn-primary rounded-pill px-5 fw-bold shadow">GANTI PASSWORD SEKARANG</a>
                </div>
            </div>
        </div>

    <?php elseif ($role === 'admin' || $role === 'pengawas'): ?>
        <div class="dashboard-shell">
            <div class="dashboard-header">
                <div class="dashboard-top-title">
                    <h3>Command Center</h3>
                    <p>Overview keamanan & aktivitas personil dalam satu tampilan.</p>
                </div>
                <span class="status-chip">
                    <i class="fas fa-shield-alt"></i> SISTEM ONLINE
                </span>
            </div>

            <!-- ================= BANNER SOS ================= -->
            <?php if($total_sos_unik > 0): ?>
            <div class="dashboard-alert">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-2 text-danger"><i class="fas fa-exclamation-triangle me-2 animate-pulse"></i>NOTIFIKASI SOS AKTIF!</h5>
                        <span>Ada <strong><?= $total_sos_unik ?> personil</strong> membutuhkan bantuan segera.</span>
                    </div>
                    <a href="index.php?page=laporan_sos" class="btn btn-light text-danger fw-bold rounded-pill px-4">LIHAT LOKASI</a>
                </div>
            </div>
            <?php endif; ?>
            <!-- ================= END BANNER SOS ================= -->

            <div class="dashboard-metrics-grid">
                <article class="dashboard-metric-card">
                    <span class="dash-label">PERSONIL</span>
                    <div class="d-flex align-items-center justify-content-between gap-4">
                        <div>
                            <div class="dash-value"><?= $total_personil ?></div>
                            <div class="dash-note text-success fw-bold mt-2"><?= $count_hadir ?> Hadir</div>
                        </div>
                        <div class="icon-shape icon-primary"><i class="fas fa-users"></i></div>
                    </div>
                </article>
                <article class="dashboard-metric-card">
                    <span class="dash-label">PATROL COVERAGE</span>
                    <div class="d-flex align-items-center justify-content-between gap-4">
                        <div>
                            <div class="dash-value"><?= $coverage ?>%</div>
                            <div class="dash-note mt-2">
                                <div class="dashboard-progress">
                                    <div class="dashboard-progress-bar" style="width: <?= $coverage ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="icon-shape icon-success"><i class="fas fa-check-circle"></i></div>
                    </div>
                </article>
                <article class="dashboard-metric-card">
                    <span class="dash-label">TOTAL POS</span>
                    <div class="d-flex align-items-center justify-content-between gap-4">
                        <div>
                            <div class="dash-value"><?= $total_pos ?></div>
                            <div class="dash-note mt-2">Titik Terdaftar</div>
                        </div>
                        <div class="icon-shape icon-info"><i class="fas fa-map-marker-alt"></i></div>
                    </div>
                </article>
                <article class="dashboard-metric-card">
                    <span class="dash-label">TEMUAN</span>
                    <div class="d-flex align-items-center justify-content-between gap-4">
                        <div>
                            <div class="dash-value text-danger"><?= $insiden ?></div>
                            <div class="dash-note text-danger fw-bold mt-2">Butuh Tindakan</div>
                        </div>
                        <div class="icon-shape icon-danger"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </article>
            </div>

            <div class="dashboard-main-grid">
                <section class="dashboard-chart-card">
                    <div class="card-body p-4">
                        <h5 class="fw-bold">Tren Aktivitas Patroli (7 Hari)</h5>
                        <div class="chart-wrapper"><canvas id="patrolChart"></canvas></div>
                    </div>
                </section>
                <aside class="dashboard-log-card">
                    <div class="card-body p-0 overflow-hidden">
                        <div class="p-4 border-bottom"><h5 class="fw-bold mb-0">Log Patroli Terkini</h5></div>
                        <div class="list-group list-group-flush">
                            <?php
                            $recent = $pdo->query("SELECT p.*, u.nama_lengkap, c.nama_lokasi FROM patroli p 
                                                   JOIN users u ON p.user_id = u.id 
                                                   JOIN checkpoints c ON p.checkpoint_id = c.id
                                                   ORDER BY p.waktu_scan DESC LIMIT 5");
                            while($r = $recent->fetch()): ?>
                                <div class="list-group-item dashboard-log-item">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-bold small"><?= htmlspecialchars($r['nama_lengkap']) ?></div>
                                            <div class="text-muted small">Di: <?= $r['nama_lokasi'] ?></div>
                                        </div>
                                        <span class="badge-status <?= ($r['status_temuan']=='Aman') ? 'badge-status-success' : 'badge-status-danger' ?>">
                                            <?= $r['status_temuan'] ?>
                                        </span>
                                    </div>
                                    <div class="text-muted text-end mt-2 small"><?= date('H:i', strtotime($r['waktu_scan'])) ?> WIB</div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

    <?php else: ?>
        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">Selamat Bertugas, <?= explode(' ', $_SESSION['nama_lengkap'])[0] ?>!</h2>
            <p class="text-secondary small">Utamakan keselamatan diri dan kewaspadaan.</p>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card-dash bg-primary text-white dashboard-hero-card">
                    <div class="card-body">
                        <div class="hero-overlay"><i class="fas fa-qrcode"></i></div>
                        <h5 class="hero-title">Target Patroli</h5>
                        <p class="hero-subtitle mb-4">Selesaikan semua titik hari ini.</p>
                        <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">
                            <div>
                                <h1 class="fw-bold hero-count"><?= $my_scans_count ?></h1>
                                <div class="small text-white-75">Titik Selesai</div>
                            </div>
                            <div class="hero-action">
                                <a href="index.php?page=patroli_scan" class="btn btn-light rounded-pill px-4 fw-bold text-primary shadow-sm">
                                    <i class="fas fa-camera me-2"></i> SCAN QR SEKARANG
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card-dash p-4 bg-white border-0 shadow-sm rounded-4">
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
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: { 
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#ffffff',
                    bodyColor: '#93c5fd',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 12 }
                }
            },
            scales: { 
                y: { 
                    beginAtZero: true, 
                    grid: { color: '#f1f5f9' },
                    ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', size: 11 } }
                },
                x: { 
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', size: 11 } }
                }
            }
        }
    });
    <?php endif; ?>
</script>