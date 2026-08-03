<?php
require_once 'config/database.php';
require_once 'config/helper_notif.php'; // <--- Tambahan: Saraf Notifikasi
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['act']) && $_POST['act'] == 'add_visitor') {
    $nama     = trim($_POST['nama_tamu']);
    $instansi = trim($_POST['instansi']);
    $hp       = trim($_POST['no_hp']);
    $bertemu  = trim($_POST['bertemu_dengan']);
    $tujuan   = trim($_POST['keperluan']);
    
    // Sinkronisasi ID User
    $petugas  = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 1;

    $sql = "INSERT INTO visitors (user_id, nama_tamu, instansi, no_hp, bertemu_dengan, keperluan, waktu_masuk, status) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Masuk')";
    
    if ($pdo->prepare($sql)->execute([$petugas, $nama, $instansi, $hp, $bertemu, $tujuan])) {
        
        // --- LOGIC NOTIFIKASI: Kabari Admin ada tamu ---
        $admins = getAdminIds($pdo);
        sendNotification(
            $pdo, 
            $admins, 
            'info', 
            'Tamu Baru Tiba ??', 
            "$nama ($instansi) tiba di lobi untuk bertemu $bertemu.", 
            'index.php?page=visit_riwayat'
        );

        echo "<script>
            Swal.fire({
                icon: 'success', 
                title: 'Check-In Sukses', 
                html: 'Selamat datang, <b>$nama</b>.<br>Data kunjungan telah tercatat.', 
                timer: 2000, 
                showConfirmButton: false
            }).then(() => { window.location.href = 'index.php?page=visit_riwayat'; });
        </script>";
    }
}
?>

<div class="container-fluid px-0">
    <div class="mb-4 animate-fade-up">
        <a href="index.php?page=visit_riwayat" class="text-decoration-none text-muted small mb-2 d-inline-block">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
        <h4 class="fw-bold text-dark">Registrasi Tamu Baru</h4>
    </div>

    <div class="row justify-content-center animate-fade-up">
        <div class="col-lg-8 col-xl-7">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-4 px-4 text-center" style="background: linear-gradient(45deg, #4e73df, #224abe);">
                    <i class="fas fa-id-card fa-3x mb-2 opacity-50"></i>
                    <h5 class="fw-bold mb-0">Formulir Buku Tamu</h5>
                    <p class="small mb-0 opacity-75">Silakan isi data diri pengunjung dengan lengkap.</p>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <form method="POST">
                        <input type="hidden" name="act" value="add_visitor">

                        <div class="mb-4">
                            <h6 class="text-primary fw-bold text-uppercase small ls-1 mb-3"><i class="fas fa-user me-2"></i>Identitas Pengunjung</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-font text-muted"></i></span>
                                        <input type="text" name="nama_tamu" class="form-control" required placeholder="Nama sesuai KTP">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">No. Handphone / WA</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-phone text-muted"></i></span>
                                        <input type="text" name="no_hp" class="form-control" placeholder="08xxx">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-secondary">Asal Instansi / Perusahaan</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-building text-muted"></i></span>
                                        <input type="text" name="instansi" class="form-control" placeholder="Kosongkan jika tamu pribadi">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-light my-4">

                        <div class="mb-4">
                            <h6 class="text-primary fw-bold text-uppercase small ls-1 mb-3"><i class="fas fa-info-circle me-2"></i>Detail Kunjungan</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Bertemu Dengan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-user-tie text-muted"></i></span>
                                        <input type="text" name="bertemu_dengan" class="form-control" required placeholder="Nama Staff / Karyawan">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Keperluan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-comment-alt text-muted"></i></span>
                                        <input type="text" name="keperluan" class="form-control" required placeholder="Contoh: Meeting, Interview...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-primary py-3 rounded-pill fw-bold shadow hover-shadow transition-all">
                                <i class="fas fa-paper-plane me-2"></i> Check-In & Simpan Data
                            </button>
                            <a href="index.php?page=visit_riwayat" class="btn btn-light py-2 rounded-pill text-muted">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>