<?php
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $is_https ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once 'config/database.php';
require_once 'config/auth.php';

function set_app_cookie($name, $value, $expires_at, $is_https) {
    setcookie($name, $value, [
        'expires' => $expires_at,
        'path' => '/',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function clear_app_cookie($name, $is_https) {
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// --- AMBIL DATA DARI COOKIE UNTUK AUTO-FILL (Konsep Seperti Google) ---
$saved_user = $_COOKIE['remember_username'] ?? '';
$is_remembered = isset($_COOKIE['remember_username']);

// --- 1. LOGIKA AUTO-LOGIN (Jika Token Masih Aktif di Browser) ---
if (!isset($_SESSION['id_user']) && isset($_COOKIE['remember_sipandu'])) {
    $token = $_COOKIE['remember_sipandu'];
    $token_hash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token_hash]);
    $user = $stmt->fetch();

    // Kompatibilitas token lama (plaintext) lalu migrasi otomatis ke hash.
    if (!$user) {
        $legacy_stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
        $legacy_stmt->execute([$token]);
        $legacy_user = $legacy_stmt->fetch();
        if ($legacy_user) {
            $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token_hash, $legacy_user['id']]);
            $user = $legacy_user;
        }
    }

function record_login_log($pdo, $username, $status, $keterangan) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $stmt = $pdo->prepare("INSERT INTO login_logs (username, ip_address, user_agent, status, keterangan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$username, $ip, $ua, $status, $keterangan]);
    } catch (Exception $e) {}
}

if ($user && (int)$user['is_active'] === 1) {
    $_SESSION['id_user'] = $user['id'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];
    record_login_log($pdo, $user['username'], 'Success', 'Auto-Login via Cookie');
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
// Cloudflare Turnstile keys
$site_key = getenv('TURNSTILE_SITE_KEY') ?: '0x4AAAAAACeCjGGFP7HSgL54';
$secret_key = getenv('TURNSTILE_SECRET_KEY') ?: '0x4AAAAAACeCjA0JWYmygin_ttZapQENAVg';
$turnstile_configured = ($site_key !== '' && $secret_key !== '');

if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}

