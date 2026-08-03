<?php
session_start();
require_once 'config/database.php';

if (isset($_POST['update_akun'])) {
    $userId    = $_SESSION['id_user'] ?? $_SESSION['user_id'];
    $nama      = htmlspecialchars($_POST['nama'] ?? '');
    $email     = htmlspecialchars($_POST['email'] ?? '');
    $no_hp     = htmlspecialchars($_POST['no_hp'] ?? '');
    $bahasa    = htmlspecialchars($_POST['bahasa'] ?? 'id');
    $timezone  = htmlspecialchars($_POST['timezone'] ?? 'Asia/Jakarta');
    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    
    $new_pass  = $_POST['new_pass'] ?? '';
    $conf_pass = $_POST['conf_pass'] ?? '';

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

        // 2. Ganti Foto Profil (Anti-Sampah)
        if (!empty($_FILES['foto_profil']['name'])) {
            $dir = "uploads/profil/";
            $ext = strtolower(pathinfo($_FILES["foto_profil"]["name"], PATHINFO_EXTENSION));
            $file_name = "profil_" . $userId . "_" . time() . "." . $ext;

            // Hapus file lama jika bukan default
            $old = $pdo->prepare("SELECT foto_profil FROM users WHERE id = ?");
            $old->execute([$userId]);
            $resOld = $old->fetch();
            
            if ($resOld['foto_profil'] != 'default.png' && file_exists($dir . $resOld['foto_profil'])) {
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