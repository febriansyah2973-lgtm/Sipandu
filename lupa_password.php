<?php
require_once 'config/database.php';

$message = ""; $status = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $email = htmlspecialchars($_POST['email']); // Kunci ketiga
    
    // Verifikasi 3 Kunci sekaligus
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND nama_lengkap = ? AND email = ?");
    $stmt->execute([$username, $nama_lengkap, $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Password default setelah reset
        $password_baru = password_hash('12345678', PASSWORD_BCRYPT);
        
        // Update password dan reset percobaan gagal
        $update = $pdo->prepare("UPDATE users SET password = ?, failed_attempts = 0 WHERE id = ?");
        $update->execute([$password_baru, $user['id']]);

        // Catat Audit Trail (Log)
        $log_msg = "Self-Reset Password Berhasil (3 Kunci Verifikasi Match)";
        $stmt_log = $pdo->prepare("INSERT INTO login_logs (username, ip_address, status, keterangan) VALUES (?, ?, 'Success', ?)");
        $stmt_log->execute([$username, $_SERVER['REMOTE_ADDR'], $log_msg]);

        $status = "success";
        $message = "<b>Verifikasi Berhasil!</b><br>Password Anda telah di-reset menjadi: <span class='badge bg-primary fs-6'>12345678</span><br><small>Segera ganti password Anda setelah login.</small>";
    } else {
        $status = "danger";
        $message = "<b>Data Tidak Cocok!</b><br>Kombinasi Username, Nama, dan Email tidak ditemukan. Silakan hubungi Admin Pusat.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SIPANDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .reset-card { width: 420px; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .form-control { border-radius: 10px; padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; }
        .btn-primary { border-radius: 10px; padding: 12px; font-weight: bold; }
    </style>
</head>
<body>

<div class="reset-card text-center">
    <div class="text-primary mb-3"><i class="fas fa-user-shield fa-3x"></i></div>
    <h4 class="fw-bold mb-1">Pemulihan Akun</h4>
    <p class="text-muted small mb-4">Lengkapi 3 data identitas untuk reset password.</p>

    <?php if($message !== ""): ?>
        <div class="alert alert-<?= $status ?> small mb-4 py-3 shadow-sm"><?= $message ?></div>
        <?php if($status == 'success'): ?>
            <a href="login.php" class="btn btn-primary w-100">KEMBALI KE LOGIN</a>
        <?php else: ?>
            <a href="lupa_password.php" class="btn btn-outline-secondary btn-sm w-100">COBA LAGI</a>
        <?php endif; ?>
    <?php else: ?>
        <form method="POST">
            <div class="mb-3 text-start">
                <label class="small fw-bold text-secondary mb-1">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username Anda" required autocomplete="off">
            </div>
            <div class="mb-3 text-start">
                <label class="small fw-bold text-secondary mb-1">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama sesuai ID Card" required>
            </div>
            <div class="mb-3 text-start">
                <label class="small fw-bold text-secondary mb-1">Email Terdaftar</label>
                <input type="email" name="email" class="form-control" placeholder="Alamat email Anda" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 shadow-sm mt-2">RESET PASSWORD SEKARANG</button>
            
            <div class="mt-4">
                <a href="login.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Batal</a>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>