<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .map-container { height: 600px; width: 100%; border-radius: 12px; z-index: 1; }
    .cp-list { max-height: 600px; overflow-y: auto; }
    .cp-item { transition: all 0.2s; border-left: 4px solid transparent; cursor: pointer; }
    .cp-item:hover { background-color: #f8f9fc; transform: translateX(5px); }
    .cp-item.active { background-color: #f0f4ff; border-left-color: #4e73df; }
    .custom-div-icon {
        background: transparent;
        border: none;
    }
    .marker-pin {
        width: 30px; height: 30px; border-radius: 50% 50% 50% 0; background: #3b82f6; position: absolute; transform: rotate(-45deg);
        left: 50%; top: 50%; margin: -15px 0 0 -15px;
        box-shadow: 0 3px 5px rgba(0,0,0,0.3);
    }
    .marker-pin::after {
        content: ''; width: 14px; height: 14px; margin: 8px 0 0 8px; background: #fff; position: absolute; border-radius: 50%;
    }
    .marker-pin.visited { background: #10b981; }
    .marker-pin.alert { background: #ef4444; } 
    
    .marker-avatar {
        width: 40px; height: 40px; border-radius: 50%; border: 3px solid #fff; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.2); object-fit: cover;
    }
</style>

<?php
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
$sql = "SELECT c.*, 
        (SELECT p.waktu_scan FROM patroli p WHERE p.checkpoint_id = c.id AND DATE(p.waktu_scan) = '$today' ORDER BY p.waktu_scan DESC LIMIT 1) as last_scan,
        (SELECT p.user_id FROM patroli p WHERE p.checkpoint_id = c.id AND DATE(p.waktu_scan) = '$today' ORDER BY p.waktu_scan DESC LIMIT 1) as user_id,
        (SELECT u.nama_lengkap FROM patroli p JOIN users u ON p.user_id = u.id WHERE p.checkpoint_id = c.id AND DATE(p.waktu_scan) = '$today' ORDER BY p.waktu_scan DESC LIMIT 1) as petugas,
        (SELECT u.foto FROM patroli p JOIN users u ON p.user_id = u.id WHERE p.checkpoint_id = c.id AND DATE(p.waktu_scan) = '$today' ORDER BY p.waktu_scan DESC LIMIT 1) as foto_petugas
        FROM checkpoints c 
        ORDER BY c.nama_lokasi ASC";

$stmt = $pdo->query($sql);
$checkpoints = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalTitik = count($checkpoints);
$sudahScan = 0;
foreach($checkpoints as $cp) {
    if($cp['last_scan']) $sudahScan++;
}
$persen = $totalTitik > 0 ? round(($sudahScan/$totalTitik)*100) : 0;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 animate-fade-up">
    <div>
        <h4 class="fw-bold text-dark mb-1">Live Monitoring Patroli</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0 small">
                <li class="breadcrumb-item"><a href="#" class="text-muted text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active text-primary" aria-current="page">Peta Sebaran</li>
            </ol>
        </nav>
    </div>
    
    <div class="d-flex align-items-center gap-3 mt-3 mt-md-0">
        <div class="bg-white px-3 py-2 rounded-pill shadow-sm border d-flex align-items-center gap-3">
            <div class="d-flex flex-column align-items-end lh-1">
                <span class="small text-muted fw-bold">PROGRESS HARI INI</span>
                <span class="h6 mb-0 fw-bold text-primary"><?= $sudahScan ?> / <?= $totalTitik ?> Titik</span>
            </div>
            <div style="width: 50px; height: 50px;">
                <div class="rounded-circle border border-4 border-light position-relative d-flex align-items-center justify-content-center h-100 w-100" 
                     style="background: conic-gradient(#4e73df <?= $persen ?>%, #f8f9fc 0);">
                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 80%; height: 80%;">
                        <span class="small fw-bold"><?= $persen ?>%</span>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-primary btn-sm rounded-pill px-3 h-100" onclick="location.reload()">
            <i class="fas fa-sync-alt me-1"></i> Refresh
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2">
                <div id="map" class="map-container"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list-ul me-2"></i>Status Checkpoint</h6>
            </div>
            <div class="cp-list p-0">
                <div class="list-group list-group-flush">
                    <?php foreach($checkpoints as $index => $row): ?>
                        <?php 
                            $isVisited = !empty($row['last_scan']);
                            $isRecent = false;
                            if ($isVisited) {
                                $scanTime = strtotime($row['last_scan']);
                                if (time() - $scanTime < 3600) $isRecent = true;
                            }
                        ?>
                        <div class="list-group-item cp-item p-3" onclick="focusToMap(<?= $row['latitude'] ?>, <?= $row['longitude'] ?>, '<?= $row['nama_lokasi'] ?>')">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark"><?= $row['nama_lokasi'] ?></h6>
                                    <div class="small text-muted mb-1">
                                        <i class="fas fa-map-pin me-1"></i> <?= $row['latitude'] ? $row['latitude'] . ', ' . $row['longitude'] : 'No GPS' ?>
                                    </div>
                                    
                                    <?php if($isVisited): ?>
                                        <div class="d-flex align-items-center mt-2">
                                            <?php if($row['foto_petugas']): ?>
                                                <img src="assets/img/profil/<?= $row['foto_petugas'] ?>" class="rounded-circle border me-2" width="25" height="25">
                                            <?php else: ?>
                                                <div class="bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:25px; height:25px; font-size:10px;">
                                                    <?= substr($row['petugas'],0,1) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="small fw-bold text-dark"><?= $row['petugas'] ?></div>
                                                <div class="small text-muted" style="font-size: 10px;">
                                                    <i class="far fa-clock me-1"></i><?= date('H:i', strtotime($row['last_scan'])) ?> WIB
                                                    <?php if($isRecent): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill ms-1" style="font-size: 8px;">BARU SAJA</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border mt-2">Belum Discan</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <?php if($isVisited): ?>
                                        <i class="fas fa-check-circle text-success fs-4"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle text-danger fs-4 opacity-25"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const checkpoints = <?= json_encode($checkpoints) ?>;
    const map = L.map('map').setView([-6.200000, 106.816666], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; SIPANDU System'
    }).addTo(map);
    var markers = [];
    checkpoints.forEach(cp => {
        if(cp.latitude && cp.longitude) {
            const isVisited = cp.last_scan != null;
            const timeDiff = isVisited ? (new Date() - new Date(cp.last_scan)) / 1000 : 99999;
            const isRecent = timeDiff < 3600;

            let iconHtml = '';
            if(isRecent && cp.foto_petugas) {
                iconHtml = `<img src="assets/img/profil/${cp.foto_petugas}" class="marker-avatar">`;
            } else if (isRecent) {
                iconHtml = `<div class="marker-avatar bg-primary text-white d-flex align-items-center justify-content-center fw-bold border border-white border-3 shadow">${cp.petugas.charAt(0)}</div>`;
            } else {
                const colorClass = isVisited ? 'visited' : 'alert';
                iconHtml = `<div class="marker-pin ${colorClass}"></div>`;
            }

            const customIcon = L.divIcon({
                className: 'custom-div-icon',
                html: iconHtml,
                iconSize: [30, 42],
                iconAnchor: [15, 42]
            });
            let popupContent = `<strong>${cp.nama_lokasi}</strong><br>`;
            if(isVisited) {
                popupContent += `<span class="text-success">✔ Sudah Patroli</span><br>
                                 <small>Oleh: <b>${cp.petugas}</b></small><br>
                                 <small>Jam: ${cp.last_scan.substring(11,16)} WIB</small>`;
            } else {
                popupContent += `<span class="text-danger">✖ Belum Patroli</span>`;
            }
            const marker = L.marker([cp.latitude, cp.longitude], {icon: customIcon})
                .addTo(map)
                .bindPopup(popupContent);
            
            markers.push(marker);
        }
    });
    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds(), {padding: [50, 50]});
    }
    function focusToMap(lat, lng, nama) {
        if(lat && lng) {
            map.flyTo([lat, lng], 18, {
                animate: true,
                duration: 1.5
            });
        } else {
            Swal.fire('Info', 'Titik ini belum memiliki koordinat GPS.', 'info');
        }
    }
</script>