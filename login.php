<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'config/database.php';

// --- AMBIL DATA DARI COOKIE UNTUK AUTO-FILL (Konsep Seperti Google) ---
$saved_user = $_COOKIE['remember_username'] ?? '';
$saved_pass = $_COOKIE['remember_password'] ?? '';
$is_remembered = isset($_COOKIE['remember_username']);

// --- 1. LOGIKA AUTO-LOGIN (Jika Token Masih Aktif di Browser) ---
if (!isset($_SESSION['id_user']) && isset($_COOKIE['remember_sipandu'])) {
    $token = $_COOKIE['remember_sipandu'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user && (int)$user['is_active'] === 1) {
        $_SESSION['id_user'] = $user['id'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    }
}

// Redirect jika sudah login
if (isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

// --- 2. PROSES LOGIN ---
$error = "";
$site_key = "0x4AAAAAACeCjGGFP7HSgL54";
$secret_key = "0x4AAAAAACeCjA0JWYmygin_ttZapQENAVg";

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    // Verifikasi Keamanan Cloudflare
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['secret' => $secret_key, 'response' => $turnstile_response, 'remoteip' => $_SERVER['REMOTE_ADDR']]
    ]);
    $verification = json_decode(curl_exec($ch), true);

    if (!$verification['success']) {
        $error = "Verifikasi Keamanan Gagal.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Cek Brute Force (Percobaan Gagal)
            if ((int)$user['failed_attempts'] >= 3) {
                $last_failed = strtotime($user['last_failed_login']);
                if ((time() - $last_failed) < 60) {
                    $error = "Akun terkunci! Coba lagi dalam 60 detik.";
                } else {
                    $pdo->prepare("UPDATE users SET failed_attempts = 0 WHERE id = ?")->execute([$user['id']]);
                }
            }

            if (empty($error)) {
                if (password_verify($password, $user['password'])) {
                    if ((int)$user['is_active'] !== 1) {
                        $error = "Akun dinonaktifkan oleh Admin.";
                    } else {
                        // LOGIN BERHASIL
                        $_SESSION['id_user'] = $user['id'];
                        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                        $_SESSION['role'] = $user['role'] ?? 'Anggota';

                        // --- LOGIKA REMEMBER ME (SIMPAN KE COOKIE) ---
                        if ($remember) {
                            // A. Token untuk Auto-Login
                            $token = bin2hex(random_bytes(32));
                            setcookie('remember_sipandu', $token, time() + (86400 * 30), "/", "", false, true);
                            $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token, $user['id']]);

                            // B. Username & Password untuk Auto-fill (Isi Otomatis)
                            setcookie('remember_username', $username, time() + (86400 * 30), "/");
                            setcookie('remember_password', $password, time() + (86400 * 30), "/");
                        } else {
                            // Jika tidak dicentang, hapus semua jejak cookie
                            setcookie('remember_sipandu', '', time() - 3600, "/");
                            setcookie('remember_username', '', time() - 3600, "/");
                            setcookie('remember_password', '', time() - 3600, "/");
                        }

                        header("Location: index.php");
                        exit;
                    }
                } else {
                    $attempts = (int)$user['failed_attempts'] + 1;
                    $pdo->prepare("UPDATE users SET failed_attempts = ?, last_failed_login = NOW() WHERE id = ?")->execute([$attempts, $user['id']]);
                    $error = "Username atau Password salah!";
                }
            }
        } else { $error = "Username tidak terdaftar."; }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login SIPANDU - Integrated Security</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
    body, html { height: 100%; margin: 0; font-family: 'Inter', sans-serif; background: #fff; }
    .h-100vh { min-height: 100vh; }
    
    .login-left-side {
        /* Saya ubah dari 0.8 & 0.9 menjadi 0.4 & 0.5 agar lebih transparan */
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.4) 0%, rgba(10, 88, 202, 0.5) 100%), url('assets/img/background_login.png');
        background-size: cover; 
        background-position: center; 
        color: white; 
        display: flex; 
        flex-direction: column; 
        justify-content: center; 
        padding: 4rem; 
        clip-path: polygon(0 0, 100% 0, 85% 100%, 0% 100%);
        
        /* Tambahan: Efek Blur halus biar lebih mewah (Glassmorphism) */
        backdrop-filter: blur(2px); 
    }

    .login-right-side { display: flex; align-items: center; justify-content: center; background: #fff; }
    .login-form-wrapper { width: 100%; max-width: 420px; padding: 2rem; }
    .form-control { border-radius: 12px; padding: 12px; background: #f8f9fa; border: 1px solid #eee; transition: 0.3s; }
    .form-control:focus { background: #fff; box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1); border-color: #0d6efd; }
    .btn-login { border-radius: 12px; padding: 14px; font-weight: 700; background: #0d6efd; border: none; color: #fff; transition: 0.3s; }
    .btn-login:hover { background: #0b5ed7; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3); }
    .user-validation-top { position: fixed; top: 20px; right: 20px; background: #fff; padding: 8px 16px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 2px solid #28a745; display: flex; align-items: center; gap: 10px; z-index: 999; }
    
    @media (max-width: 991.98px) { 
        .login-left-side { display: none; } 
        .login-right-side { min-height: 100vh; padding: 1.5rem; background: #fdfdfd; } 
    }
</style>
</head>
<body>

<?php if($error): ?>
<script> Swal.fire({ icon: 'error', title: 'Login Gagal', text: '<?= $error ?>', confirmButtonColor: '#0d6efd' }); </script>
<?php endif; ?>

<div class="user-validation-top">
    <div class="spinner-grow spinner-grow-sm text-success" role="status"></div>
    <span class="small fw-bold">VALIDASI AKTIF</span>
</div>

<div class="container-fluid p-0">
    <div class="row g-0 h-100vh">
        <div class="col-lg-7 login-left-side">
            <div>
                <div class="mb-4"><i class="fas fa-shield-halved fa-5x"></i></div>
                <h1 class="display-4 fw-bold text-white">SIPANDU</h1>
                <p class="fs-5 opacity-75 text-white">System Integrated Security & Patrol Digital.</p>
            </div>
        </div>

        <div class="col-lg-5 login-right-side">
            <div class="login-form-wrapper">
                <div class="mb-4 text-center text-lg-start">
                    <h2 class="fw-bold mb-1">Login Petugas</h2>
                    <p class="text-muted">Gunakan akun personil yang terdaftar.</p>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 px-3"><i class="fas fa-user text-muted small"></i></span>
                            <input type="text" name="username" class="form-control border-start-0 ps-0" 
                                   placeholder="Masukkan username" value="<?= htmlspecialchars($saved_user) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 px-3"><i class="fas fa-lock text-muted small"></i></span>
                            <input type="password" name="password" id="inputPassword" class="form-control border-start-0 ps-0" 
                                   placeholder="Masukkan password" value="<?= htmlspecialchars($saved_pass) ?>" required>
                            <span class="input-group-text bg-white border-start-0" style="cursor: pointer;" onclick="togglePassword()">
                                <i class="fas fa-eye text-muted" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" <?= $is_remembered ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="remember">Ingat Saya</label>
                        </div>
                        <a href="lupa_password.php" class="small text-decoration-none fw-bold">Lupa Password?</a>
                    </div>

                    <div class="mb-4 d-flex justify-content-center">
                        <div class="cf-turnstile" data-sitekey="<?= $site_key ?>" data-size="flexible"></div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary btn-login w-100 mb-4">
                        MASUK SEKARANG <i class="fas fa-arrow-right ms-2"></i>
                    </button>

                    <div class="text-center border-top pt-3">
                        <p class="small text-muted mb-0">Belum punya akun? <a href="register.php" class="text-primary fw-bold text-decoration-none">Daftar Personil</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const passInput = document.getElementById('inputPassword');
        const icon = document.getElementById('eyeIcon');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
</body>
</html>