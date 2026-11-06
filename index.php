<?php
ob_start();
session_start();

// 1. CEK LOGIN DULU
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// 2. LOAD KONEKSI & HELPER
require_once 'config/database.php';
require_once 'config/helper_notif.php';
require_once 'config/csrf.php';

// =========================================================================
// 2.5 CEK FOTO PROFIL (SATPAM PEMAKSA UPLOAD FOTO)
// =========================================================================
$stmtCekFoto = $pdo->prepare("SELECT foto_profil, foto FROM users WHERE id = ?");
$stmtCekFoto->execute([$_SESSION['id_user']]);
$uDataCek = $stmtCekFoto->fetch(PDO::FETCH_ASSOC);

// Ambil nama file fotonya
$fotoP = $uDataCek['foto_profil'] ?? $uDataCek['foto'] ?? '';
$punyaFoto = (!empty($fotoP) && $fotoP !== 'default.png' && $fotoP !== 'default.jpg');

// Halaman yang diizinkan untuk diakses meski belum punya foto
$currentPage = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['akun_saya', 'proses_akun', 'logout', 'ganti_password']; 

// Jika belum punya foto dan bukan di halaman pengecualian, TENDANG!
if (!$punyaFoto && !in_array($currentPage, $allowed_pages)) {
    header("Location: index.php?page=akun_saya&status=wajib_foto");
    exit;
}
// =========================================================================

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
$currentLang = (($uData['bahasa'] ?? 'id') === 'en') ? 'en' : 'id';
$GLOBALS['__current_lang'] = $currentLang;
require_once 'config/lang.php';

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
$mon_pages    = ['monitoring_presensi', 'monitoring_lembur', 'monitoring_izin', 'monitoring_patroli', 'monitoring_serah_terima', 'live_cctv', 'cctv_settings', 'cctv_setting', 'approval_user'];
$lap_pages    = ['laporan_patroli', 'laporan_presensi', 'laporan_inventaris', 'laporan_visitor', 'laporan_aktivitas', 'laporan_sos'];
$admin_only   = ['log_aktivitas', 'master_akun', 'cctv_settings', 'cctv_setting', 'approval_user', 'broadcast'];

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
$csrf_token = csrf_token_value();
?>

<!DOCTYPE html>
<html lang="<?= $currentLang ?>" class="<?= $darkModeClass ?>" data-bs-theme="<?= (($uData['dark_mode'] ?? 0) == 1) ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>SIPANDU - Command Center (<?= strtoupper($userTimezone) ?>)</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sipandu.css?v=<?= time(); ?>">
</head>
<body class="<?= $is_locked ? 'locked-mode' : '' ?>">

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<nav class="sidebar shadow" id="sidebar">
    <div class="brand">
        <a href="index.php" class="brand-logo-link d-flex align-items-center gap-3 text-decoration-none">
            <div class="logo-box">
                <img src="assets/img/logo-target.jpeg" alt="Target Integrated Security Services" class="brand-logo-img">
            </div>
            <div>
                <div class="fw-bold">SIPANDU</div>
                <small class="text-secondary brand-subtitle">Command Center</small>
            </div>
        </a>
    </div>
    <?php include 'layout/sidebar.php'; ?>
</nav>

