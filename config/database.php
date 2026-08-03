<?php
date_default_timezone_set('Asia/Jakarta'); 

$host = 'localhost';
$db   = 'db_sipandu';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi Gagal: " . $e->getMessage());
}

if (!function_exists('hasAccess')) {
    function hasAccess($role, $page_slug, $pdo) {
        if ($role === 'Admin') return true;

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM role_permissions rp
            JOIN master_menu mm ON rp.menu_id = mm.id
            WHERE rp.role_name = ? AND mm.page_slug = ?
        ");
        $stmt->execute([$role, $page_slug]);
        return $stmt->fetchColumn() > 0;
    }
}