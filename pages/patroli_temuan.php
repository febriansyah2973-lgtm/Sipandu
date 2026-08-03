<link rel="stylesheet" href="assets/css/master.css">

<?php
require_once 'config/database.php';
$filterStatus = $_GET['status_filter'] ?? '';
$sql = "SELECT p.*, c.nama_lokasi, u.nama_lengkap 
        FROM patroli p 
        JOIN checkpoints c ON p.checkpoint_id = c.id 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status_temuan = 'Temuan'";

if ($filterStatus != '') {
    $sql .= " AND p.status_penanganan = '$filterStatus'";
}

$sql .= " ORDER BY p.waktu_scan DESC";
$stmt = $pdo->query($sql);
$dataTemuan = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Monitoring Patroli Temuan</h3>
        <p class="text-secondary mb-0 small">Daftar laporan insiden dan progres penanganannya.</p>
    </div>
</div>

<div class="card-master mb-4 shadow-sm">
    <div class="p-3 d-flex gap-2 overflow-auto">
        <a href="index.php?page=patroli_temuan" class="btn <?= $filterStatus == '' ? 'btn-primary' : 'btn-light border' ?> btn-sm rounded-pill px-4">Semua Laporan</a>
        <a href="index.php?page=patroli_temuan&status_filter=Baru" class="btn <?= $filterStatus == 'Baru' ? 'btn-danger' : 'btn-light border' ?> btn-sm rounded-pill px-4">Baru</a>
        <a href="index.php?page=patroli_temuan&status_filter=Proses" class="btn <?= $filterStatus == 'Proses' ? 'btn-warning text-white' : 'btn-light border' ?> btn-sm rounded-pill px-4">Proses</a>
        <a href="index.php?page=patroli_temuan&status_filter=Selesai" class="btn <?= $filterStatus == 'Selesai' ? 'btn-success' : 'btn-light border' ?> btn-sm rounded-pill px-4">Selesai</a>
    </div>
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

                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['nama_lokasi']) ?></h6>
                <div class="text-muted small mb-3 lh-sm" style="min-height: 45px;">
                    <?= htmlspecialchars($row['keterangan']) ?>
                </div>

                <div class="bg-light rounded mb-3 overflow-hidden text-center position-relative group-hover-zoom" style="height: 160px; cursor: pointer;" 
                     onclick="lihatFoto('assets/img/temuan/<?= $row['foto_bukti'] ?>', 'Temuan di <?= htmlspecialchars($row['nama_lokasi']) ?>')">
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
                        <small class="text-muted d-block" style="font-size: 0.65rem;">Pelapor:</small>
                        <span class="small fw-bold text-dark"><?= $row['nama_lengkap'] ?></span>
                    </div>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3" 
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