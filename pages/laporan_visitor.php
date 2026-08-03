<style>
    @media print {
        .sidebar, .navbar, .btn-print, .card-header form, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table th, .table td { border: 1px solid #000 !important; padding: 5px !important; font-size: 10pt !important; }
        .body { background: #fff !important; -webkit-print-color-adjust: exact; }
        .badge { border: none !important; color: #000 !important; padding: 0 !important; font-weight: normal; }
    }
    .bg-soft-info { background-color: #e0f2f1; color: #00695c; }
    .bg-soft-warning { background-color: #fff8e1; color: #ff8f00; }
</style>

<?php
require_once 'config/database.php';
$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$keyword = $_GET['keyword'] ?? '';
$sql = "SELECT * FROM tamu 
        WHERE (DATE(waktu_masuk) BETWEEN :mulai AND :selesai)";
$params = ['mulai' => $mulai, 'selesai' => $selesai];

if (!empty($keyword)) {
    $sql .= " AND (nama_tamu LIKE :keyword OR instansi LIKE :keyword OR bertemu_dengan LIKE :keyword)";
    $params['keyword'] = "%$keyword%";
}
$sql .= " ORDER BY waktu_masuk DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();
$total_tamu = count($data);
$tamu_aktif = 0;
$tamu_selesai = 0;
foreach($data as $r) {
    if (empty($r['waktu_keluar']) || $r['waktu_keluar'] == '0000-00-00 00:00:00') {
        $tamu_aktif++;
    } else {
        $tamu_selesai++;
    }
}
?>

<div class="animate-fade-up px-2">
    <div class="d-flex justify-content-between align-items-end mb-4 no-print">
        <div>
            <h5 class="text-secondary mb-1">Laporan</h5>
            <h3 class="fw-bold text-dark">Buku Tamu (Visitor)</h3>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark btn-print">
                <i class="fas fa-print me-2"></i>Cetak
            </button>
            <button onclick="window.location.href='export_excel_visitor.php?mulai=<?= $mulai ?>&selesai=<?= $selesai ?>&keyword=<?= $keyword ?>'" class="btn btn-success btn-print">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body p-3">
            <form action="" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="laporan_visitor">
                
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" name="mulai" class="form-control" value="<?= $mulai ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="selesai" class="form-control" value="<?= $selesai ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Cari (Nama / Instansi)</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Ketik pencarian..." value="<?= htmlspecialchars($keyword) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-2"></i>Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-md-4">
            <div class="card bg-soft-primary border-0 p-3 text-center">
                <h3 class="fw-bold mb-0 text-primary"><?= $total_tamu ?></h3>
                <small class="text-muted">Total Kunjungan</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-soft-warning border-0 p-3 text-center">
                <h3 class="fw-bold mb-0 text-warning"><?= $tamu_aktif ?></h3>
                <small class="text-muted">Sedang Berkunjung (Aktif)</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-soft-info border-0 p-3 text-center">
                <h3 class="fw-bold mb-0 text-success"><?= $tamu_selesai ?></h3>
                <small class="text-muted">Selesai Berkunjung</small>
            </div>
        </div>
    </div>

    <div class="d-none d-print-block text-center mb-4">
        <h3 class="fw-bold mb-0">PT. TARGET KELOLA SECURINDO</h3>
        <p class="mb-0">Laporan Kunjungan Tamu (Visitor Log)</p>
        <small class="text-muted">Periode: <?= date('d M Y', strtotime($mulai)) ?> s/d <?= date('d M Y', strtotime($selesai)) ?></small>
        <hr class="border-dark border-2 opacity-100">
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase fw-bold text-center">
                    <tr>
                        <th width="5%">No</th>
                        <th>Waktu Kedatangan</th>
                        <th class="text-start">Identitas Tamu</th>
                        <th class="text-start">Tujuan / Keperluan</th>
                        <th>Waktu Keluar</th>
                        <th>Status</th>
                        <th>Foto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data)): ?>
                        <tr><td colspan="7" class="text-center py-4">Tidak ada data tamu ditemukan.</td></tr>
                    <?php else: ?>
                        <?php $no=1; foreach($data as $row): ?>
                        <tr class="text-center">
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold"><?= date('H:i', strtotime($row['waktu_masuk'])) ?></div>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($row['waktu_masuk'])) ?></small>
                            </td>
                            <td class="text-start">
                                <div class="fw-bold"><?= $row['nama_tamu'] ?></div>
                                <div class="small text-muted"><i class="fas fa-building me-1"></i> <?= $row['instansi'] ?? 'Pribadi' ?></div>
                            </td>
                            <td class="text-start">
                                <div class="small">Bertemu: <b><?= $row['bertemu_dengan'] ?></b></div>
                                <small class="text-muted">"<?= substr($row['keperluan'], 0, 30) ?>..."</small>
                            </td>
                            <td>
                                <?php if(!empty($row['waktu_keluar']) && $row['waktu_keluar'] != '0000-00-00 00:00:00'): ?>
                                    <div class="fw-bold text-dark"><?= date('H:i', strtotime($row['waktu_keluar'])) ?></div>
                                    
                                    <?php 
                                        $masuk = new DateTime($row['waktu_masuk']);
                                        $keluar = new DateTime($row['waktu_keluar']);
                                        $interval = $masuk->diff($keluar);
                                        echo "<small class='text-muted'>(" . $interval->h . " jam " . $interval->i . " mnt)</small>";
                                    ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(empty($row['waktu_keluar']) || $row['waktu_keluar'] == '0000-00-00 00:00:00'): ?>
                                    <span class="badge bg-warning text-dark border border-warning rounded-pill px-3">On Site</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border rounded-pill px-3">Check Out</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($row['foto_wajah'])): ?>
                                    <img src="uploads/visitor/<?= $row['foto_wajah'] ?>" 
                                         class="rounded-circle border" width="40" height="40" 
                                         style="object-fit:cover; cursor:pointer;"
                                         onclick="window.open(this.src)">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border mx-auto" style="width:40px; height:40px;">
                                        <i class="fas fa-user text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="d-none d-print-block mt-5">
        <div class="row">
            <div class="col-4 text-center offset-8">
                <p>Jakarta, <?= date('d F Y') ?><br>Mengetahui,</p>
                <br><br><br>
                <p class="fw-bold text-decoration-underline">Chief Security</p>
            </div>
        </div>
    </div>
</div>