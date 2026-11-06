<?php
require_once 'config/database.php';

$cctv_items = [];
try {
    $cctv_items = $pdo->query("SELECT * FROM cctv_list WHERE status = 'Aktif' ORDER BY channel ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cctv_items = [];
}

if (empty($cctv_items)) {
    for ($i = 1; $i <= 4; $i++) {
        $cctv_items[] = [
            'id' => $i,
            'nama_cctv' => "Kamera Pos 0$i",
            'channel' => $i,
            'ip_address' => '192.168.1.' . (100 + $i)
        ];
    }
}
?>

<style>
    .cctv-grid-container { transition: all 0.3s ease; }
    .cctv-card-modern {
        background: #0f172a;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    .cctv-card-modern:hover {
        border-color: #3b82f6;
        transform: scale(1.005);
    }
    .cctv-header {
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        padding: 10px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .status-badge-live {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        border-radius: 50px;
        padding: 4px 10px;
    }
    .cctv-video-controls {
        top: auto !important;
        left: auto !important;
        right: 0;
        bottom: 0;
        width: auto !important;
        height: auto !important;
        display: flex;
        align-items: center;
        flex-direction: row;
        padding: 10px;
    }
    .cctv-video-controls .btn {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50% !important;
    }
    .cctv-video-frame {
        background: #020617 !important;
    }
    @media (max-width: 768px) {
        .cctv-page-header { margin-bottom: 1rem !important; }
        .cctv-page-header h3 { font-size: 1.15rem; }
        .cctv-page-header p { display: none; }
        .cctv-page-actions { width: 100%; justify-content: space-between; }
        .cctv-page-actions .btn-group { flex: 1; }
        .cctv-page-actions .btn-group .btn { flex: 1; padding-inline: .5rem; }
        .cctv-page-actions > .btn { margin-left: 0 !important; white-space: nowrap; }
        .cctv-monitor-card { border-radius: 14px !important; }
        .cctv-monitor-card > .card-header { padding: .85rem 1rem !important; }
        .cctv-monitor-card > .card-header > span:first-child { font-size: .8rem; }
        .cctv-monitor-card > .card-header .badge { font-size: .65rem; padding: .45rem .6rem !important; }
        .cctv-monitor-card > .card-body { padding: .75rem !important; }
        .cctv-grid-container { --bs-gutter-y: .75rem; }
        .cctv-grid-col { width: 100%; }
        .cctv-card-modern { border-radius: 12px; }
        .cctv-header { padding: .7rem .8rem; }
        .cctv-header .status-badge-live { font-size: .6rem; padding: 4px 7px; }
        .cctv-card-modern .ratio { min-height: 0; }
        .cctv-card-modern canvas { display: block; }
    }
</style>

<div class="container-fluid animate-fade-up cctv-page">
    <!-- Header Command Center -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 cctv-page-header">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-video me-2 text-danger"></i>Live View CCTV Surveillance</h3>
            <p class="text-secondary small mb-0">Pemantauan kamera pengawas real-time Command Center (Multi-Channel Grid).</p>
        </div>
        <div class="d-flex align-items-center gap-2 cctv-page-actions">
            <span class="small fw-bold text-muted me-2 d-none d-sm-inline"><i class="fas fa-th-large me-1"></i>GRID:</span>
            <div class="btn-group shadow-sm" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-start-pill px-3 active" onclick="setGrid(2)">2x2</button>
                <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="setGrid(1)">1x1</button>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-end-pill px-3" onclick="setGrid(3)">3x3</button>
            </div>
            <a href="index.php?page=cctv_setting" class="btn btn-sipandu btn-sipandu-outline btn-sm ms-2">
                <i class="fas fa-cog me-1"></i>Setting CCTV
            </a>
        </div>
    </div>

    <!-- Main CCTV Grid Container -->
    <div class="card card-sipandu border-0 shadow-lg mb-4 cctv-monitor-card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-satellite-dish text-success me-2 animate-pulse-sos"></i>LIVE STREAMING MONITORING</span>
            <span class="badge bg-primary rounded-pill px-3"><i class="fas fa-broadcast-tower me-1"></i><?= count($cctv_items) ?> KAMERA AKTIF</span>
        </div>
        <div class="card-body bg-black p-3 p-md-4">
            <div class="row g-3 cctv-grid-container" id="cctvGridRow">
                <?php foreach ($cctv_items as $index => $cam): 
                    $camId = (int)$cam['id'];
                    $camName = htmlspecialchars($cam['nama_cctv']);
                    $channel = (int)$cam['channel'];
                ?>
                <div class="col-md-6 cctv-grid-col" id="col-cam-<?= $camId ?>">
                    <div class="card cctv-card-modern text-white h-100">
                        <div class="cctv-header d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-truncate max-w-400">
                                <i class="fas fa-camera text-primary me-2"></i><?= $camName ?> <small class="text-white-50">(CH <?= $channel ?>)</small>
                            </span>
                            <span id="status-<?= $camId ?>" class="badge bg-secondary status-badge-live">
                                <i class="fas fa-spinner fa-spin me-1"></i>CONNECTING...
                            </span>
                        </div>

                        <div class="ratio ratio-16x9 bg-black position-relative overflow-hidden cctv-video-frame">
                            <div id="loader-<?= $camId ?>" class="position-absolute top-50 start-50 translate-middle z-3 text-center">
                                <div class="spinner-border text-primary mb-2" role="status"></div>
                                <div class="small text-white-50">Menghubungkan Stream...</div>
                            </div>
                            
                            <canvas id="canvas-<?= $camId ?>" class="w-100 h-100 object-fit-contain"></canvas>

                            <!-- Controls Overlay -->
                            <div class="position-absolute cctv-video-controls gap-2 z-3 opacity-75 hover-opacity-100">
                                <button class="btn btn-sm btn-dark bg-dark-subtle border-0 rounded-circle text-white shadow-sm" 
                                        onclick="snapshotCamera(<?= $camId ?>, '<?= addslashes($camName) ?>')" title="Ambil Foto Snapshot">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <button class="btn btn-sm btn-dark bg-dark-subtle border-0 rounded-circle text-white shadow-sm" 
                                        onclick="fullscreenCamera('canvas-<?= $camId ?>')" title="Layar Penuh">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function setGrid(cols) {
        const gridCols = document.querySelectorAll('.cctv-grid-col');
        gridCols.forEach(col => {
            col.className = 'cctv-grid-col';
            if (cols === 1) {
                col.classList.add('col-12');
            } else if (cols === 3) {
                col.classList.add('col-md-4');
            } else {
                col.classList.add('col-md-6');
            }
        });
    }

    function initCameraStream(camId, channel) {
        const canvas = document.getElementById(`canvas-${camId}`);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const statusBadge = document.getElementById(`status-${camId}`);
        const loader = document.getElementById(`loader-${camId}`);
        
        canvas.width = 1280; 
        canvas.height = 720;

        let activeWebSocket = null;

        // 1. Coba WebSocket JSMpeg Stream (Relay Server Port 8000)
        try {
            const wsUrl = `ws://${window.location.hostname}:8000/${camId}`;
            const ws = new WebSocket(wsUrl);
            activeWebSocket = ws;

            ws.onopen = () => {
                if(loader) loader.style.display = 'none';
                if(statusBadge) {
                    statusBadge.className = 'badge bg-success status-badge-live';
                    statusBadge.innerHTML = '<i class="fas fa-circle me-1"></i>ONLINE (LIVE 25FPS)';
                }
            };

            ws.onerror = () => {
                ws.close();
                fallbackHttpStream();
            };
        } catch (e) {
            fallbackHttpStream();
        }

        // 2. Fallback HTTP Proxy Stream
        function fallbackHttpStream() {
            const loadNextFrame = () => {
                const img = new Image();
                img.src = `cctv_proxy.php?ch=${channel}&_t=${new Date().getTime()}`;

                img.onload = function() {
                    if(loader) loader.style.display = 'none';
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    if(statusBadge) {
                        statusBadge.className = 'badge bg-success status-badge-live';
                        statusBadge.innerHTML = '<i class="fas fa-circle me-1"></i>ONLINE (HTTP PROXY)';
                    }
                    setTimeout(loadNextFrame, 150);
                };

                img.onerror = function() {
                    if(statusBadge) {
                        statusBadge.className = 'badge bg-danger status-badge-live';
                        statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>NO SIGNAL';
                    }
                    setTimeout(loadNextFrame, 3000);
                };
            };
            loadNextFrame();
        }
    }

    function fullscreenCamera(canvasId) {
        const elem = document.getElementById(canvasId);
        if (!elem) return;
        if (elem.requestFullscreen) { elem.requestFullscreen(); }
        else if (elem.webkitRequestFullscreen) { elem.webkitRequestFullscreen(); }
    }

    function snapshotCamera(camId, camName) {
        const canvas = document.getElementById(`canvas-${camId}`);
        if (!canvas) return;
        const image = canvas.toDataURL('image/jpeg');
        const link = document.createElement('a');
        link.download = `snapshot_${camName.replace(/\s+/g, '_')}_${Date.now()}.jpg`;
        link.href = image;
        link.click();
    }

    document.addEventListener('DOMContentLoaded', () => {
        <?php foreach ($cctv_items as $cam): ?>
            initCameraStream(<?= (int)$cam['id'] ?>, <?= (int)$cam['channel'] ?>);
        <?php endforeach; ?>
    });
</script>