<?php
// Deteksi status force dari index.php
$is_force = (isset($_GET['status']) && $_GET['status'] == 'force') || (isset($_SESSION['perlu_ganti_pass']) && $_SESSION['perlu_ganti_pass'] == 1);
$error = ""; 

if (isset($_POST['update_password'])) {
    $new_pass = $_POST['new_pass'];
    $conf_pass = $_POST['conf_pass'];

    if (strlen($new_pass) < 8) {
        $error = "Password minimal 8 karakter!";
    } elseif ($new_pass !== $conf_pass) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif ($new_pass === '12345678') {
        $error = "Jangan gunakan password default '12345678'.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, perlu_ganti_pass = 0 WHERE id = ?");
        
        if ($stmt->execute([$hashed, $_SESSION['id_user']])) {
            unset($_SESSION['perlu_ganti_pass']); 
            
            // GUNAKAN SWEETALERT2 YANG ELEGAN
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Update Berhasil!',
                        text: 'Sandi Anda telah diperbarui. Silakan login ulang.',
                        icon: 'success',
                        confirmButtonColor: '#0d6efd',
                        confirmButtonText: 'LOGOUT & LOGIN ULANG',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'logout.php';
                        }
                    });
                });
            </script>";
        }
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="card border-0 shadow-lg p-4 animate-fade-up" style="width: 450px; border-radius: 25px;">
        <div class="text-center mb-4">
            <div class="bg-danger-subtle text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                <i class="fas fa-shield-virus fa-2x"></i>
            </div>
            <h4 class="fw-bold text-dark">Keamanan Akun</h4>
            <p class="text-secondary small">
                <?= $is_force ? "Akses terkunci! Anda <b>wajib</b> mengganti password default untuk melindungi data personil." : "Perbarui password Anda secara berkala demi keamanan." ?>
            </p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger py-2 small border-0 mb-3 text-center" style="border-radius: 10px;">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="formGantiPass">
            <div class="mb-3">
                <label class="small fw-bold text-secondary mb-2">Password Baru</label>
                <div class="input-group">
                    <input type="password" name="new_pass" id="new_pass" class="form-control rounded-start-3 py-2 border-end-0" placeholder="Minimal 8 karakter" required>
                    <span class="input-group-text bg-white border-start-0 rounded-end-3" onclick="toggleSee()" style="cursor: pointer;">
                        <i class="fas fa-eye text-muted" id="eyeIcon"></i>
                    </span>
                </div>
                <div class="progress mt-2" style="height: 5px;">
                    <div id="pass-strength" class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <small id="pass-feedback" class="text-muted" style="font-size: 0.7rem;"></small>
            </div>

            <div class="mb-4">
                <label class="small fw-bold text-secondary mb-2">Ulangi Password Baru</label>
                <input type="password" name="conf_pass" class="form-control rounded-3 py-2" placeholder="Konfirmasi sandi baru" required>
            </div>

            <button type="submit" name="update_password" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                SIMPAN & LOGIN ULANG <i class="fas fa-sign-in-alt ms-2"></i>
            </button>
        </form>
    </div>
</div>

<script>
    // JS Logic Bapak (Strength Meter & Eye Toggle)
    const passInput = document.getElementById('new_pass');
    const strengthBar = document.getElementById('pass-strength');
    const feedback = document.getElementById('pass-feedback');

    passInput.addEventListener('input', () => {
        const val = passInput.value;
        let strength = 0;
        if (val.length >= 8) strength += 25;
        if (val.match(/[a-z]/) && val.match(/[A-Z]/)) strength += 25;
        if (val.match(/[0-9]/)) strength += 25;
        if (val.match(/[^a-zA-Z0-9]/)) strength += 25;
        strengthBar.style.width = strength + '%';
        if (strength <= 25) { strengthBar.className='progress-bar bg-danger'; feedback.innerText='Sangat Lemah'; }
        else if (strength <= 50) { strengthBar.className='progress-bar bg-warning'; feedback.innerText='Lemah'; }
        else if (strength <= 75) { strengthBar.className='progress-bar bg-info'; feedback.innerText='Cukup Kuat'; }
        else { strengthBar.className='progress-bar bg-success'; feedback.innerText='Sangat Kuat'; }
    });

    function toggleSee() {
        const pass = document.getElementById('new_pass');
        const icon = document.getElementById('eyeIcon');
        if (pass.type === 'password') {
            pass.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            pass.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>