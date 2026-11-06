<?php
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
session_start();
require_once 'config/database.php';

function clear_app_cookie($name, $is_https) {
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

$user_id = $_SESSION['id_user'] ?? null;
if (!empty($user_id)) {
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
}

session_unset();
session_destroy();

// Hapus token "Auto-login" saja agar tidak ditarik masuk otomatis
if (isset($_COOKIE['remember_sipandu'])) {
    clear_app_cookie('remember_sipandu', $is_https);
}

// Simpan username untuk kemudahan login ulang, tapi bersihkan cookie password lama jika ada.
if (isset($_COOKIE['remember_password'])) {
    clear_app_cookie('remember_password', $is_https);
}

header("Location: login.php");
exit;
?>