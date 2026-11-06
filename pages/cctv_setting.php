<?php
require_once 'config/database.php';

// --- PROSES SIMPAN NVR BARU ---
if (isset($_POST['save_nvr'])) {
    $nama = $_POST['nama_nvr'];
    $ip   = $_POST['ip_nvr'];
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $ins = $pdo->prepare("INSERT INTO nvr_list (nama_nvr, ip_nvr, username, password) VALUES (?, ?, ?, ?)");
    if ($ins->execute([$nama, $ip, $user, $pass])) {
        echo "<script>alert('NVR Berhasil Ditambahkan!'); window.location='?page=cctv_setting';</script>";
    }
}

// --- PROSES EDIT NVR (FITUR BARU) ---
if (isset($_POST['edit_nvr'])) {
    $id   = $_POST['id_nvr'];
    $nama = $_POST['nama_nvr'];
    $ip   = $_POST['ip_nvr'];
    $user = $_POST['username'];
    
    // Cek apakah password diisi baru, kalau kosong berarti pakai password lama
    if (!empty($_POST['password'])) {
        $pass = $_POST['password'];
        $upd = $pdo->prepare("UPDATE nvr_list SET nama_nvr = ?, ip_nvr = ?, username = ?, password = ? WHERE id = ?");
        $upd->execute([$nama, $ip, $user, $pass, $id]);
    } else {
        $upd = $pdo->prepare("UPDATE nvr_list SET nama_nvr = ?, ip_nvr = ?, username = ? WHERE id = ?");
        $upd->execute([$nama, $ip, $user, $id]);
    }
    echo "<script>alert('Data NVR Berhasil Diperbarui!'); window.location='?page=cctv_setting';</script>";
}

// --- PROSES HAPUS NVR ---
if (isset($_GET['delete_id'])) {
    $del = $pdo->prepare("DELETE FROM nvr_list WHERE id = ?");
    $del->execute([$_GET['delete_id']]);
    echo "<script>window.location='?page=cctv_setting';</script>";
}

$nvrs = $pdo->query("SELECT * FROM nvr_list ORDER BY id DESC")->fetchAll();
?>

<div class="container-fluid animate-fade-up">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h3 class="fw-bold text-dark mb-0">Konfigurasi Alat NVR</h3>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addNvr">
            <i class="fas fa-plus me-2"></i>Tambah NVR
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">NAMA NVR</th>
                            <th>IP ADDRESS</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($nvrs as $n): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= htmlspecialchars($n['nama_nvr']) ?></td>
                            <td><code class="text-primary"><?= $n['ip_nvr'] ?></code></td>
                            <td>
                                <a href="?page=sync_nvr&id=<?= $n['id'] ?>" class="btn btn-success btn-sm rounded-pill px-3">
                                    <i class="fas fa-sync-alt me-1"></i> Sync CCTV
                                </a>
                                <button class="btn btn-warning btn-sm rounded-circle text-white ms-1" data-bs-toggle="modal" data-bs-target="#editNvr<?= $n['id'] ?>" title="Edit NVR">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?page=cctv_setting&delete_id=<?= $n['id'] ?>" class="btn btn-outline-danger btn-sm rounded-circle ms-1" onclick="return confirm('Hapus NVR ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach($nvrs as $n): ?>
<div class="modal fade" id="editNvr<?= $n['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Edit NVR: <?= htmlspecialchars($n['nama_nvr']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_nvr" value="<?= $n['id'] ?>">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">Nama Lokasi / NVR</label>
                    <input type="text" name="nama_nvr" class="form-control rounded-3 bg-light" value="<?= htmlspecialchars($n['nama_nvr']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">IP Address NVR</label>
                    <input type="text" name="ip_nvr" class="form-control rounded-3 bg-light" value="<?= htmlspecialchars($n['ip_nvr']) ?>" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="small fw-bold text-muted mb-1">Username</label>
                        <input type="text" name="username" class="form-control rounded-3 bg-light" value="<?= htmlspecialchars($n['username']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">Password Baru</label>
                        <input type="password" name="password" class="form-control rounded-3 bg-light" placeholder="(Abaikan jika sama)">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="submit" name="edit_nvr" class="btn btn-warning text-white w-100 rounded-pill fw-bold">Update Alat NVR</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="addNvr" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Tambah Alat NVR Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">Nama Lokasi / NVR</label>
                    <input type="text" name="nama_nvr" class="form-control rounded-3 bg-light" placeholder="Contoh: Kantor Pusat" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted mb-1">IP Address NVR</label>
                    <input type="text" name="ip_nvr" class="form-control rounded-3 bg-light" placeholder="192.168.0.130" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="small fw-bold text-muted mb-1">Username</label>
                        <input type="text" name="username" class="form-control rounded-3 bg-light" value="admin" required>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted mb-1">Password</label>
                        <input type="password" name="password" class="form-control rounded-3 bg-light" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="submit" name="save_nvr" class="btn btn-primary w-100 rounded-pill fw-bold">Simpan Alat NVR</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        document.body.appendChild(modal);
    });
});
</script>