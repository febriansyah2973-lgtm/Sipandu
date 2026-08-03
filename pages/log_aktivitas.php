<?php
// Proteksi: Hanya Administrator yang boleh masuk
if (strtolower($role) !== 'admin' && strtolower($role) !== 'administrator') {
    echo "<script>location.href='index.php?page=dashboard';</script>";
    exit;
}

$query = $pdo->query("SELECT * FROM login_logs ORDER BY created_at DESC LIMIT 200");
$logs = $query->fetchAll();
?>

<div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Log Aktivitas Login</h3>
        <p class="text-secondary mb-0 small">Audit riwayat percobaan masuk ke sistem.</p>
    </div>
    <button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="confirmClear()">
        <i class="fas fa-trash-alt me-1"></i> Bersihkan Log
    </button>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 15px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>Username</th>
                        <th>IP Address</th>
                        <th>Status</th>
                        <th>Detail Keterangan</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="ps-4"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($log['username']) ?></td>
                        <td class="text-monospace"><?= $log['ip_address'] ?></td>
                        <td>
                            <?php if($log['status'] == 'Success'): ?>
                                <span class="badge bg-success-subtle text-success px-3 rounded-pill">SUCCESS</span>
                            <?php elseif($log['status'] == 'Frozen'): ?>
                                <span class="badge bg-warning-subtle text-warning px-3 rounded-pill">FROZEN</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger px-3 rounded-pill">FAILED</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($log['keterangan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>