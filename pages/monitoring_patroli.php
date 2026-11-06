<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .map-container { height: 600px; width: 100%; border-radius: 12px; z-index: 1; }
    .sos-marker-container { display: flex; justify-content: center; align-items: center; }
    .pulse-sos-map {
        width: 25px; height: 25px; background: #ff0000;
        border-radius: 50%; border: 3px solid white;
        box-shadow: 0 0 0 rgba(255, 0, 0, 0.4);
        animation: pulse-map 1.2s infinite;
    }
    @keyframes pulse-map {
        0% { box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7); }
        70% { box-shadow: 0 0 0 20px rgba(255, 0, 0, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 0, 0, 0); }
    }
</style>

<?php
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

// --- AMBIL ROLE & ID USER UNTUK ISOLASI DATA ---
$role_aktif = strtolower($_SESSION['role'] ?? '');
$id_user_aktif = $_SESSION['id_user'] ?? 0;
$is_admin = ($role_aktif === 'admin' || $role_aktif === 'administrator');

// --- 1. PROSES SIMPAN PENANGANAN ---
if (isset($_POST['simpan_penanganan'])) {
    $id_patroli = $_POST['id_patroli'];
    $catatan_penanganan = $_POST['keterangan_penanganan'];
    $sqlUpd = "UPDATE patroli SET status_penanganan = 'Selesai', keterangan = CONCAT(keterangan, ' | Penanganan: ', :catatan) WHERE id = :id";
    $stmtUpd = $pdo->prepare($sqlUpd);
    $saved = $stmtUpd->execute(['catatan' => $catatan_penanganan, 'id' => $id_patroli]);
    if($saved) echo "<script>alert('Berhasil!'); window.location.href='index.php?page=monitoring_patroli';</script>";
}

// --- 2. QUERY SOS (DENGAN ISOLASI DATA) ---
$sqlSOS = "SELECT n.*, u.nama_lengkap, u.foto 
           FROM notifications n 
           JOIN users u ON n.sender_id = u.id 
           WHERE n.id IN (
               SELECT MAX(id) 
               FROM notifications 
               WHERE category = 'alert' AND is_read = 0 
               GROUP BY sender_id
           )";

$paramsSOS = [];
// Jika BUKAN admin, hanya tampilkan SOS milik dirinya sendiri
if (!$is_admin) {
    $sqlSOS .= " AND n.sender_id = :id_user";
    $paramsSOS['id_user'] = $id_user_aktif;
}
$sqlSOS .= " ORDER BY n.created_at DESC";

$stmtSOS = $pdo->prepare($sqlSOS);
$stmtSOS->execute($paramsSOS);
$activeSOS = $stmtSOS->fetchAll(PDO::FETCH_ASSOC);

// --- 3. LOGIKA CHECKPOINT (DENGAN ISOLASI DATA) ---
$stmtCP = $pdo->query("SELECT id, nama_lokasi, latitude, longitude FROM checkpoints");
$allCheckpoints = $stmtCP->fetchAll(PDO::FETCH_ASSOC);

$sqlLog = "SELECT p.*, u.nama_lengkap 
           FROM patroli p 
           JOIN users u ON p.user_id = u.id 
           WHERE DATE(p.waktu_scan) = :hari_ini";

$paramsLog = ['hari_ini' => $today];
// Jika BUKAN admin, hanya ambil log patroli hasil scan dia sendiri
if (!$is_admin) {
    $sqlLog .= " AND p.user_id = :id_user";
    $paramsLog['id_user'] = $id_user_aktif;
}
$sqlLog .= " ORDER BY p.waktu_scan DESC";

$stmtLog = $pdo->prepare($sqlLog); 
$stmtLog->execute($paramsLog); 
$todayLogs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);

