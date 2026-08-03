<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if (isset($_POST['ganti'])) {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if ($pass1 !== $pass2) {
        $error = "Password konfirmasi tidak sama!";
    } elseif (strlen($pass1) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $id_user = $_SESSION['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET password = ?, perlu_ganti_pass = 0 WHERE id = ?");
        $stmt->execute([$hash, $id_user]);
        $_SESSION['perlu_ganti_pass'] = 0;

        echo "<script>alert('Password berhasil diubah! Silakan lanjutkan.'); window.location='index.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wajib Ganti Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-warning d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    
    <div class="card shadow p-4" style="width: 450px;">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-danger">⚠️ Keamanan Akun</h3>
            <p class="text-muted">Password Anda baru saja direset oleh Admin.<br>Demi keamanan, Anda <b>WAJIB</b> mengganti password sekarang.</p>
        </div>

        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Password Baru</label>
                <input type="password" name="pass1" class="form-control" required placeholder="Minimal 6 karakter">
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Ulangi Password Baru</label>
                <input type="password" name="pass2" class="form-control" required placeholder="Ketik ulang password baru">
            </div>

            <button type="submit" name="ganti" class="btn btn-dark w-100">SIMPAN PASSWORD BARU</button>
        </form>
        
        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none text-muted small">Batal & Logout</a>
        </div>
    </div>

</body>
</html>