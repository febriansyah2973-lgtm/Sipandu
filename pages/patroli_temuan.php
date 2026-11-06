<?php
require_once 'config/database.php';
require_once 'config/csrf.php';
$csrf_token = csrf_token_value();

$filterStatus = $_GET['status_filter'] ?? '';
$filterKategori = $_GET['kategori_filter'] ?? '';
$sql = "SELECT p.*, c.nama_lokasi, u.nama_lengkap 
        FROM patroli p 
        JOIN checkpoints c ON p.checkpoint_id = c.id 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status_temuan = 'Temuan'";
$params = [];

if ($filterStatus != '') {
    $sql .= " AND p.status_penanganan = :status";
    $params['status'] = $filterStatus;
}

if ($filterKategori != '') {
    $sql .= " AND p.keterangan LIKE :kategori";
    $params['kategori'] = "%[{$filterKategori}]%";
}

$sql .= " ORDER BY p.waktu_scan DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dataTemuan = $stmt->fetchAll();

function parseTemuanKeterangan($keterangan) {
    $parsed = ['judul' => '', 'kategori' => '', 'detail' => $keterangan];
    if (preg_match('/^\[(.*?)\]\s*\[(.*?)\]\s*(.*)$/', $keterangan, $m)) {
        $parsed['judul'] = $m[1];
        $parsed['kategori'] = $m[2];
        $parsed['detail'] = $m[3];
    } elseif (preg_match('/^\[(.*?)\]\s*(.*)$/', $keterangan, $m)) {
        $parsed['judul'] = $m[1];
        $parsed['detail'] = $m[2];
    }
    return $parsed;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Monitoring Patroli Temuan</h3>
        <p class="text-secondary mb-0 small">Daftar laporan insiden dan progres penanganannya.</p>
    </div>
</div>

<div class="card-master mb-4 shadow-sm">
        <div class="card-header-action p-3 gap-2 overflow-auto align-items-center">
            <a href="index.php?page=patroli_temuan" class="btn <?= $filterStatus == '' ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm rounded-pill px-4">Semua Laporan</a>
            <a href="index.php?page=patroli_temuan&status_filter=Baru<?= $filterKategori ? '&kategori_filter=' . urlencode($filterKategori) : '' ?>" class="btn <?= $filterStatus == 'Baru' ? 'btn-danger' : 'btn-outline-secondary' ?> btn-sm rounded-pill px-4">Baru</a>
            <a href="index.php?page=patroli_temuan&status_filter=Proses<?= $filterKategori ? '&kategori_filter=' . urlencode($filterKategori) : '' ?>" class="btn <?= $filterStatus == 'Proses' ? 'btn-warning text-white' : 'btn-outline-secondary' ?> btn-sm rounded-pill px-4">Proses</a>
            <a href="index.php?page=patroli_temuan&status_filter=Selesai<?= $filterKategori ? '&kategori_filter=' . urlencode($filterKategori) : '' ?>" class="btn <?= $filterStatus == 'Selesai' ? 'btn-success' : 'btn-outline-secondary' ?> btn-sm rounded-pill px-4">Selesai</a>

            <form method="GET" class="d-flex gap-2 ms-auto align-items-center">
                <input type="hidden" name="page" value="patroli_temuan">
                <input type="hidden" name="status_filter" value="<?= htmlspecialchars($filterStatus) ?>">
                <select name="kategori_filter" class="form-select form-select-sm bg-light border-0 rounded-pill" style="min-width: 180px;">
                    <option value="">Semua Kategori</option>
                    <option value="Gangguan" <?= $filterKategori == 'Gangguan' ? 'selected' : '' ?>>Gangguan</option>
                    <option value="Kebocoran" <?= $filterKategori == 'Kebocoran' ? 'selected' : '' ?>>Kebocoran</option>
                    <option value="Kerusakan" <?= $filterKategori == 'Kerusakan' ? 'selected' : '' ?>>Kerusakan</option>
                    <option value="Kecurangan" <?= $filterKategori == 'Kecurangan' ? 'selected' : '' ?>>Kecurangan</option>
                    <option value="Lainnya" <?= $filterKategori == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Filter</button>
            </form>
</div>


<div class="row g-4">
    <?php if(empty($dataTemuan)): ?>
        <div class="col-12 text-center py-5">
            <div class="opacity-25 mb-3">
                <i class="fas fa-clipboard-check fa-4x text-secondary"></i>
            </div>
            <p class="text-secondary fw-medium">Tidak ada data temuan untuk status ini.</p>
        </div>
    <?php endif; ?>

    <?php foreach($dataTemuan as $row): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card-master h-100 shadow-sm border-top border-4 <?= $row['status_penanganan'] == 'Baru' ? 'border-danger' : ($row['status_penanganan'] == 'Proses' ? 'border-warning' : 'border-success') ?>">
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge <?= $row['status_penanganan'] == 'Baru' ? 'bg-danger' : ($row['status_penanganan'] == 'Proses' ? 'bg-warning' : 'bg-success') ?> rounded-pill px-3">
                        <?= $row['status_penanganan'] ?>
                    </span>
                    <small class="text-secondary small fw-bold"><i class="far fa-clock me-1"></i><?= date('d M, H:i', strtotime($row['waktu_scan'])) ?></small>
                </div>

                <?php $parsed = parseTemuanKeterangan($row['keterangan']); ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($parsed['judul'] ?: $row['nama_lokasi']) ?></h6>
                    <?php if (!empty($parsed['kategori'])): ?>
                        <span class="badge bg-info-subtle text-info rounded-pill small py-2 px-3"><?= htmlspecialchars($parsed['kategori']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-muted small mb-3 lh-sm patrol-description">
                    <?= htmlspecialchars($parsed['detail'] ?: $row['keterangan']) ?>
                </div>

                <div class="bg-light rounded mb-3 overflow-hidden text-center position-relative patrol-photo-card" onclick="lihatFoto('assets/img/temuan/<?= $row['foto_bukti'] ?>', 'Temuan di <?= htmlspecialchars($row['nama_lokasi']) ?>')">
                    <?php if($row['foto_bukti']): ?>
                        <img src="assets/img/temuan/<?= $row['foto_bukti'] ?>" class="w-100 h-100 object-fit-cover transition-all">
                        <div class="position-absolute top-50 start-50 translate-middle opacity-0 group-hover-opacity-100 text-white">
                            <i class="fas fa-search-plus fa-2x"></i>
                        </div>
                    <?php else: ?>
                        <div class="d-flex h-100 align-items-center justify-content-center text-secondary opacity-50">
                            <i class="fas fa-image fa-2x"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <div class="lh-1">
                        <small class="text-muted d-block small-text-xs">Pelapor:</small>
                        <span class="small fw-bold text-dark"><?= $row['nama_lengkap'] ?></span>
                    </div>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" 
                            onclick="updateStatusTemuan(<?= $row['id'] ?>, '<?= $row['status_penanganan'] ?>')">
                        <i class="fas fa-edit me-1"></i> Status
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function updateStatusTemuan(id, currentStatus) {
    Swal.fire({
        title: 'Update Penanganan',
        text: 'Ubah status progres temuan ini:',
        input: 'select',
        inputOptions: {
            'Baru': 'Baru (Belum Ditangani)',
            'Proses': 'Proses (Dalam Perbaikan)',
            'Selesai': 'Selesai (Tuntas)'
        },
        inputValue: currentStatus,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        confirmButtonColor: '#3b82f6',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('act', 'update_status_temuan');
            formData.append('id', id);
            formData.append('status_baru', result.value);
            formData.append('csrf_token', <?= json_encode($csrf_token) ?>);

            fetch('proses_patroli.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}
</script>
