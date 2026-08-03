<?php
session_start();
session_destroy();

// Hapus token "Auto-login" saja agar tidak ditarik masuk otomatis
if (isset($_COOKIE['remember_sipandu'])) {
    setcookie('remember_sipandu', '', time() - 3600, "/");
}

// JANGAN hapus remember_username & remember_password di sini
// supaya pas balik ke login.php, form-nya tetap terisi otomatis.

header("Location: login.php");
exit;
?>