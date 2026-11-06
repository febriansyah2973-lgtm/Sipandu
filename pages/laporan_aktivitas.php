<?php
// 1. Proteksi Role (Sesuai kode Bapak)
if (strtolower($role) !== 'admin' && strtolower($role) !== 'administrator') {
    echo "<script>location.href='index.php?page=dashboard';</script>"; exit;
}

require_once 'config/database.php';

// 2. Filter Tanggal & Search
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-t');
$search_user = $_GET['search_user'] ?? '';

// 3. Query Data (KEMBALI KE login_logs & created_at)
try {
    $sql = "SELECT * FROM login_logs WHERE DATE(created_at) BETWEEN :mulai AND :selesai";
    $params = [':mulai' => $tgl_mulai, ':selesai' => $tgl_selesai];

    if (!empty($search_user)) {
        $sql .= " AND (username LIKE :search OR keterangan LIKE :search)";
        $params[':search'] = "%$search_user%";
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-3'><b>Fatal Error:</b> " . $e->getMessage() . "</div>";
    $logs = [];
}

// 4. Hitung Statistik
$total_log = count($logs);
$sukses = 0; $gagal = 0;
foreach($logs as $d) {
    if(strtolower($d['status']) == 'success' || strtolower($d['status']) == 'berhasil') { $sukses++; } 
    else { $gagal++; }
}

$nama_file = "Log_Aktivitas_" . $tgl_mulai . "_sd_" . $tgl_selesai;
?>

<div class="animate-fade-up">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Laporan Aktivitas User</h3>
            <p class="text-secondary mb-0 small">Periode: <?= date('d M Y', strtotime($tgl_mulai)) ?> s/d <?= date('d M Y', strtotime($tgl_selesai)) ?></p>
        </div>
        <div class="dropdown">
            <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-file-export me-1"></i> Export Laporan
            </button>
            <ul class="dropdown-menu shadow border-0">
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="exportExcel()"><i class="fas fa-file-excel text-success me-2"></i> Format Excel (.xlsx)</a></li>
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="exportPDF()"><i class="fas fa-file-pdf text-danger me-2"></i> Format PDF (.pdf)</a></li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="laporan_aktivitas">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold text-secondary mb-2">DARI TANGGAL</label>
                        <input type="date" name="tgl_mulai" class="form-control bg-light border-0 py-2 rounded-3" value="<?= $tgl_mulai ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-secondary mb-2">SAMPAI TANGGAL</label>
                        <input type="date" name="tgl_selesai" class="form-control bg-light border-0 py-2 rounded-3" value="<?= $tgl_selesai ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-secondary mb-2">CARI USERNAME / KETERANGAN</label>
                        <input type="text" name="search_user" class="form-control bg-light border-0 py-2 rounded-3" placeholder="Ketik nama..." value="<?= htmlspecialchars($search_user) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm">
                            <i class="fas fa-search me-1"></i> CARI
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center gap-3">
                <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fas fa-list-ul fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0"><?= $total_log ?></h4>
                    <small class="text-secondary fw-bold">Total Aktivitas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center gap-3">
                <div class="bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fas fa-check-circle fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0"><?= $sukses ?></h4>
                    <small class="text-secondary fw-bold">Login Berhasil</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 d-flex flex-row align-items-center gap-3">
                <div class="bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fas fa-times-circle fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0"><?= $gagal ?></h4>
                    <small class="text-secondary fw-bold">Login Gagal</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableLog">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary small fw-bold">WAKTU</th>
                        <th class="py-3 text-secondary small fw-bold">USERNAME</th>
                        <th class="py-3 text-secondary small fw-bold">IP ADDRESS</th>
                        <th class="py-3 text-secondary small fw-bold text-center">STATUS</th>
                        <th class="py-3 text-secondary small fw-bold pe-4">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if ($total_log > 0): foreach ($logs as $log): ?>
                    <tr>
                        <td class="ps-4"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($log['username']) ?></td>
                        <td class="font-monospace text-muted small"><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td class="text-center">
                            <span class="badge rounded-pill px-3 <?= $log['status'] == 'Success' ? 'bg-success' : 'bg-danger' ?>">
                                <?= $log['status'] ?>
                            </span>
                        </td>
                        <td class="text-secondary pe-4"><?= htmlspecialchars($log['keterangan']) ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-secondary">Data tidak ditemukan.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<script>
function exportExcel() {
    let wb = XLSX.utils.table_to_book(document.getElementById("tableLog"), {sheet: "Aktivitas"});
    XLSX.writeFile(wb, "<?= $nama_file ?>.xlsx");
}

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');
    doc.setFontSize(16);
    doc.text("LAPORAN AKTIVITAS USER", 40, 40);
    doc.setFontSize(10);
    doc.text("Periode: <?= $tgl_mulai ?> s/d <?= $tgl_selesai ?>", 40, 60);
    doc.autoTable({
        html: '#tableLog',
        startY: 80,
        theme: 'grid',
        headStyles: { fillColor: [52, 73, 94] },
        styles: { fontSize: 8 },
        margin: { left: 40, right: 40 }
    });
    doc.save("<?= $nama_file ?>.pdf");
}
</script>

<script src="assets/js/app.js"></script>
<script src="assets/js/master.js"></script>
