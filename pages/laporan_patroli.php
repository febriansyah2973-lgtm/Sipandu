<style>
    @media print {
        .sidebar, .navbar, .btn-print, .card-header form, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table { width: 100% !important; border-collapse: collapse !important; }
        .table th, .table td { border: 1px solid #000 !important; padding: 5px !important; font-size: 10pt !important; }
        body { background: #fff !important; -webkit-print-color-adjust: exact; }
    }
    .bg-soft-primary { background-color: #e8f0fe; color: #1967d2; }
    /* Style tambahan untuk thumbnail foto */
    .img-thumbnail-patroli {
        width: 50px; height: 50px; object-fit: cover; cursor: pointer; border-radius: 8px; transition: 0.3s;
    }
    .img-thumbnail-patroli:hover { transform: scale(1.1); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
</style>

<?php
require_once 'config/database.php';
$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$search_name = $_GET['search_name'] ?? '';

// --- UPDATE QUERY: Menambahkan foto_selfie dan jarak_meter ---
$sql = "SELECT pt.*, 
               u.nama_lengkap, 
               u.id as user_id,
               cp.nama_lokasi as nama_checkpoint  
        FROM patroli pt 
        JOIN users u ON pt.user_id = u.id 
        LEFT JOIN checkpoints cp ON pt.checkpoint_id = cp.id
        WHERE (DATE(pt.waktu_scan) BETWEEN :mulai AND :selesai)";

$params = ['mulai' => $mulai, 'selesai' => $selesai];
if (!empty($search_name)) {
    $sql .= " AND u.nama_lengkap LIKE :search_name";
    $params['search_name'] = "%$search_name%";
}
$sql .= " ORDER BY pt.waktu_scan DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

$total_patroli = count($data);
$total_aman = 0; 
$total_temuan = 0;

foreach($data as $r) {
    if(stripos($r['status_temuan'], 'Aman') !== false) {
        $total_aman++;
    } else {
        $total_temuan++;
    }
}
?>

<div class="animate-fade-up px-2">
    <div class="d-flex justify-content-between align-items-end mb-4 no-print">
        <div>
            <h5 class="text-secondary mb-1">Laporan</h5>
            <h3 class="fw-bold text-dark">Rekapitulasi Patroli & Galeri Selfie</h3>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark btn-print">
                <i class="fas fa-print me-2"></i>Cetak Laporan
            </button>
            <button onclick="window.location.href='export_excel_patroli.php?mulai=<?= $mulai ?>&selesai=<?= $selesai ?>&search_name=<?= $search_name ?>'" class="btn btn-success btn-print">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body p-3">
            <form action="" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="laporan_patroli">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" name="mulai" class="form-control" value="<?= $mulai ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="selesai" class="form-control" value="<?= $selesai ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Cari Nama Personil</label>
                    <input type="text" name="search_name" class="form-control" placeholder="Nama..." value="<?= htmlspecialchars($search_name) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-2"></i>Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-none d-print-block text-center mb-4">
        <h3 class="fw-bold mb-0">PT. TARGET KELOLA SECURINDO</h3>
        <p class="mb-0">Laporan Kegiatan Patroli Area & Audit Kehadiran</p>
        <small class="text-muted">Periode: <?= date('d M Y', strtotime($mulai)) ?> s/d <?= date('d M Y', strtotime($selesai)) ?></small>
        <hr class="border-dark border-2 opacity-100">
    </div>

    <div class="row g-3 mb-4 no-print">
        <div class="col-md-4 col-12"><div class="card bg-soft-primary border-0 p-3 text-center"><h3 class="fw-bold mb-0"><?= $total_patroli ?></h3><small>Total Titik Diperiksa</small></div></div>
        <div class="col-md-4 col-6"><div class="card bg-success-subtle text-success border-0 p-3 text-center"><h3 class="fw-bold mb-0"><?= $total_aman ?></h3><small>Kondisi Aman</small></div></div>
        <div class="col-md-4 col-6"><div class="card bg-danger-subtle text-danger border-0 p-3 text-center"><h3 class="fw-bold mb-0"><?= $total_temuan ?></h3><small>Temuan / Insiden</small></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0" id="tableLaporan">
                <thead class="bg-light text-secondary small text-uppercase fw-bold text-center">
                    <tr>
                        <th width="5%">No</th>
                        <th>Waktu</th>
                        <th class="text-start">Petugas & Lokasi</th>
                        <th>Foto Selfie</th> <th>Jarak (m)</th> <th>Status</th>
                        <th width="20%">Keterangan</th>
                        <th>Foto Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data)): ?>
                        <tr><td colspan="8" class="text-center py-4">Tidak ada data ditemukan.</td></tr>
                    <?php else: ?>
                        <?php $no=1; foreach($data as $row): ?>
                        <tr class="text-center">
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold"><?= date('H:i', strtotime($row['waktu_scan'])) ?></div>
                                <small class="text-muted"><?= date('d/m/y', strtotime($row['waktu_scan'])) ?></small>
                            </td>
                            <td class="text-start">
                                <div class="fw-bold text-dark"><?= $row['nama_lengkap'] ?></div>
                                <div class="small text-primary"><i class="fas fa-map-marker-alt me-1"></i><?= $row['nama_checkpoint'] ?? 'ID: ' . $row['checkpoint_id'] ?></div>
                            </td>
                            
                            <td>
                                <?php if(!empty($row['foto_selfie'])): ?>
                                    <img src="assets/img/patroli/<?= $row['foto_selfie'] ?>" 
                                         class="img-thumbnail-patroli" 
                                         onclick="previewImage('assets/img/patroli/<?= $row['foto_selfie'] ?>', 'Selfie Petugas')">
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php 
                                    $jarak = $row['jarak_meter'] ?? 0;
                                    $color = ($jarak > 100) ? 'text-danger fw-bold' : 'text-muted';
                                ?>
                                <span class="<?= $color ?>"><?= $jarak ?>m</span>
                            </td>

                            <td>
                                <?php 
                                    $status = $row['status_temuan'];
                                    $bg = (stripos($status, 'Aman') === false) ? 'danger' : 'success';
                                ?>
                                <span class="badge bg-<?= $bg ?>-subtle text-<?= $bg ?> border border-<?= $bg ?> rounded-pill px-2">
                                    <?= $status ?>
                                </span>
                            </td>
                            <td class="text-start small text-muted"><?= $row['keterangan'] ?? '-' ?></td>
                            <td>
                                <?php if(!empty($row['foto_bukti'])): ?>
                                    <?php $pathTemuan = 'assets/img/temuan/' . $row['foto_bukti']; ?>
                                    <?php $pathLegacy = 'uploads/patroli/' . $row['foto_bukti']; ?>
                                    <?php $fotoPath = file_exists($pathTemuan) ? $pathTemuan : $pathLegacy; ?>
                                    <img src="<?= $fotoPath ?>" 
                                         class="img-thumbnail-patroli" 
                                         onclick="previewImage('<?= $fotoPath ?>', 'Foto Bukti Temuan')">
                                <?php else: ?> - <?php endif; ?>
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

<script>
function previewImage(url, title) {
    Swal.fire({
        title: title,
        imageUrl: url,
        imageAlt: 'Preview Foto',
        showCloseButton: true,
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#6366f1'
    });
}
</script>