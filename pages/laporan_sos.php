<?php
require_once 'config/database.php';

// 1. Tangkap Filter (Tanggal & Search)
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$search_user = $_GET['search_user'] ?? '';

// 2. Query Database dengan Fitur Search
try {
    $sql = "SELECT n.*, u.nama_lengkap as pengirim, u.username as kode_user 
            FROM notifications n 
            JOIN users u ON n.sender_id = u.id 
            WHERE n.category = 'alert' 
            AND DATE(n.created_at) BETWEEN :mulai AND :selesai";
    
    $params = [
        ':mulai' => $tgl_mulai,
        ':selesai' => $tgl_selesai
    ];

    // Tambahkan kondisi pencarian jika input search tidak kosong
    if (!empty($search_user)) {
        $sql .= " AND (u.nama_lengkap LIKE :search OR u.username LIKE :search OR n.message LIKE :search)";
        $params[':search'] = "%$search_user%";
    }

    $sql .= " ORDER BY n.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Hitung Statistik Ringkas
    $total_sos = count($reports);
    $selesai = 0;
    foreach($reports as $r) { 
        if(($r['is_read'] ?? 0) == 1 || stripos($r['status'] ?? '', 'selesai') !== false) $selesai++; 
    }
    $pending = $total_sos - $selesai;

} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-3'><b>Database Error:</b> " . $e->getMessage() . "</div>";
    $reports = [];
    $total_sos = $selesai = $pending = 0;
}

$nama_file = "Laporan_SOS_" . $tgl_mulai . "_sd_" . $tgl_selesai;
?>

<link rel="stylesheet" href="assets/css/master.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<div class="animate-fade-up">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="text-secondary mb-0">Laporan Security</h5>
            <h3 class="fw-bold text-dark">Rekapitulasi Penanganan SOS</h3>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle rounded-pill px-4 shadow-sm fw-bold" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-file-export me-2"></i>Export Laporan
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                <li><a class="dropdown-item py-2 fw-medium" href="javascript:void(0)" onclick="exportExcel()">
                    <i class="far fa-file-excel text-success me-2"></i>Format Excel (.xlsx)</a>
                </li>
                <li><a class="dropdown-item py-2 fw-medium" href="javascript:void(0)" onclick="exportPDF()">
                    <i class="far fa-file-pdf text-danger me-2"></i>Format PDF (.pdf)</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-center gap-3">
                    <div class="bg-primary-subtle text-primary rounded-circle p-3" style="width: 55px; height: 55px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bullhorn fs-5"></i>
                    </div>
                    <div class="text-start">
                        <h4 class="fw-bold mb-0 text-dark"><?= $total_sos ?></h4>
                        <small class="text-muted fw-bold">Total Kejadian</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-center gap-3">
                    <div class="bg-success-subtle text-success rounded-circle p-3" style="width: 55px; height: 55px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check-double fs-5"></i>
                    </div>
                    <div class="text-start">
                        <h4 class="fw-bold mb-0 text-dark"><?= $selesai ?></h4>
                        <small class="text-muted fw-bold">Sudah Ditangani</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-center gap-3">
                    <div class="bg-danger-subtle text-danger rounded-circle p-3" style="width: 55px; height: 55px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-clock fs-5"></i>
                    </div>
                    <div class="text-start">
                        <h4 class="fw-bold mb-0 text-dark"><?= $pending ?></h4>
                        <small class="text-muted fw-bold">Pending/Aktif</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="laporan_sos">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">DARI TANGGAL</label>
                    <input type="date" name="tgl_mulai" class="form-control rounded-3 border-0 bg-light py-2" value="<?= $tgl_mulai ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">SAMPAI TANGGAL</label>
                    <input type="date" name="tgl_selesai" class="form-control rounded-3 border-0 bg-light py-2" value="<?= $tgl_selesai ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">CARI PERSONIL / PESAN</label>
                    <input type="text" name="search_user" class="form-control rounded-3 border-0 bg-light py-2" placeholder="Ketik nama atau isi pesan..." value="<?= htmlspecialchars($search_user) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow-sm">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableSOS">
                <thead class="bg-light">
                    <tr class="text-uppercase small fw-bold">
                        <th class="px-4 py-3 border-0 text-secondary">WAKTU KEJADIAN</th>
                        <th class="py-3 border-0 text-secondary">PERSONIL (PENGIRIM)</th>
                        <th class="py-3 border-0 text-secondary">LOKASI & KOORDINAT</th>
                        <th class="py-3 border-0 text-secondary text-center">STATUS</th>
                        <th class="px-4 py-3 border-0 text-secondary">CATATAN PENANGANAN ADMIN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($reports)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Data SOS tidak ditemukan untuk pencarian ini.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($reports as $row): 
                        $isSelesai = (($row['is_read'] ?? 0) == 1 || stripos($row['status'] ?? '', 'selesai') !== false);
                    ?>
                    <tr>
                        <td class="px-4">
                            <div class="small fw-bold text-dark"><?= date('d/m/Y', strtotime($row['created_at'])) ?></div>
                            <div class="text-muted" style="font-size: 11px;"><?= date('H:i', strtotime($row['created_at'])) ?> WIB</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark mb-0 small"><?= htmlspecialchars($row['pengirim']) ?></div>
                            <div class="text-muted" style="font-size: 10px;">User: @<?= htmlspecialchars($row['kode_user']) ?></div>
                        </td>
                        <td class="small" style="max-width: 280px;"><?= htmlspecialchars($row['message']) ?></td>
                        <td class="text-center">
                            <?php if($isSelesai): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">SELESAI</span>
                            <?php else: ?>
                                <span class="badge bg-danger text-white rounded-pill px-3 animate-pulse">DARURAT</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 small text-secondary italic">
                            <?= ($row['handling_note'] ?? '') ? '"' . htmlspecialchars($row['handling_note']) . '"' : '<span class="text-muted">- Belum ada catatan -</span>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportExcel() {
    const table = document.getElementById("tableSOS");
    const wb = XLSX.utils.table_to_book(table, { sheet: "Laporan SOS" });
    XLSX.writeFile(wb, "<?= $nama_file ?>.xlsx");
}

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');
    doc.setFontSize(16);
    doc.text("LAPORAN PENANGANAN SOS - SIPANDU", 14, 15);
    doc.setFontSize(10);
    doc.text("Periode: <?= $tgl_mulai ?> s/d <?= $tgl_selesai ?> | Filter: <?= $search_user ?: 'Semua' ?>", 14, 22);
    doc.autoTable({
        html: '#tableSOS',
        startY: 30,
        theme: 'grid',
        headStyles: { fillColor: [52, 73, 94] },
        styles: { fontSize: 8 }
    });
    doc.save("<?= $nama_file ?>.pdf");
}
</script>

<script src="assets/js/app.js"></script>
<script src="assets/js/master.js"></script>