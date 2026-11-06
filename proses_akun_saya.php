<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';

auth_session_start();
$userId = auth_require_login('index.php?page=login');

if (isset($_POST['update_akun'])) {
    if (!csrf_validate_request()) {
        http_response_code(419);
        exit('CSRF token tidak valid');
    }

    $nama      = trim((string)($_POST['nama'] ?? ''));
    $email     = trim((string)($_POST['email'] ?? ''));
    $no_hp     = trim((string)($_POST['no_hp'] ?? ''));
    $bahasa    = trim((string)($_POST['bahasa'] ?? 'id'));
    $timezone  = trim((string)($_POST['timezone'] ?? 'Asia/Jakarta'));
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    
    $new_pass  = $_POST['new_pass'] ?? '';
    $conf_pass = $_POST['conf_pass'] ?? '';

    if ($nama === '') {
        header("Location: index.php?page=akun_saya&status=invalid");
        exit;
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?page=akun_saya&status=invalid_email");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Update Data Dasar & Preferensi
        $sql = "UPDATE users SET 
                    nama_lengkap = ?, 
                    email = ?, 
                    no_hp = ?, 
                    bahasa = ?, 
                    timezone = ?, 
                    dark_mode = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nama, $email, $no_hp, $bahasa, $timezone, $dark_mode, $userId]);
        $_SESSION['nama_lengkap'] = $nama;

        // 2. Ganti Foto Profil (Anti-Sampah)
        if (!empty($_FILES['foto_profil']['name'])) {
            $dir = "uploads/profil/";
            $ext = strtolower(pathinfo($_FILES["foto_profil"]["name"], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowedExt, true) || (int)($_FILES['foto_profil']['size'] ?? 0) > 2 * 1024 * 1024) {
                $pdo->rollBack();
                header("Location: index.php?page=akun_saya&status=invalid_photo");
                exit;
            }

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $file_name = "profil_" . $userId . "_" . time() . "." . $ext;

            // Hapus file lama jika bukan default
            $old = $pdo->prepare("SELECT foto_profil FROM users WHERE id = ?");
            $old->execute([$userId]);
            $resOld = $old->fetch();
            
            if (!empty($resOld['foto_profil']) && $resOld['foto_profil'] != 'default.png' && file_exists($dir . $resOld['foto_profil'])) {
                unlink($dir . $resOld['foto_profil']);
            }

            if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $dir . $file_name)) {
                $pdo->prepare("UPDATE users SET foto_profil = ? WHERE id = ?")->execute([$file_name, $userId]);
            }
        }

        // 3. Update Password
        if (!empty($new_pass)) {
            if ($new_pass === $conf_pass) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $userId]);
            } else {
                header("Location: index.php?page=akun_saya&status=mismatch");
                exit;
            }
        }

        $pdo->commit();
        header("Location: index.php?page=akun_saya&status=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: index.php?page=akun_saya&status=error");
        exit;
    }
}

header('Location: index.php?page=akun_saya');
exit;