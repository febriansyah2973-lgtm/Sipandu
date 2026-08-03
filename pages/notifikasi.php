<?php
// 1. UPDATE SEMUA JADI DIBACA (Jika tombol ditekan)
if (isset($_POST['mark_all_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['id_user']]);
    echo "<script>window.location='index.php?page=notifikasi';</script>";
    exit;
}

// 2. AMBIL SEMUA NOTIFIKASI (Limit 50 Terakhir)
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['id_user']]);
$all_notif = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Riwayat Notifikasi</h3>
        <p class="text-secondary mb-0 small">Pemberitahuan aktivitas sistem terbaru.</p>
    </div>
    
    <?php if (!empty($all_notif)): ?>
    <form method="POST">
        <button type="submit" name="mark_all_read" class="btn btn-light border text-secondary shadow-sm rounded-pill px-4">
            <i class="fas fa-check-double me-2"></i> Tandai Semua Dibaca
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden animate-fade-up">
    <div class="list-group list-group-flush">
        
        <?php if (empty($all_notif)): ?>
            <div class="text-center py-5">
                <div class="mb-3 text-secondary opacity-25">
                    <i class="far fa-bell-slash fa-4x"></i>
                </div>
                <h5 class="fw-bold text-secondary">Tidak ada notifikasi</h5>
                <p class="text-muted small">Anda belum memiliki pemberitahuan apapun saat ini.</p>
            </div>
        <?php else: ?>
            
            <?php foreach ($all_notif as $n): ?>
            <a href="<?= $n['link'] ?>" class="list-group-item list-group-item-action p-4 d-flex gap-3 align-items-start <?= $n['is_read'] == 0 ? 'bg-primary-subtle' : 'bg-white' ?>">
                
                <div class="flex-shrink-0">
                    <?php if($n['category'] == 'success'): ?>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                    <?php elseif($n['category'] == 'alert'): ?>
                        <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fas fa-exclamation-triangle fa-lg"></i>
                        </div>
                    <?php elseif($n['category'] == 'warning'): ?>
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fas fa-bell fa-lg"></i>
                        </div>
                    <?php else: ?>
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fas fa-info-circle fa-lg"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex-grow-1">
                    <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fw-bold <?= $n['is_read'] == 0 ? 'text-primary' : 'text-dark' ?>">
                            <?= htmlspecialchars($n['title']) ?>
                            <?php if($n['is_read'] == 0): ?>
                                <span class="badge bg-danger rounded-pill ms-2" style="font-size: 0.6rem;">BARU</span>
                            <?php endif; ?>
                        </h6>
                        <small class="text-secondary fw-medium">
                            <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                        </small>
                    </div>
                    <p class="mb-1 text-muted small" style="line-height: 1.5;"><?= htmlspecialchars($n['message']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
            
        <?php endif; ?>
    </div>
</div>