$mapData = []; $totalScanned = 0; $totalTemuan = 0;
foreach ($allCheckpoints as $cp) {
    $isScanned = false; $time = '-'; $off = '-'; $stat = '-'; $patId = 0;
    foreach ($todayLogs as $log) {
        if ($log['checkpoint_id'] == $cp['id']) {
            $isScanned = true; $patId = $log['id']; $time = date('H:i', strtotime($log['waktu_scan']));
            $off = $log['nama_lengkap']; $stat = $log['status_temuan']; $totalScanned++;
            if(stripos($stat, 'Aman') === false) $totalTemuan++;
            break;
        }
    }
    $mapData[] = ['id'=>$cp['id'], 'nama'=>$cp['nama_lokasi'], 'lat'=>$cp['latitude'], 'lng'=>$cp['longitude'], 'is_scanned'=>$isScanned, 'time'=>$time, 'officer'=>$off, 'status'=>$stat, 'patroli_id'=>$patId];
}
$totalTitik = count($allCheckpoints);
$persen = $totalTitik > 0 ? round(($totalScanned/$totalTitik)*100) : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <?= $is_admin ? 'Live Monitoring Patroli & SOS' : 'Live Tracking Patroli Saya' ?>
        </h3>
        <p class="text-secondary mb-0 small">
            <?= $is_admin ? 'Mendeteksi real-time pergerakan petugas dan sinyal bahaya.' : 'Pantau progress checkpoint patroli Anda hari ini.' ?>
        </p>
    </div>
    <div class="d-flex gap-4">
        <div class="text-center"><span class="d-block fw-bold text-primary fs-5"><?= $persen ?>%</span><small>Progress</small></div>
        <div class="border-start ps-3 text-center"><span class="d-block fw-bold text-success fs-5"><?= $totalScanned ?>/<?= $totalTitik ?></span><small>Tercover</small></div>
        <div class="border-start ps-3 text-center"><span class="d-block fw-bold text-danger fs-5"><?= $totalTemuan ?></span><small>Temuan</small></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="height: 600px; border-radius: 12px; overflow: hidden;">
            <div id="mapMonitoring" style="width: 100%; height: 100%;"></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <?php if(!empty($activeSOS)): ?>
            <div class="card-header bg-danger text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-bullhorn me-2"></i>SOS AKTIF!</h6>
            </div>
            <div class="p-0 overflow-auto" style="max-height: 250px; background: #fff5f5;">
                <?php foreach($activeSOS as $sos): ?>
                <div class="p-3 border-bottom border-danger border-opacity-25 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="text-danger d-block"><?= $sos['nama_lengkap'] ?></strong>
                        <small class="text-muted" style="font-size: 10px;"><?= date('H:i', strtotime($sos['created_at'])) ?> WIB</small>
                    </div>
                    <button class="btn btn-xs btn-danger rounded-pill px-2" style="font-size: 10px;" onclick="fokusSOS('<?= $sos['message'] ?>')">TRACK HP</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-list-ul me-2"></i>Live Feed Checkpoint</h6>
            </div>
            <div class="p-0 overflow-auto" style="max-height: 400px;">
                <div class="list-group list-group-flush">
                    <?php if(empty($mapData)): ?> <div class="p-4 text-center text-muted small">Belum ada data lokasi.</div> <?php endif; ?>
                    <?php foreach ($mapData as $data): ?>
                    <div class="list-group-item p-3 border-bottom border-light cp-item" onclick="focusToMap(<?= $data['lat'] ?>, <?= $data['lng'] ?>)">
                        <div class="d-flex justify-content-between mb-1">
                            <h6 class="fw-bold text-dark mb-0 small"><?= htmlspecialchars($data['nama']) ?></h6>
                            <span class="badge <?= $data['is_scanned'] ? 'bg-light text-dark border' : 'bg-danger-subtle text-danger' ?> rounded-pill" style="font-size: 0.65rem;">
                                <?= $data['is_scanned'] ? $data['time'] : 'Belum' ?>
                            </span>
                        </div>
                        <?php if($data['is_scanned'] && stripos($data['status'], 'Aman') === false): ?>
                            <button class="btn btn-xs btn-danger w-100 mt-2 btn-aksi" data-id="<?= $data['patroli_id'] ?>" data-lokasi="<?= htmlspecialchars($data['nama']) ?>" data-bs-toggle="modal" data-bs-target="#modalAksi">Tindak Lanjut</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAksi" tabindex="-1" style="z-index: 2000;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Penanganan Insiden</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_patroli" id="input_id_patroli">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lokasi</label>
                        <input type="text" class="form-control bg-light" id="input_lokasi" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tindakan Selesai</label>
                        <textarea name="keterangan_penanganan" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="simpan_penanganan" class="btn btn-success px-4">Tandai Aman</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Inisialisasi Peta
    const points = <?= json_encode($mapData) ?>;
    const sosData = <?= json_encode($activeSOS) ?>;
    const map = L.map('mapMonitoring').setView([-6.2408, 106.6730], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; SIPANDU' }).addTo(map);

    // Marker Checkpoint
    points.forEach(p => {
        if(p.lat && p.lng) {
            let color = p.is_scanned ? (p.status.toLowerCase().includes('aman') ? '#10b981' : '#f59e0b') : '#ef4444';
            L.circleMarker([p.lat, p.lng], { radius: 10, fillColor: color, color: "#fff", weight: 2, fillOpacity: 0.9 }).addTo(map)
             .bindPopup(`<b>${p.nama}</b><br>Status: ${p.status}`);
        }
    });

    // Marker SOS Petugas (HP TRACKING)
    sosData.forEach(s => {
        const coords = s.message.match(/(-?\d+\.\d+),(-?\d+\.\d+)/);
        if(coords) {
            const sosIcon = L.divIcon({ className: 'sos-marker-container', html: '<div class="pulse-sos-map"></div>', iconSize: [30, 30] });
            L.marker([coords[1], coords[2]], {icon: sosIcon}).addTo(map)
             .bindPopup(`<b class='text-danger'>?? SOS DARI PETUGAS: ${s.nama_lengkap}</b><br>Lokasi HP Terdeteksi`);
        }
    });

    function fokusSOS(msg) {
        const coords = msg.match(/(-?\d+\.\d+),(-?\d+\.\d+)/);
        if(coords) map.flyTo([coords[1], coords[2]], 18, { animate: true, duration: 1.5 });
    }
    function focusToMap(lat, lng) { if(lat && lng) map.flyTo([lat, lng], 18); }

    // Modal Handle
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-aksi');
        if (btn) {
            document.getElementById('input_id_patroli').value = btn.getAttribute('data-id');
            document.getElementById('input_lokasi').value = btn.getAttribute('data-lokasi');
        }
    });
</script>
