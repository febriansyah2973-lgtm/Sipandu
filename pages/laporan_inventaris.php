<style>
    @media print {
        .sidebar, .navbar, .btn-print, .card-header form, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .table th, .table td { border: 1px solid #000 !important; padding: 8px !important; font-size: 10pt !important; }
        body { background: #fff !important; }
    }
    .bg-soft-primary { background-color: #e3f2fd; color: #0d6efd; }
    .bg-soft-success { background-color: #e8f5e9; color: #198754; }
    .bg-soft-danger { background-color: #ffebee; color: #dc3545; }
    .bg-soft-warning { background-color: #fff8e1; color: #f57c00; }
</style>

<?php
require_once 'config/database.php';
$filter_kondisi = $_GET['kondisi'] ?? '';
$search = $_GET['keyword'] ?? '';
$sqlBarang = "SELECT b.*, 
             (SELECT COUNT(*) FROM pinjam_barang pb WHERE pb.barang_id = b.id AND pb.status = 'Dipinjam') as is_pinjam 
             FROM barang b WHERE 1=1";
$paramsB = [];
if(!empty($search)) { 
    $sqlBarang .= " AND (b.nama_barang LIKE :kw OR b.kode_barang LIKE :kw)"; 
    $paramsB['kw'] = "%$search%"; 
}
$stmtB = $pdo->prepare($sqlBarang);
$stmtB->execute($paramsB);
$dataBarang = $stmtB->fetchAll(PDO::FETCH_ASSOC);
$sqlKen = "SELECT * FROM kendaraan WHERE 1=1";
$paramsK = [];
if(!empty($search)) { 
    $sqlKen .= " AND (nama_kendaraan LIKE :kw OR nopol LIKE :kw OR kendaraan LIKE :kw OR merk LIKE :kw)"; 
    $paramsK['kw'] = "%$search%"; 
}
$stmtK = $pdo->prepare($sqlKen);
$stmtK->execute($paramsK);
$dataKen = $stmtK->fetchAll(PDO::FETCH_ASSOC);
$total_brg = count($dataBarang);
$total_ken = count($dataKen);
$total_out = 0;
$total_rusak = 0;

foreach($dataBarang as $b) { 
    if(($b['is_pinjam'] ?? 0) > 0) $total_out++; 
    if(stripos(($b['kondisi'] ?? ''), 'Rusak') !== false) $total_rusak++;
}
foreach($dataKen as $k) { 
    if(stripos(($k['status'] ?? ''), 'Dipakai') !== false) $total_out++; 
    if(stripos(($k['status'] ?? ''), 'Rusak') !== false) $total_rusak++;
}
?>

<div class="animate-fade-up px-3">
    <div class="d-flex justify-content-between align-items-end mb-4 no-print">
        <div>
            <h5 class="text-secondary mb-1">Laporan</h5>
            <h3 class="fw-bold text-dark">Rekapitulasi Inventaris</h3>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark btn-print">
                <i class="fas fa-print me-2"></i>Cetak Laporan
            </button>
            <button class="btn btn-success btn-print">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body p-3">
            <form action="" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="laporan_inventaris">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Cari Nama / Kode / Nopol</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Ketik pencarian..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Status Aset</label>
                    <select name="kondisi" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Baik" <?= $filter_kondisi == 'Baik' ? 'selected' : '' ?>>Baik / Tersedia</option>
                        <option value="Dipinjam" <?= $filter_kondisi == 'Dipinjam' ? 'selected' : '' ?>>Dipinjam / Dipakai</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-2"></i>Tampilkan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4 no-print text-center">
        <div class="col-md-3"><div class="card bg-soft-primary border-0 p-3 shadow-sm"><h3 class="fw-bold mb-0"><?= $total_brg ?></h3><small>Total Barang</small></div></div>
        <div class="col-md-3"><div class="card bg-soft-success border-0 p-3 shadow-sm"><h3 class="fw-bold mb-0"><?= $total_ken ?></h3><small>Total Kendaraan</small></div></div>
        <div class="col-md-3"><div class="card bg-soft-warning border-0 p-3 shadow-sm"><h3 class="fw-bold mb-0"><?= $total_out ?></h3><small>Dipinjam / Dipakai</small></div></div>
        <div class="col-md-3"><div class="card bg-soft-danger border-0 p-3 shadow-sm"><h3 class="fw-bold mb-0"><?= $total_rusak ?></h3><small>Temuan Rusak</small></div></div>
    </div>

    <div class="d-none d-print-block text-center mb-4">
        <h3 class="fw-bold mb-0">PT. TARGET KELOLA SECURINDO</h3>
        <p class="mb-0">Laporan Inventaris Aset & Kendaraan Operasional</p>
        <hr class="border-dark border-2 opacity-100">
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0"><h6 class="fw-bold mb-0 text-primary">Aset Barang & Perlengkapan</h6></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                    <tr><th>No</th><th class="text-start">Nama Barang</th><th>Kode</th><th>Lokasi</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php $n=1; foreach($dataBarang as $rb): ?>
                    <tr>
                        <td><?= $n++ ?></td>
                        <td class="text-start fw-bold"><?= $rb['nama_barang'] ?? $rb['nama'] ?? '-' ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $rb['kode_barang'] ?? $rb['kode'] ?? '-' ?></span></td>
                        <td><?= $rb['lokasi'] ?? '-' ?></td>
                        <td>
                            <?php 
                                if(($rb['is_pinjam'] ?? 0) > 0) {
                                    echo '<span class="badge bg-warning text-dark px-3 rounded-pill">Dipinjam</span>';
                                } else {
                                    $k_brg = $rb['kondisi'] ?? '-';
                                    $cls = (stripos($k_brg, 'Baik') !== false) ? 'success' : 'danger';
                                    echo '<span class="badge bg-'.$cls.' px-3 rounded-pill">'.$k_brg.'</span>';
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0"><h6 class="fw-bold mb-0 text-info">Armada Kendaraan Operasional</h6></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="bg-light text-secondary small text-uppercase fw-bold">
                    <tr><th>No</th><th>Jenis</th><th class="text-start">Unit Kendaraan</th><th>Plat Nomor</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php $nk=1; foreach($dataKen as $rk): ?>
                    <tr>
                        <td><?= $nk++ ?></td>
                        <td><?= stripos(($rk['jenis'] ?? ''), 'Mobil') !== false ? '<i class="fas fa-car text-primary"></i>' : '<i class="fas fa-motorcycle text-success"></i>' ?></td>
                        <td class="text-start fw-bold">
                            <?= $rk['nama_kendaraan'] ?? $rk['kendaraan'] ?? $rk['nama'] ?? $rk['merk'] ?? '-' ?>
                        </td>
                        <td>
                            <span class="badge bg-dark text-white font-monospace">
                                <?= $rk['nopol'] ?? $rk['plat_nomor'] ?? '-' ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                $s_ken = $rk['status'] ?? '-';
                                $bg = (stripos($s_ken, 'Dipakai') !== false) ? 'primary' : 'success';
                                echo '<span class="badge bg-'.$bg.' px-3 rounded-pill">'.$s_ken.'</span>';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>