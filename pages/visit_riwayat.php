<style>
    .bg-gradient-visitor { background: linear-gradient(135deg, #ffffff 0%, #f3f4f6 100%); }
    .card-stat { border: none; transition: all 0.2s; position: relative; overflow: hidden; }
    .card-stat:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    .stat-icon { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1; transform: rotate(-15deg); }
    .avatar-initial { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .bg-rnd-1 { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-rnd-2 { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-rnd-3 { background: linear-gradient(45deg, #36b9cc, #258391); }
    .bg-rnd-4 { background: linear-gradient(45deg, #f6c23e, #dda20a); }
    .bg-rnd-5 { background: linear-gradient(45deg, #e74a3b, #be2617); }
</style>

<?php
require_once 'config/database.php';
require_once 'config/helper_notif.php'; // <--- Tambahan Saraf Notifikasi

if (isset($_POST['act']) && $_POST['act'] == 'checkout') {
    $id = $_POST['id'];
    
    // Ambil nama tamu untuk notifikasi
    $stmtN = $pdo->prepare("SELECT nama_tamu FROM visitors WHERE id = ?");
    $stmtN->execute([$id]);
    $nama_tamu = $stmtN->fetchColumn();

    $stmt = $pdo->prepare("UPDATE visitors SET status = 'Keluar', waktu_keluar = NOW() WHERE id = ?");
    if ($stmt->execute([$id])) {
        
        // --- LOGIC NOTIFIKASI: Kabari Admin tamu sudah pulang ---
        $admins = getAdminIds($pdo);
        sendNotification($pdo, $admins, 'success', 'Tamu Telah Keluar ??', "$nama_tamu telah melakukan checkout dan meninggalkan area.", 'index.php?page=visit_riwayat');

        echo "<script>Swal.fire({icon: 'success', title: 'Checkout Berhasil', text: 'Tamu telah keluar area.', timer: 1500, showConfirmButton: false});</script>";
    }
}

$today = date('Y-m-d');
$sqlTotal = "SELECT COUNT(*) FROM visitors WHERE DATE(waktu_masuk) = '$today'";
$totalHariIni = $pdo->query($sqlTotal)->fetchColumn();

$sqlAktif = "SELECT COUNT(*) FROM visitors WHERE status = 'Masuk'";
$totalAktif = $pdo->query($sqlAktif)->fetchColumn();

$tamuAktif = $pdo->query("SELECT * FROM visitors WHERE status = 'Masuk' ORDER BY waktu_masuk DESC")->fetchAll();
$riwayat = $pdo->query("SELECT * FROM visitors WHERE status = 'Keluar' ORDER BY waktu_keluar DESC LIMIT 20")->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 animate-fade-up">
    <div>
        <h4 class="fw-bold text-dark mb-1">Pusat Data Kunjungan</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0 small">
                <li class="breadcrumb-item"><a href="#" class="text-muted text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active text-primary" aria-current="page">Visitor Management</li>
            </ol>
        </nav>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="index.php?page=visit_input" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="fas fa-plus-circle me-2"></i> Registrasi Tamu Baru
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-stat h-100 shadow-sm border-start border-4 border-warning">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase text-muted small fw-bold mb-1">Sedang Berkunjung</p>
                        <h2 class="text-dark fw-bold mb-0"><?= $totalAktif ?> <span class="fs-6 text-muted fw-normal">Orang</span></h2>
                    </div>
                    <div class="bg-warning-subtle text-warning rounded-circle p-3">
                        <i class="fas fa-walking fa-2x"></i>
                    </div>
                </div>
                <i class="fas fa-door-open stat-icon text-warning"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-stat h-100 shadow-sm border-start border-4 border-success">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase text-muted small fw-bold mb-1">Total Tamu Hari Ini</p>
                        <h2 class="text-dark fw-bold mb-0"><?= $totalHariIni ?> <span class="fs-6 text-muted fw-normal">Orang</span></h2>
                    </div>
                    <div class="bg-success-subtle text-success rounded-circle p-3">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
                <i class="fas fa-calendar-check stat-icon text-success"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-stat h-100 shadow-sm border-start border-4 border-info bg-primary text-white" style="background: linear-gradient(45deg, #4e73df, #224abe);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1">Visitor System</h5>
                        <p class="small mb-0 opacity-75">Keamanan area terjaga dengan monitoring tamu yang ketat.</p>
                    </div>
                    <i class="fas fa-shield-alt stat-icon text-white opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow mb-4 rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-warning mb-0"><i class="fas fa-user-clock me-2"></i>Tamu Sedang di Lokasi (Aktif)</h6>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">Perlu Checkout</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4 py-3">Profil Tamu</th>
                    <th>Tujuan Kunjungan</th>
                    <th>Waktu Masuk</th>
                    <th>Pass Tamu</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($tamuAktif)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted fst-italic">Tidak ada tamu yang sedang berkunjung.</td></tr>
                <?php else: foreach($tamuAktif as $row): 
                        $inisial = substr($row['nama_tamu'], 0, 1);
                        $bgRandom = 'bg-rnd-' . rand(1, 5);
                    ?>
                    <tr>
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-initial <?= $bgRandom ?> me-3"><?= strtoupper($inisial) ?></div>
                                <div>
                                    <div class="fw-bold text-dark"><?= $row['nama_tamu'] ?></div>
                                    <div class="small text-muted"><i class="fas fa-building me-1"></i> <?= $row['instansi'] ?: 'Pribadi' ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-bold text-dark">Bertemu: <?= $row['bertemu_dengan'] ?></div>
                            <small class="text-secondary d-inline-block text-truncate" style="max-width: 200px;"><?= $row['keperluan'] ?></small>
                        </td>
                        <td>
                            <span class="fw-bold text-success"><?= date('H:i', strtotime($row['waktu_masuk'])) ?></span>
                            <small class="text-muted d-block" style="font-size: 10px;">WIB</small>
                        </td>
                        <td>
                            <button onclick="cetakKartu('<?= $row['nama_tamu'] ?>', '<?= $row['instansi'] ?: 'Pribadi' ?>', '<?= $row['bertemu_dengan'] ?>')" 
                                    class="btn btn-light btn-sm border text-primary rounded-pill">
                                <i class="fas fa-id-badge"></i> Badge
                            </button>
                        </td>
                        <td class="text-end pe-4">
                            <form method="POST" onsubmit="return confirm('Konfirmasi Checkout?');">
                                <input type="hidden" name="act" value="checkout">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                                    Checkout <i class="fas fa-sign-out-alt ms-1"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-history me-2 text-secondary"></i>Log Riwayat Kunjungan</h6>
        <div class="input-group input-group-sm" style="width: 200px;">
            <input type="text" class="form-control bg-light" placeholder="Cari tamu..." id="searchHistory">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tableRiwayat">
            <thead class="bg-light text-secondary small text-uppercase">
                <tr>
                    <th class="ps-4 py-3">Nama Tamu</th>
                    <th>Detail</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Durasi</th>
                    <th class="text-end pe-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($riwayat as $row): 
                    $masuk = strtotime($row['waktu_masuk']);
                    $keluar = strtotime($row['waktu_keluar']);
                    $durasi = round(abs($keluar - $masuk) / 60, 0);
                ?>
                <tr>
                    <td class="ps-4 py-3">
                        <div class="fw-bold text-dark"><?= $row['nama_tamu'] ?></div>
                        <small class="text-muted"><?= $row['instansi'] ?: '-' ?></small>
                    </td>
                    <td class="small"><i class="fas fa-user-friends me-1 text-muted"></i> <?= $row['bertemu_dengan'] ?></td>
                    <td class="small"><?= date('d M, H:i', $masuk) ?></td>
                    <td class="small"><?= date('d M, H:i', $keluar) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= $durasi ?> mnt</span></td>
                    <td class="text-end pe-4"><span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">Selesai</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function cetakKartu(nama, instansi, bertemu) {
    Swal.fire({
        title: 'Visitor Pass',
        html: `
            <div style="border: 2px dashed #ccc; padding: 20px; background: #f9f9f9; border-radius: 10px;">
                <h3 style="margin:0; color:#4e73df;">VISITOR</h3>
                <p style="margin:0; font-size: 11px; color:#666;">PT TARGET INTEGRATED SYSTEM</p>
                <hr>
                <h2 style="margin: 10px 0; font-weight:bold;">${nama}</h2>
                <p style="margin:0;">${instansi}</p>
                <br>
                <div style="background:#eef2f7; padding:5px; border-radius:5px; font-size:12px;">
                    Bertemu: <strong>${bertemu}</strong>
                </div>
            </div>
        `,
        confirmButtonText: '<i class="fas fa-print"></i> Cetak',
        confirmButtonColor: '#333',
    }).then((result) => { if (result.isConfirmed) { window.print(); } });
}

document.getElementById('searchHistory').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#tableRiwayat tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>