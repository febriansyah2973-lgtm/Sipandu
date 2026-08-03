<!-- layout/bottom_nav.php -->
<?php
// Navigasi bawah khusus tampilan mobile
?>
<div class="d-md-none fixed-bottom bg-white border-top shadow-lg py-2 px-3">
    <div class="d-flex justify-content-around text-center">
        <a href="index.php?page=dashboard" class="text-primary text-decoration-none small">
            <i class="fas fa-home d-block fa-lg mb-1"></i> Dashboard
        </a>
        <a href="index.php?page=patroli_scan" class="text-secondary text-decoration-none small">
            <i class="fas fa-qrcode d-block fa-lg mb-1"></i> Patroli
        </a>
        <a href="index.php?page=laporan_aktivitas" class="text-secondary text-decoration-none small">
            <i class="fas fa-file-alt d-block fa-lg mb-1"></i> Log
        </a>
        <a href="index.php?page=akun_saya" class="text-secondary text-decoration-none small">
            <i class="fas fa-user d-block fa-lg mb-1"></i> Profil
        </a>
    </div>
</div>