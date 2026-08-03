<link rel="stylesheet" href="assets/css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
require_once 'config/database.php';
$role = strtolower($_SESSION['role']);
$dir_handbook = "assets/uploads/handbook/";

// --- 1. LOGIKA HAPUS DOKUMEN (HANYA ADMIN/PENGAWAS) ---
if (isset($_GET['delete']) && in_array($role, ['admin', 'pengawas'])) {
    $id_del = $_GET['delete'];
    
    // Ambil nama file dulu untuk dihapus dari folder
    $stmt_file = $pdo->prepare("SELECT file_path FROM sop_handbook WHERE id = ?");
    $stmt_file->execute([$id_del]);
    $file = $stmt_file->fetch();

    if ($file) {
        // Hapus file fisik
        if (file_exists($dir_handbook . $file['file_path'])) {
            unlink($dir_handbook . $file['file_path']);
        }
        // Hapus record di database
        $stmt_del = $pdo->prepare("DELETE FROM sop_handbook WHERE id = ?");
        if ($stmt_del->execute([$id_del])) {
            echo "<script>
                Swal.fire('Terhapus!', 'Dokumen telah dihapus permanen.', 'success')
                .then(() => { window.location.href='index.php?page=sop_handbook'; });
            </script>";
        }
    }
}

// --- 2. LOGIKA UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && in_array($role, ['admin', 'pengawas'])) {
    if (!empty($_FILES['file_sop']['name'])) {
        $judul = htmlspecialchars($_POST['judul']);
        $kat   = $_POST['kategori'];
        $ext   = strtolower(pathinfo($_FILES['file_sop']['name'], PATHINFO_EXTENSION));
        $file_name = "SOP_" . time() . "." . $ext;

        if (!is_dir($dir_handbook)) mkdir($dir_handbook, 0777, true);
        if (move_uploaded_file($_FILES['file_sop']['tmp_name'], $dir_handbook . $file_name)) {
            $stmt = $pdo->prepare("INSERT INTO sop_handbook (judul, kategori, file_path, uploaded_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$judul, $kat, $file_name, $_SESSION['id_user']]);
            echo "<script>Swal.fire('Berhasil!', 'SOP telah diunggah.', 'success');</script>";
        }
    }
}

$data_sop = $pdo->query("SELECT * FROM sop_handbook ORDER BY created_at DESC")->fetchAll();
?>

<style>
    /* CSS SCOPED (Hanya untuk halaman ini agar Sidebar tidak berubah) */
    .sop-wrapper .sop-card-item {
        border: none;
        border-radius: 20px;
        background: #fff;
        transition: all 0.3s ease;
        border: 1px solid #f0f2f5;
        overflow: hidden;
    }
    .sop-wrapper .sop-card-item:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        border-color: #0d6efd;
    }
    .sop-wrapper .sop-icon-box {
        width: 55px;
        height: 55px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        background: #fff5f5;
        color: #e03131;
    }
    
    /* MODAL FIXED */
    #modalAddSOP .modal-content {
        border-radius: 25px !important;
        border: none;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
    }
</style>

<div class="sop-wrapper animate-fade-up px-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="text-secondary mb-1">Knowledge Base</h5>
            <h3 class="fw-bold text-dark">Digital SOP & Handbook</h3>
        </div>
        <?php if (in_array($role, ['admin', 'pengawas'])): ?>
            <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddSOP">
                <i class="fas fa-upload me-2"></i>Tambah Dokumen
            </button>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <?php if(empty($data_sop)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">Belum ada dokumen yang diunggah.</p>
            </div>
        <?php else: foreach ($data_sop as $s): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card sop-card-item h-100 shadow-sm text-center">
                    <div class="card-body p-4">
                        <div class="sop-icon-box shadow-sm">
                            <i class="fas fa-file-pdf fa-2x"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= $s['judul'] ?>"><?= $s['judul'] ?></h6>
                        <div class="mb-4">
                            <span class="badge bg-light text-secondary rounded-pill px-3"><?= $s['kategori'] ?></span>
                        </div>
                        
                        <div class="d-flex gap-2 align-items-center">
                            <a href="<?= $dir_handbook . $s['file_path'] ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-bold">
                                <i class="fas fa-eye me-1"></i> BACA
                            </a>
                            <a href="<?= $dir_handbook . $s['file_path'] ?>" download class="btn btn-light btn-sm rounded-circle border">
                                <i class="fas fa-download"></i>
                            </a>
                            
                            <?php if (in_array($role, ['admin', 'pengawas'])): ?>
                                <button type="button" onclick="confirmDelete(<?= $s['id'] ?>)" class="btn btn-light btn-sm rounded-circle border text-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<div class="modal fade" id="modalAddSOP" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Upload Panduan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">JUDUL DOKUMEN</label>
                    <input type="text" name="judul" class="form-control rounded-3 bg-light border-0" placeholder="Contoh: Prosedur Patroli" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">KATEGORI</label>
                    <select name="kategori" class="form-select rounded-3 bg-light border-0">
                        <option value="SOP">SOP (Standar Operasional)</option>
                        <option value="Handbook">Handbook (Buku Saku)</option>
                        <option value="Darurat">Instruksi Darurat</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-secondary mb-2">FILE DOKUMEN (PDF)</label>
                    <input type="file" name="file_sop" class="form-control rounded-3 bg-light border-0" accept=".pdf" required>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow">
                    <i class="fas fa-save me-2"></i>SIMPAN DOKUMEN
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Fungsi Hapus dengan Konfirmasi
function confirmDelete(id) {
    Swal.fire({
        title: 'Hapus Dokumen?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6e7881',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?page=sop_handbook&delete=${id}`;
        }
    });
}

// Perbaikan Modal
document.addEventListener('DOMContentLoaded', function() {
    const modalSOP = document.getElementById('modalAddSOP');
    if (modalSOP) {
        document.body.appendChild(modalSOP);
        modalSOP.addEventListener('hidden.bs.modal', function () {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(b => b.remove());
            document.body.style.overflow = 'auto';
        });
    }
});
</script>