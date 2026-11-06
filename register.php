<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'config/database.php';

// --- 1. AMBIL DATA DARI DATABASE UNTUK DROPDOWN ---
try {
    // Ambil Role
    $list_role = $pdo->query("SELECT DISTINCT role FROM users WHERE role != '' ORDER BY role ASC")->fetchAll(PDO::FETCH_ASSOC);
    // Ambil Cabang 
    $list_cabang = $pdo->query("SELECT id, nama_cabang FROM cabang ORDER BY nama_cabang ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $list_role = []; 
    $list_cabang = [];
}

$error = "";

if (isset($_POST['register'])) {
    $nama        = htmlspecialchars($_POST['nama_lengkap']);
    $username    = htmlspecialchars($_POST['username']);
    $password    = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role        = $_POST['role'];
    $jabatan     = $_POST['jabatan'];
    $email       = $_POST['email'];
    $no_hp       = $_POST['no_hp'];
    $cabang_id   = $_POST['cabang_id'] ?? null;

    // --- NILAI DEFAULT ---
    $foto_profil = 'default.png';
    $shift_id    = null;

    try {
        // Cek Username Ganda
        $stmtCek = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtCek->execute([$username]);
        if ($stmtCek->rowCount() > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // INSERT SESUAI STRUKTUR TABEL
            $sql = "INSERT INTO users (
                        nama_lengkap, username, password, role, foto_profil, 
                        shift_id, status, jabatan, email, no_hp, 
                        created_at, status_akun, perlu_ganti_pass, cabang_id, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, 'Nonaktif', ?, ?, ?, NOW(), 'Nonaktif', 0, ?, 0)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nama, $username, $password, $role, $foto_profil,
                $shift_id, $jabatan, $email, $no_hp, $cabang_id
            ]);

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                  <script>document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({title:'Pendaftaran Berhasil!', text:'Tunggu approval admin.', icon:'success'}).then(()=>{window.location.href='login.php';});
                  });</script>";
            exit;
        }
    } catch (PDOException $e) { 
        $error = "Error: " . $e->getMessage(); 
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register SIPANDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html { height: 100%; margin: 0; font-family: 'Inter', sans-serif; background: #f8fafc; }
        .login-left-side {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.4) 0%, rgba(10, 88, 202, 0.5) 100%), url('assets/img/background_login.png');
            background-size: cover; background-position: center; color: white; display: flex; align-items: center; padding: 4rem; clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%);
        }
        .form-control, .form-select { border-radius: 8px; background: #f8f9fa; }
        .login-logo { max-width: 120px; }
        .form-card { background: #ffffff; border-radius: 24px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08); border: 1px solid rgba(13, 110, 253, 0.09); padding: 2rem; }
        @media (max-width: 991.98px) {
            .login-left-side { display: none; }
            .register-form-side { background: linear-gradient(rgba(248, 250, 252, 0.9), rgba(248, 250, 252, 0.9)), url('assets/img/background_login.png') center / cover; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 vh-100">
        <div class="col-lg-6 login-left-side">
            <div>
                <i class="fas fa-shield-halved fa-4x mb-4"></i>
                <h1 class="fw-bold">Pendaftaran Personil</h1>
                <p>Silakan lengkapi data sesuai penempatan tugas Anda.</p>
            </div>
        </div>
        <div class="col-lg-6 d-flex align-items-center justify-content-center bg-white p-4 register-form-side" style="overflow-y: auto;">
            <div class="w-100" style="max-width: 550px;">
                <div class="text-center mb-4">
                    <img src="assets/img/logo-target.jpeg" alt="Target Integrated Security Services" style="max-width: 140px; width: 100%;">
                </div>
                <h3 class="fw-bold mb-4 text-center text-lg-start">Buat Akun Baru</h3>
                <?php if($error): ?> <div class="alert alert-danger py-2 small"><?= $error ?></div> <?php endif; ?>
                
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-secondary">NAMA LENGKAP</label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Sesuai KTP" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-secondary">USERNAME</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-secondary">PASSWORD</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-secondary">ROLE / HAK AKSES</label>
                            <select name="role" class="form-select" required>
                                <option value="" disabled selected>-- Pilih --</option>
                                <?php foreach($list_role as $r): ?> <option value="<?= $r['role'] ?>"><?= $r['role'] ?></option> <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-secondary">JABATAN</label>
                            <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Danru" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-secondary">EMAIL</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-secondary">WHATSAPP</label>
                            <input type="number" name="no_hp" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold text-secondary">PENEMPATAN (CABANG)</label>
                            <select name="cabang_id" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Cabang --</option>
                                <?php foreach($list_cabang as $c): ?> <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option> <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" name="register" class="btn btn-primary w-100 fw-bold py-3 shadow-sm">DAFTAR SEKARANG <i class="fas fa-paper-plane ms-2"></i></button>
                            <p class="text-center small mt-3">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold">Masuk Sekarang</a></p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>