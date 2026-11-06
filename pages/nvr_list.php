<?php
require_once 'config/database.php';
$nvrs = $pdo->query("SELECT * FROM nvr_list ORDER BY id DESC")->fetchAll();
?>

<div class="container-fluid animate-fade-up">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">Daftar Alat NVR</h3>
        <button class="btn btn-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#addNvrModal">
            <i class="fas fa-plus me-2"></i>Tambah NVR Baru
        </button>
    </div>

    <div class="row">
        <?php foreach($nvrs as $n): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="fas fa-server fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($n['nama_nvr']) ?></h5>
                            <span class="badge bg-light text-dark border small"><?= $n['ip_nvr'] ?></span>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="?page=sync_nvr&id=<?= $n['id'] ?>" class="btn btn-success rounded-pill">
                            <i class="fas fa-sync-alt me-2"></i>Sync Semua CCTV
                        </a>
                        <a href="?page=live_cctv&nvr_id=<?= $n['id'] ?>" class="btn btn-outline-primary rounded-pill">
                            <i class="fas fa-desktop me-2"></i>Monitor Live
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>