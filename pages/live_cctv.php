<style>
    .cctv-card:hover { border-color: #0d6efd !important; transform: scale(1.01); transition: 0.3s; }
    .status-badge { font-size: 0.7rem; font-weight: bold; letter-spacing: 0.5px; }
</style>

<div class="row">
    <div class="col-12 mb-3">
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-video me-2 text-danger"></i>Professional Monitoring</h5>
                <small class="text-white-50"><i class="fas fa-server me-1"></i>PHP Native Client</small>
            </div>
            <div class="card-body bg-black">
                
                <div class="row g-3">
                    <?php for($i=1; $i<=4; $i++): ?>
                    <div class="col-md-6">
                        <div class="card bg-dark text-white h-100 border-secondary cctv-card">
                            
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><i class="fas fa-camera me-2"></i>CAM 0<?= $i ?></span>
                                <span id="status-<?= $i ?>" class="badge bg-secondary status-badge">CONNECTING...</span>
                            </div>

                            <div class="ratio ratio-16x9 bg-black position-relative overflow-hidden">
                                <div id="loader-<?= $i ?>" class="position-absolute top-50 start-50 translate-middle">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                                
                                <canvas id="canvas-<?= $i ?>" class="w-100 h-100"></canvas>

                                <div class="position-absolute bottom-0 end-0 p-2 opacity-50 hover-opacity-100">
                                    <button class="btn btn-sm btn-dark border-0" onclick="fullscreen(<?= $i ?>)" title="Fullscreen">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function initCamera(channel) {
        const canvas = document.getElementById(`canvas-${channel}`);
        const ctx = canvas.getContext('2d');
        const statusBadge = document.getElementById(`status-${channel}`);
        const loader = document.getElementById(`loader-${channel}`);
        canvas.width = 1280; 
        canvas.height = 720;

        const loadNextFrame = () => {
            const img = new Image();
            img.src = `cctv_proxy.php?ch=${channel}&_t=${new Date().getTime()}`;

            img.onload = function() {
                if(loader) loader.style.display = 'none';
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                if(statusBadge.innerText !== 'ONLINE') {
                    statusBadge.className = 'badge bg-success status-badge';
                    statusBadge.innerHTML = '<i class="fas fa-circle me-1"></i>ONLINE';
                }
                setTimeout(loadNextFrame, 100); 
            };

            img.onerror = function() {
                statusBadge.className = 'badge bg-danger status-badge';
                statusBadge.innerText = 'NO SIGNAL';
                setTimeout(loadNextFrame, 2000);
            };
        };

        loadNextFrame();
    }
    function fullscreen(id) {
        const elem = document.getElementById('canvas-' + id);
        if (elem.requestFullscreen) { elem.requestFullscreen(); }
        else if (elem.webkitRequestFullscreen) { elem.webkitRequestFullscreen(); } // Safari
    }
    setTimeout(() => initCamera(1), 0);
    setTimeout(() => initCamera(2), 300);
    setTimeout(() => initCamera(3), 600);
    setTimeout(() => initCamera(4), 900);
</script>