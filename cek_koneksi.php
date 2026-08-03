<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>--- SIPANDU Connection Diagnostic ---</h3>";

// 1. Cek Extension cURL
if (function_exists('curl_version')) {
    echo "<p style='color:green;'>[OK] Extension cURL aktif.</p>";
} else {
    echo "<p style='color:red;'>[ERROR] Extension cURL MATI. Aktifkan di Laragon!</p>";
}

// 2. Cek Koneksi ke Cloudflare (Penting untuk Turnstile)
$ch = curl_init("https://challenges.cloudflare.com/turnstile/v0/siteverify");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Sesuai saran sebelumnya
$res = curl_exec($ch);

if ($res === false) {
    echo "<p style='color:red;'>[ERROR] Server gagal menghubungi Cloudflare: " . curl_error($ch) . "</p>";
    echo "<p><i>Saran: Cek Firewall Outbound atau koneksi internet server.</i></p>";
} else {
    echo "<p style='color:green;'>[OK] Koneksi ke Cloudflare lancar.</p>";
}
curl_close($ch);

// 3. Cek Database
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SELECT * FROM users LIMIT 1");
    echo "<p style='color:green;'>[OK] Koneksi Database lancar.</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>[ERROR] Database bermasalah: " . $e->getMessage() . "</p>";
}
?>