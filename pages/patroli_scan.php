<link rel="stylesheet" href="assets/css/master.css">
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<?php
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');

// Pastikan ID User konsisten
if (!isset($_SESSION['user_id'])) { $_SESSION['user_id'] = 1; }
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// REVISI QUERY: Menggunakan pengecekan tanggal yang lebih fleksibel
$sql = "SELECT p.*, c.nama_lokasi, DATE_FORMAT(p.waktu_scan, '%H:%i') as jam 
        FROM patroli p 
        JOIN checkpoints c ON p.checkpoint_id = c.id 
        WHERE p.user_id = ? 
        AND (DATE(p.waktu_scan) = ? OR p.waktu_scan >= NOW() - INTERVAL 12 HOUR)
        ORDER BY p.waktu_scan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $today]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC); // Tambahkan FETCH_ASSOC
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Scan Patroli</h3>
        <p class="text-secondary mb-0 small">Petugas: <?= $_SESSION['nama_lengkap'] ?? 'Admin' ?> (ID: <?= $user_id ?>)</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card-master h-100 shadow-sm border-0">
            <div class="card-header-action d-flex justify-content-between align-items-center bg-light-subtle">
                <h6 class="mb-0 fw-bold"><i class="fas fa-qrcode me-2"></i>Area Scanner</h6>
                <span class="badge bg-primary-subtle text-primary border rounded-pill" id="status-badge">Mode Kamera</span>
            </div>
            
            <div class="p-4 text-center">
                <div id="reader-wrapper" class="scanner-wrapper position-relative">
                    <button type="button" id="btnSwitchCam" class="btn btn-sm btn-light border position-absolute top-0 end-0 m-2 z-3 rounded-circle" style="width:40px; height:40px;">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div id="reader" style="width: 100%; border-radius: 12px; overflow: hidden; border: 2px dashed #cbd5e1; background: #f8f9fa;"></div>
                </div>

                <div id="manual-input-wrapper" class="d-none mt-3">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control fw-bold" id="inputKodeLokasi" placeholder="LOC-XXX" style="text-transform: uppercase;">
                        <label>Kode Lokasi</label>
                    </div>
                    <button type="button" class="btn btn-primary w-100 py-3 fw-bold" id="btnSubmitManual">Kirim Kehadiran</button>
                </div>

                <hr class="my-4 opacity-25">
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-4" id="btnToggleMode">
                    <i class="fas fa-keyboard me-1"></i> Masalah Kamera? Input Manual
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card-master h-100 shadow-sm border-0">
            <div class="card-header-action bg-light-subtle">
                <h6 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i>Riwayat Hari Ini</h6>
            </div>
            <div class="p-0">
                <?php if(empty($riwayat)): ?>
                    <div class="text-center py-5 text-secondary opacity-50">
                        <i class="fas fa-clipboard-check fs-1 mb-3"></i>
                        <p class="small">Belum ada riwayat scan untuk hari ini.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($riwayat as $log): ?>
                        <div class="list-group-item px-4 py-3 border-bottom hover-bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($log['nama_lokasi']) ?></h6>
                                    <span class="badge <?= $log['status_temuan'] == 'Aman' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> border rounded-pill" style="font-size: 0.65rem;">
                                        <?= $log['status_temuan'] ?>
                                    </span>
                                </div>
                                <span class="badge bg-light text-dark border fw-bold font-monospace"><?= $log['jam'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/patroli_scan.js?v=<?= time() ?>"></script>