if (isset($_POST['login'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_login'], $csrf_token)) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";
    }

    if (empty($error) && !$turnstile_configured) {
        $error = "Konfigurasi keamanan login belum lengkap. Hubungi Admin.";
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    // Verifikasi Keamanan Cloudflare
    $verification = ['success' => false];
    if (empty($error) && !empty($turnstile_response)) {
        $payload = [
            'secret' => $secret_key,
            'response' => $turnstile_response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 10
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response !== false) {
                $decoded = json_decode($response, true);
                if (is_array($decoded)) {
                    $verification = $decoded;
                }
            }
        } else {
            $post_fields = http_build_query($payload);
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                        . "Content-Length: " . strlen($post_fields) . "\r\n",
                    'content' => $post_fields,
                    'timeout' => 10
                ]
            ]);
            $response = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);

            if ($response !== false) {
                $decoded = json_decode($response, true);
                if (is_array($decoded)) {
                    $verification = $decoded;
                }
            }
        }
    }

    if (empty($error) && empty($verification['success'])) {
        $error = "Verifikasi Keamanan Gagal.";
        record_login_log($pdo, $username, 'Failed', 'Verifikasi Keamanan Cloudflare Gagal');
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
                    record_login_log($pdo, $username, 'Frozen', 'Akun terkunci karena percobaan berulang');
                } else {
                    $pdo->prepare("UPDATE users SET failed_attempts = 0 WHERE id = ?")->execute([$user['id']]);
                }
            }

            if (empty($error)) {
                if (password_verify($password, $user['password'])) {
                    if ((int)$user['is_active'] !== 1) {
                        $error = "Akun dinonaktifkan oleh Admin.";
                        record_login_log($pdo, $username, 'Failed', 'Akun Nonaktif / Dinonaktifkan Admin');
                    } else {
                        // LOGIN BERHASIL
                        session_regenerate_id(true);
                        $pdo->prepare("UPDATE users SET failed_attempts = 0, last_failed_login = NULL, last_seen = NOW() WHERE id = ?")->execute([$user['id']]);
                        $_SESSION['id_user'] = $user['id'];
                        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                        $_SESSION['role'] = $user['role'] ?? 'Anggota';

                        record_login_log($pdo, $username, 'Success', 'Login Berhasil');

                        // --- LOGIKA REMEMBER ME (SIMPAN KE COOKIE) ---
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $token_hash = hash('sha256', $token);
                            set_app_cookie('remember_sipandu', $token, time() + (86400 * 30), $is_https);
                            $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token_hash, $user['id']]);
                            set_app_cookie('remember_username', $username, time() + (86400 * 30), $is_https);
                            clear_app_cookie('remember_password', $is_https);
                        } else {
                            clear_app_cookie('remember_sipandu', $is_https);
                            clear_app_cookie('remember_username', $is_https);
                            clear_app_cookie('remember_password', $is_https);
                        }

                        header("Location: index.php");
                        exit;
                    }
                } else {
                    $attempts = (int)$user['failed_attempts'] + 1;
                    $pdo->prepare("UPDATE users SET failed_attempts = ?, last_failed_login = NOW() WHERE id = ?")->execute([$attempts, $user['id']]);
                    $error = "Username atau Password salah!";
                    record_login_log($pdo, $username, 'Failed', 'Password salah (Percobaan ke-' . $attempts . ')');
                }
            }
        } else {
            $error = "Username tidak terdaftar.";
            record_login_log($pdo, $username, 'Failed', 'Username tidak terdaftar');
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<style>
    body, html { height: 100%; margin: 0; font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; background: #f8fafc; color: #0f172a; }
    .h-100vh { min-height: 100vh; }
    
    .login-left-side {
        background: linear-gradient(135deg, rgba(10, 35, 86, 0.84), rgba(37, 99, 235, 0.58)), url('assets/img/background_login.png');
        background-size: cover;
        background-position: center;
        color: #ffffff; 
        display: flex; 
        flex-direction: column; 
        justify-content: center; 
        padding: 5rem 4rem; 
        position: relative;
        overflow: hidden;
    }

    .login-left-side::before {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
        top: -100px;
        right: -100px;
    }

    .login-right-side { 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        background: #f8fafc; 
        padding: 2rem;
    }

    .login-form-wrapper { 
        width: 100%; 
        max-width: 440px; 
        padding: 2.5rem; 
        background: #ffffff; 
        border-radius: 18px; 
        box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04); 
        border: 1px solid #e2e8f0; 
    }

    .login-logo { 
        max-width: 48px; 
        height: auto;
    }

    .form-control { 
        border-radius: 8px; 
        padding: 10px 14px; 
        background: #ffffff !important; 
        border: 1px solid #cbd5e1 !important; 
        color: #0f172a !important; 
        font-size: 0.9rem;
        transition: all 0.2s ease; 
    }

    .form-control:focus { 
        background: #ffffff !important; 
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important; 
        border-color: #2563eb !important; 
    }

    .input-group-text { 
        background-color: #f8fafc !important; 
        border: 1px solid #cbd5e1 !important; 
        color: #64748b !important; 
        border-radius: 8px;
    }

    .btn-login { 
        border-radius: 8px; 
        padding: 11px 16px; 
        font-weight: 700; 
        background: #2563eb !important; 
        border: none; 
        color: #ffffff !important; 
        transition: all 0.2s ease; 
        letter-spacing: 0.02em; 
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }

    .btn-login:hover { 
        background: #1d4ed8 !important; 
        transform: translateY(-1px); 
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35); 
    }

    .user-validation-top { 
        position: fixed; 
        top: 20px; 
        right: 20px; 
        background: #ecfdf5; 
        color: #059669; 
        padding: 6px 16px; 
        border-radius: 9999px; 
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06); 
        border: 1px solid #a7f3d0; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        z-index: 999; 
        font-size: 0.78rem;
        font-weight: 700;
    }
    
    @media (max-width: 991.98px) { 
        .login-left-side { display: none; } 
        .login-right-side { min-height: 100vh; padding: 1.5rem; background: linear-gradient(rgba(248, 250, 252, 0.88), rgba(248, 250, 252, 0.88)), url('assets/img/background_login.png') center / cover; } 
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
            <div></div>
        </div>

        <div class="col-lg-5 login-right-side">
            <div class="login-form-wrapper">
                <div class="mb-4 text-center text-lg-start">
                    <img src="assets/img/logo-target.jpeg" alt="Target Integrated Security Services" class="login-logo mb-3">
                    <h2 class="fw-bold mb-1">Login Petugas</h2>
                    <p class="text-muted">Gunakan akun personil yang terdaftar.</p>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_login']) ?>">
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
                                placeholder="Masukkan password" required>
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
                        <?php if ($turnstile_configured): ?>
                            <div class="cf-turnstile" data-sitekey="<?= $site_key ?>" data-size="flexible"></div>
                        <?php else: ?>
                            <div class="alert alert-warning w-100 mb-0">Turnstile belum dikonfigurasi di environment server.</div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary btn-login w-100 mb-4" <?= $turnstile_configured ? '' : 'disabled' ?>>
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