<div class="main-content">
    <?php include 'layout/navbar.php'; ?>

    <div class="container-fluid p-4">
        <?php
        if ($akses_ditolak) {
            echo "<div class='locked-screen d-flex align-items-center justify-content-center'>
                    <div class='text-center p-5 shadow-lg bg-white rounded-5 animate__animated animate__zoomIn locked-card'>
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

    <footer class="mt-auto text-center py-4 border-top small text-muted">
        &copy; <?= date('Y') ?> <span class="fw-bold text-info">SIPANDU Integrated System</span>. Cabang: <?= $uData['nama_cabang'] ?? 'Pusat' ?>
    </footer>
</div>

<audio id="sirenAudio" loop preload="auto"><source src="assets/audio/alarm.mp3" type="audio/mpeg"></audio>

<?php if(!in_array($role, ['admin', 'administrator'])): ?>
<a href="javascript:void(0)" onclick="triggerSOS()" class="btn-sos-floating animate-pulse-sos">
    <div class="text-center"><i class="fas fa-bullhorn fa-lg d-block mb-1"></i><span class="fw-bold sos-label">SOS</span></div>
</a>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/master.js"></script>
<script>
    window.APP_CSRF_TOKEN = <?= json_encode($csrf_token) ?>;

    // Global Status Notifier (SweetAlert)
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');
        const sim = urlParams.get('sim');

        if (status === 'clockin_success') {
            Swal.fire({
                icon: 'success',
                title: 'Presensi Masuk Berhasil!',
                html: 'Wajah Anda berhasil diverifikasi oleh AI.<br>' + (sim ? `<span class="badge bg-success fs-6 mt-2 p-2">Tingkat Kemiripan: ${sim}%</span>` : ''),
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'clockout_success') {
            Swal.fire({
                icon: 'success',
                title: 'Presensi Pulang Berhasil!',
                html: 'Absen pulang telah dicatat dan terverifikasi AI.<br>' + (sim ? `<span class="badge bg-success fs-6 mt-2 p-2">Tingkat Kemiripan: ${sim}%</span>` : ''),
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'wajib_foto') {
            Swal.fire({
                icon: 'warning',
                title: 'Wajib Foto Profil!',
                text: 'Silakan upload foto profil Anda terlebih dahulu untuk mengaktifkan fitur Face Recognition Presensi.',
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'success' && urlParams.get('page') === 'barang_lapor') {
            Swal.fire({
                icon: 'success',
                title: 'Laporan Berhasil Terkirim',
                text: 'Laporan masalah barang sudah tersimpan dan stok telah diperbarui.',
                timer: 2500,
                showConfirmButton: false
            });
        } else if (status === 'error' && msg) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: decodeURIComponent(msg),
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'error' && urlParams.get('page') === 'barang_lapor') {
            Swal.fire({
                icon: 'error',
                title: 'Laporan Gagal Terkirim',
                text: 'Laporan tidak tersimpan. Silakan periksa data dan coba lagi.',
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'success') {
            // Jaring pengaman: setiap modul yang redirect dengan status=success
            // otomatis dapat popup, tanpa perlu ditambal satu per satu.
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: msg ? decodeURIComponent(msg) : 'Data berhasil disimpan.',
                timer: 2200,
                showConfirmButton: false
            });
        } else if (status === 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: msg ? decodeURIComponent(msg) : 'Terjadi kesalahan. Silakan periksa kembali data Anda.',
                confirmButtonColor: '#2563eb'
            });
        }
    });

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
                        fd.append('csrf_token', window.APP_CSRF_TOKEN || '');
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
    let audioUnlocked = false;

    // Browser memblokir autoplay sebelum ada interaksi; "pancing" audio sekali pada klik/tap pertama.
    function unlockAlarmAudio() {
        const a = document.getElementById('sirenAudio');
        if (!a || audioUnlocked) return;
        a.muted = true;
        a.play().then(() => {
            a.pause(); a.currentTime = 0; a.muted = false; audioUnlocked = true;
        }).catch(() => { a.muted = false; });
    }
    ['click', 'touchstart', 'keydown'].forEach(ev =>
        document.addEventListener(ev, unlockAlarmAudio, { once: true, passive: true }));

    function startAlarm() {
        const a = document.getElementById('sirenAudio');
        if (!a) return;
        a.loop = true;
        a.muted = false;
        a.play().catch(() => {
            // Masih diblokir: ulangi saat interaksi berikutnya.
            const retry = () => { if (isEmergency) a.play().catch(() => {}); };
            document.addEventListener('click', retry, { once: true });
        });
    }

    function stopAlarm() {
        const a = document.getElementById('sirenAudio');
        if (a) { a.pause(); a.currentTime = 0; }
    }

    function triggerEmergencyAlert() {
        if (isEmergency) return;
        isEmergency = true;
        const siren = document.getElementById('sirenAudio');
        startAlarm();

        Swal.fire({ 
            title: '🚨 SINYAL SOS MASUK! 🚨', 
            text: 'Ada personil dalam bahaya! Masukkan catatan penanganan atau klik matikan:', 
            icon: 'error', input: 'textarea', inputPlaceholder: 'Tindakan / respon yang diambil...',
            confirmButtonText: 'TANGANI / MATIKAN ALARM', confirmButtonColor: '#ef4444', allowOutsideClick: false
        }).then((r) => {
            stopAlarm();
            isEmergency = false;
            const fd = new FormData();
            fd.append('category', 'alert');
            fd.append('note', r.value || 'Sirene dimatikan.');
            fetch('ajax/mark_read.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .catch(() => {});
        });
    }

    function checkSOS() {
        if (["admin", "administrator", "pengawas"].includes("<?= $role ?>")) {
            if (!!window.EventSource) {
                const source = new EventSource('ajax/sos_stream.php');
                source.onmessage = function(e) {
                    try {
                        const data = JSON.parse(e.data);
                        if (data.emergency) { triggerEmergencyAlert(); }
                    } catch(err) {}
                };
                source.onerror = function() {
                    source.close();
                    setInterval(() => {
                        fetch('ajax/check_sos.php').then(res => res.json()).then(data => {
                            if (data.emergency) { triggerEmergencyAlert(); }
                        });
                    }, 8000);
                };
            } else {
                setInterval(() => {
                    fetch('ajax/check_sos.php').then(res => res.json()).then(data => {
                        if (data.emergency) { triggerEmergencyAlert(); }
                    });
                }, 8000);
            }
        }
    }
    document.addEventListener('DOMContentLoaded', checkSOS);
</script>
</body>
</html>