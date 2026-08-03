<?php
// 1. Proteksi & Identifikasi User
$userId = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? null; 
if (!$userId) { echo "<script>window.location='login.php';</script>"; exit; }

// 2. Ambil Data User (JOIN Cabang & Shift sesuai image_e803c0.jpg)
$sql = "SELECT u.*, c.nama_cabang, s.nama_shift 
        FROM users u 
        LEFT JOIN cabang c ON u.cabang_id = c.id 
        LEFT JOIN shifts s ON u.shift_id = s.id 
        WHERE u.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Logic Folder Foto
$fotoPath = (!empty($user['foto_profil']) && file_exists('uploads/profil/'.$user['foto_profil'])) 
            ? 'uploads/profil/'.$user['foto_profil'] 
            : 'uploads/profil/default.png'; 
?>

<div class="container-fluid px-4 py-4 animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Pengaturan Profil & Aplikasi</h3>
            <p class="text-secondary small mb-0">Kelola identitas digital, preferensi waktu, dan keamanan SIPANDU Anda.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">
                <i class="fas fa-check-circle me-1"></i> VALIDASI USER AKTIF
            </span>
        </div>
    </div>

    <form action="proses_akun_saya.php" method="POST" enctype="multipart/form-data">
        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 mb-4 text-center" style="border-radius: 24px;">
                    <div class="position-relative d-inline-block mx-auto mb-3 shadow-sm" style="width: 140px; height: 140px; border-radius: 35px; overflow: hidden; border: 5px solid #fff; background: #eee;">
                        <img id="previewFoto" src="<?= $fotoPath ?>" class="w-100 h-100" style="object-fit: cover;">
                    </div>
                    
                    <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($user['nama_lengkap'] ?? 'N/A') ?></h5>
                    <small class="text-primary fw-bold">@<?= htmlspecialchars($user['username'] ?? 'username') ?></small>
                    
                    <div class="mt-3">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 text-uppercase" style="font-size: 0.7rem;">
                            <i class="fas fa-user-shield me-1"></i> <?= htmlspecialchars($user['role'] ?? 'Petugas') ?>
                        </span>
                    </div>

                    <div class="row g-2 mt-4 text-start">
                        <div class="col-6">
                            <div class="bg-light p-2 rounded-3 border-start border-primary border-4">
                                <small class="text-muted d-block" style="font-size: 0.65rem;">Unit/Cabang</small>
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($user['nama_cabang'] ?? 'Pusat') ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-2 rounded-3 border-start border-info border-4">
                                <small class="text-muted d-block" style="font-size: 0.65rem;">Shift Tugas</small>
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($user['nama_shift'] ?? 'Harian') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-4" style="border-radius: 24px;">
                    <h6 class="fw-bold mb-4 text-primary"><i class="fas fa-sliders-h me-2"></i> Preferensi Aplikasi</h6>
                    
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-4 border mb-4">
                        <span class="small fw-bold text-dark"><i class="fas fa-moon me-2 text-primary"></i> Mode Gelap</span>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="dark_mode" id="darkSwitch" value="1" <?= ($user['dark_mode'] ?? 0) == 1 ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-2">Bahasa Sistem</label>
                        <select name="bahasa" class="form-select bg-light border-0 py-2 fw-bold">
                            <option value="id" <?= ($user['bahasa'] ?? 'id') == 'id' ? 'selected' : '' ?>>Bahasa Indonesia</option>
                            <option value="en" <?= ($user['bahasa'] ?? '') == 'en' ? 'selected' : '' ?>>English (US)</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-2">Zona Waktu (Timezone)</label>
                        <select name="timezone" class="form-select bg-light border-0 py-2 fw-bold">
                            <option value="Asia/Jakarta" <?= ($user['timezone'] ?? 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : '' ?>>WIB - Jakarta</option>
                            <option value="Asia/Makassar" <?= ($user['timezone'] ?? '') == 'Asia/Makassar' ? 'selected' : '' ?>>WITA - Makassar</option>
                            <option value="Asia/Jayapura" <?= ($user['timezone'] ?? '') == 'Asia/Jayapura' ? 'selected' : '' ?>>WIT - Jayapura</option>
                        </select>
                    </div>
                </div>
            </div> 

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 24px;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary-subtle p-2 rounded-3 me-3 text-primary">
                            <i class="fas fa-id-card fa-lg"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Informasi Pribadi & Kontak</h5>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">NAMA LENGKAP</label>
                            <input type="text" name="nama" class="form-control border-0 bg-light py-2" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">USERNAME (ID)</label>
                            <input type="text" class="form-control border-0 bg-light py-2" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly style="background: #e9ecef !important;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">ALAMAT EMAIL</label>
                            <input type="email" name="email" class="form-control border-0 bg-light py-2" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">NOMOR WHATSAPP</label>
                            <input type="text" name="no_hp" class="form-control border-0 bg-light py-2" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                        </div>
                        <div class="col-12 mt-3">
                            <label class="form-label small fw-bold text-secondary">GANTI FOTO PROFIL</label>
                            <input type="file" name="foto_profil" id="inputFoto" class="form-control border-0 bg-light py-2" accept="image/png, image/jpeg">
                        </div>
                    </div>

                    <hr class="my-5 opacity-25">

                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-danger-subtle p-2 rounded-3 me-3 text-danger">
                            <i class="fas fa-shield-alt fa-lg"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Keamanan Password</h5>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">PASSWORD BARU</label>
                            <input type="password" name="new_pass" class="form-control border-0 bg-light py-2" placeholder="Kosongkan jika tetap">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">KONFIRMASI PASSWORD</label>
                            <input type="password" name="conf_pass" class="form-control border-0 bg-light py-2" placeholder="Ulangi password baru">
                        </div>
                    </div>

                    <div class="text-end mt-5">
                        <button type="submit" name="update_akun" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow">
                            SIMPAN PERUBAHAN <i class="fas fa-save ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Live Preview
    document.getElementById('inputFoto').onchange = function () {
        const [file] = this.files;
        if (file) {
            document.getElementById('previewFoto').src = URL.createObjectURL(file);
        }
    };

    // Handler SweetAlert & URL Cleanup
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === 'success') {
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Data profil Anda telah diperbarui.', timer: 2500, showConfirmButton: false });
        } else if (status === 'mismatch') {
            Swal.fire({ icon: 'warning', title: 'Gagal!', text: 'Konfirmasi password tidak cocok.' });
        } else if (status === 'error') {
            Swal.fire({ icon: 'error', title: 'Error!', text: 'Terjadi kesalahan sistem.' });
        }

        if (status) {
            window.history.replaceState({}, document.title, window.location.pathname + "?page=akun_saya");
        }
    });
</script>