<?php
session_start();

// 1. CEK LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// 2. LOAD KONEKSI & HELPER
require_once 'config/database.php';
require_once 'config/helper_notif.php';

// --- 3. AMBIL DATA USER & PREFERENSI ---
$stmtUser = $pdo->prepare("SELECT u.*, c.nama_cabang 
                           FROM users u 
                           LEFT JOIN cabang c ON u.cabang_id = c.id 
                           WHERE u.id = ?");
$stmtUser->execute([$_SESSION['id_user']]);
$uData = $stmtUser->fetch(PDO::FETCH_ASSOC);

// --- 4. PENGECEKAN STATUS AKTIF REAL-TIME ---
if (!$uData || ($uData['status_akun'] ?? 'Nonaktif') !== 'Aktif') {
    session_destroy();
    if (isset($_COOKIE['remember_sipandu'])) { setcookie('remember_sipandu', '', time() - 3600, "/"); }
    header("Location: login.php?error=blocked");
    exit;
}

// --- 5. TERAPKAN PREFERENSI USER ---
$userTimezone = $uData['timezone'] ?? 'Asia/Jakarta';
date_default_timezone_set($userTimezone);
$darkModeClass = (($uData['dark_mode'] ?? 0) == 1) ? 'dark-mode' : '';

// 6. INISIALISASI VARIABEL HALAMAN
$page = $_GET['page'] ?? 'dashboard';
$role = strtolower($uData['role'] ?? 'petugas'); 

// 7. HARD WALL (WAJIB GANTI PASS)
$is_locked = (isset($_SESSION['perlu_ganti_pass']) && $_SESSION['perlu_ganti_pass'] == 1);
if ($is_locked && $page !== 'ganti_password') {
    header("Location: index.php?page=ganti_password&status=force");
    exit;
}

// =========================================================================
// 8. DEFINISI VARIABEL GLOBAL (DIKELUARKAN DARI FUNGSI AGAR SIDEBAR AMAN)
// =========================================================================
$master_pages = ['master_personil', 'master_lokasi', 'master_akun', 'master_shift', 'master_barang', 'master_kendaraan', 'master_checkpoints'];
$mon_pages    = ['monitoring_presensi', 'monitoring_lembur', 'monitoring_izin', 'monitoring_patroli', 'monitoring_serah_terima', 'live_cctv', 'cctv_settings', 'approval_user'];
$lap_pages    = ['laporan_patroli', 'laporan_presensi', 'laporan_inventaris', 'laporan_visitor', 'laporan_aktivitas', 'laporan_sos'];
$admin_only   = ['log_aktivitas', 'master_akun', 'cctv_settings', 'approval_user', 'broadcast'];

// =========================================================================
// 9. FUNGSI AKSES (RBAC) 
// =========================================================================
function hasAccess($r, $p, $locked) {
    // Panggil variabel global yang ada di atas
    global $master_pages, $mon_pages, $lap_pages, $admin_only; 
    
    if ($locked) return ($p === 'ganti_password');
    if ($r === 'admin' || $r === 'administrator') return true;

    // Aturan Admin & Pengawas
    if (in_array($p, $admin_only) && $r !== 'admin') return false;
    if ($r === 'pengawas' && in_array($p, $master_pages)) return false;
    
    // ATURAN PETUGAS / SATPAM
    if (in_array($r, ['satpam', 'petugas lapangan', 'petugas'])) {
        // Petugas diblokir dari Master Data dan Laporan
        if (in_array($p, $master_pages) || in_array($p, $lap_pages)) return false;
        
        // DARI MENU MONITORING, petugas HANYA diblokir dari setting CCTV & Approval User
        $petugas_blocked_mon = ['cctv_settings', 'approval_user'];
        if (in_array($p, $mon_pages) && in_array($p, $petugas_blocked_mon)) return false;
    }
    return true; 
}

$akses_ditolak = !hasAccess($role, $page, $is_locked);
?>

<!DOCTYPE html>
<html lang="id" data-bs-theme="light" class="<?= $darkModeClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>SIPANDU - Command Center (<?= strtoupper($userTimezone) ?>)</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/dark-mode.css"> <style>
        :root { --primary-gradient: linear-gradient(45deg, #0d6efd, #0b5ed7); }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; transition: all 0.3s ease; }
        
        /* DARK MODE OVERRIDE */
        html.dark-mode body { background-color: #121212; color: #e0e0e0; }
        html.dark-mode .card { background-color: #1e1e1e; border: 1px solid #333; color: #fff; }
        html.dark-mode .navbar, html.dark-mode .sidebar { background-color: #1a1a1a; border-color: #333; }

        /* SOS BUTTON */
        .btn-sos-floating {
            position: fixed; bottom: 30px; right: 30px; width: 70px; height: 70px; border-radius: 50%;
            background: linear-gradient(45deg, #e74a3b, #be2617); color: white; display: flex;
            align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(231, 74, 59, 0.5);
            z-index: 1050; border: 5px solid white; transition: all 0.3s; cursor: pointer; text-decoration: none;
        }
        .animate-pulse-sos { animation: pulse-red 2s infinite; }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(231, 74, 59, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(231, 74, 59, 0); }
            100% { box-shadow: 0 0 0 0 rgba(231, 74, 59, 0); }
        }
    </style>
</head>
<body class="<?= $is_locked ? 'locked-mode' : '' ?>">

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<nav class="sidebar shadow" id="sidebar">
    <div class="text-center py-4 border-bottom border-secondary mb-2" style="background: rgba(0,0,0,0.2);">
        <h4 class="fw-bold mb-0 text-white"><i class="fas fa-shield-halved text-primary me-2"></i>SIPANDU</h4>
        <small class="text-white-50 uppercase" style="font-size: 0.65em;">COMMAND CENTER SYSTEM</small>
    </div>
    <?php include 'layout/sidebar.php'; ?>
</nav>

<div class="main-content">
    <?php include 'layout/navbar.php'; ?>

    <div class="container-fluid p-4">
        <?php
        if ($akses_ditolak) {
            echo "<div class='d-flex align-items-center justify-content-center' style='min-height: 60vh'>
                    <div class='text-center p-5 shadow-lg bg-white rounded-5 animate__animated animate__zoomIn' style='max-width: 500px;'>
                        <div class='text-danger mb-4'><i class='fas fa-user-lock fa-6x'></i></div>
                        <h3 class='fw-bold'>Akses Dibatasi!</h3>
                        <p class='text-muted'>Maaf, Role <b>".ucfirst($role)."</b> Anda tidak memiliki izin untuk halaman ini.</p>
                        <a href='index.php?page=dashboard' class='btn btn-primary rounded-pill px-5 mt-3'>Kembali ke Dashboard</a>
                    </div>
                </div>";
        } else {
            $file = "pages/$page.php";
            if (file_exists($file)) { include $file; } else { include "pages/404.php"; }
        }
        ?>
    </div>

    <footer class="mt-auto bg-white text-center py-4 border-top small text-muted">
        &copy; <?= date('Y') ?> <span class="fw-bold text-primary">SIPANDU Integrated System</span>. Cabang: <?= $uData['nama_cabang'] ?? 'Pusat' ?>
    </footer>
</div>

<audio id="sirenAudio" loop><source src="assets/audio/siren.mp3" type="audio/mpeg"></audio>

<?php if(!in_array($role, ['admin', 'administrator'])): ?>
<a href="javascript:void(0)" onclick="triggerSOS()" class="btn-sos-floating animate-pulse-sos">
    <div class="text-center"><i class="fas fa-bullhorn fa-lg d-block mb-1"></i><span class="fw-bold" style="font-size: 11px;">SOS</span></div>
</a>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/master.js"></script>
<script>
    function updateClock() {
        const now = new Date();
        const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
        const timeStr = now.toLocaleTimeString('id-ID', options);
        const tzLabel = "<?= ($userTimezone == 'Asia/Jakarta') ? 'WIB' : (($userTimezone == 'Asia/Makassar') ? 'WITA' : 'WIT') ?>";
        const el = document.getElementById('nav-jam');
        if(el) el.innerText = timeStr + " " + tzLabel;
    }
    setInterval(updateClock, 1000); updateClock();

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('overlay').classList.toggle('active');
    }

    function triggerSOS() {
        Swal.fire({
            title: 'KIRIM SINYAL SOS?',
            text: "Posisi GPS Anda akan dikirim ke Command Center!",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'YA, DARURAT!'
        }).then((result) => {
            if (result.isConfirmed) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const fd = new FormData();
                        fd.append('act', 'panic_button');
                        fd.append('koordinat', position.coords.latitude + ',' + position.coords.longitude);
                        fetch('proses_sos.php', { method: 'POST', body: fd })
                        .then(res => res.json()).then(data => {
                            if(data.status == 'success') Swal.fire('SOS Terkirim!', 'Petugas bantuan sedang menuju lokasi Anda.', 'success');
                        });
                    });
                }
            }
        });
    }

    let isEmergency = false;
    function checkSOS() {
        if (["admin", "administrator", "pengawas"].includes("<?= $role ?>")) {
            fetch('ajax/check_sos.php').then(res => res.json()).then(data => {
                if (data.emergency && !isEmergency) {
                    isEmergency = true;
                    document.getElementById('sirenAudio').play();
                    Swal.fire({ 
                        title: '?? SINYAL SOS MASUK! ??', 
                        text: 'Ada petugas dalam bahaya! Masukkan catatan penanganan:', 
                        icon: 'error', input: 'textarea', inputPlaceholder: 'Tindakan yang diambil...',
                        confirmButtonText: 'MATIKAN SIRENE', allowOutsideClick: false,
                        preConfirm: (note) => { if (!note) Swal.showValidationMessage('Catatan wajib diisi!'); return note; }
                    }).then((r) => {
                        if (r.isConfirmed) {
                            document.getElementById('sirenAudio').pause(); 
                            isEmergency = false;
                            const fd = new FormData();
                            fd.append('note', r.value);
                            fetch('ajax/mark_read.php', { method: 'POST', body: fd }).then(() => { location.reload(); });
                        }
                    });
                }
            });
        }
    }
    setInterval(checkSOS, 10000);
</script>
</body>
</html>