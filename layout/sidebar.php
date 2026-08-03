<?php
$current_page = $_GET['page'] ?? 'dashboard';
$role = strtolower($_SESSION['role'] ?? ''); 

// Flag Kunci (Hard Wall)
$is_locked = (isset($_SESSION['perlu_ganti_pass']) && $_SESSION['perlu_ganti_pass'] == 1);

if (!function_exists('setActive')) {
    function setActive($page, $current, $class = 'active') {
        return ($page == $current) ? $class : '';
    }
}

if (!function_exists('checkMenu')) {
    function checkMenu($r, $s) {
        // Normalisasi role agar tidak sensitif huruf besar/kecil
        $r = strtolower($r);

        // 1. ADMIN: Full Akses
        if ($r === 'admin' || $r === 'administrator') return true;

        // 2. PENGAWAS: Semua kecuali Master Data
        if ($r === 'pengawas') {
            return ($s !== 'master_data');
        }

        // 3. PETUGAS / SATPAM: Menu Lapangan
        // SAYA TAMBAHKAN: 'petugas lapangan' dan 'satpam' agar Febri bisa login
        if ($r === 'petugas' || $r === 'petugas lapangan' || $r === 'satpam') {
            $allowed = ['dashboard', 'patroli', 'inventaris', 'kunjungan', 'presensi_saya', 'monitoring'];
            return in_array($s, $allowed);
        }

        return false;
    }
}

// Fungsi bantu untuk menonaktifkan link jika akun terkunci
function lockLink($locked) {
    return $locked ? 'style="pointer-events: none; opacity: 0.5; cursor: not-allowed;"' : '';
}
?>

<div class="mt-3 px-2"> 

    <?php if (checkMenu($role, 'dashboard')): ?>
    <a href="<?= $is_locked ? '#' : 'index.php?page=dashboard' ?>" class="nav-link-custom <?= setActive('dashboard', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-tachometer-alt" style="color: #4e73df;"></i></span><span>Dashboard</span></div>
    </a>
    <?php endif; ?>

    <?php if (checkMenu($role, 'master_data')): 
        $master_pages = ['master_personil', 'master_akun', 'master_barang', 'master_kendaraan', 'master_cabang', 'master_lokasi', 'master_shift'];
        $is_master = in_array($current_page, $master_pages); 
    ?>
    <a href="<?= $is_locked ? '#' : '#menuMaster' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_master ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-database" style="color: #d1d3e2;"></i></span><span>Master Data</span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_master && !$is_locked ? 'show' : '' ?>" id="menuMaster">
        <div class="submenu-container">
            <a href="index.php?page=master_personil" class="submenu-item <?= setActive('master_personil', $current_page) ?>">Data Personil</a>
            <a href="index.php?page=master_akun" class="submenu-item <?= setActive('master_akun', $current_page) ?>">Data Akun</a>
            <a href="index.php?page=master_barang" class="submenu-item <?= setActive('master_barang', $current_page) ?>">Data Barang</a>
            <a href="index.php?page=master_kendaraan" class="submenu-item <?= setActive('master_kendaraan', $current_page) ?>">Data Kendaraan</a>
            <a href="index.php?page=master_cabang" class="submenu-item <?= setActive('master_cabang', $current_page) ?>">Data Cabang</a>
            <a href="index.php?page=master_lokasi" class="submenu-item <?= setActive('master_lokasi', $current_page) ?>">Data Lokasi</a>
            <a href="index.php?page=master_shift" class="submenu-item <?= setActive('master_shift', $current_page) ?>">Data Shift</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'monitoring')): 
        $mon_pages = ['monitoring_presensi', 'monitoring_lembur', 'monitoring_izin', 'monitoring_patroli', 'live_cctv', 'cctv_settings', 'monitoring_serah_terima'];
        $is_mon = in_array($current_page, $mon_pages);
    ?>
    <a href="<?= $is_locked ? '#' : '#menuMonitoring' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_mon ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-desktop" style="color: #36b9cc;"></i></span><span>Monitoring</span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_mon && !$is_locked ? 'show' : '' ?>" id="menuMonitoring">
        <div class="submenu-container">
            <?php if ($role == 'admin' || $role == 'pengawas' || $role == 'petugas' || $role == 'petugas lapangan' || $role == 'satpam'): ?>
                <a href="index.php?page=monitoring_presensi" class="submenu-item <?= setActive('monitoring_presensi', $current_page) ?>">Monitoring Presensi</a>
                <a href="index.php?page=monitoring_lembur" class="submenu-item <?= setActive('monitoring_lembur', $current_page) ?>">Approval Lembur</a>
                <a href="index.php?page=monitoring_izin" class="submenu-item <?= setActive('monitoring_izin', $current_page) ?>">Approval Izin & Cuti</a>
                <a href="index.php?page=monitoring_patroli" class="submenu-item <?= setActive('monitoring_patroli', $current_page) ?>">Monitoring Patroli</a>
		<a href="index.php?page=monitoring_serah_terima" class="submenu-item <?= setActive('monitoring_serah_terima', $current_page) ?>">Monitoring Serah Terima</a>
            <?php endif; ?>
            <a href="index.php?page=live_cctv" class="submenu-item <?= setActive('live_cctv', $current_page) ?>">Live View CCTV</a>
            <?php if ($role == 'admin'): ?>
                <a href="index.php?page=cctv_settings" class="submenu-item <?= setActive('cctv_settings', $current_page) ?>">Setting CCTV</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'patroli')): 
        $patroli_pages = ['patroli_scan', 'patroli_lapor', 'patroli_temuan', 'jadwal_patroli'];
        $is_patroli = in_array($current_page, $patroli_pages); 
    ?>
    <a href="<?= $is_locked ? '#' : '#menuPatroli' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_patroli ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-calendar-check" style="color: #e74a3b;"></i></span><span>Patroli</span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_patroli && !$is_locked ? 'show' : '' ?>" id="menuPatroli">
        <div class="submenu-container">
            <a href="index.php?page=jadwal_patroli" class="submenu-item <?= setActive('jadwal_patroli', $current_page) ?>">Jadwal Patroli</a>
            <a href="index.php?page=patroli_scan" class="submenu-item <?= setActive('patroli_scan', $current_page) ?>">Scan QR Code</a>
            <a href="index.php?page=patroli_lapor" class="submenu-item <?= setActive('patroli_lapor', $current_page) ?>">Patroli Lapor</a>
            <a href="index.php?page=patroli_temuan" class="submenu-item <?= setActive('patroli_temuan', $current_page) ?>">Status Temuan</a>
	    <a href="index.php?page=serah_terima" class="submenu-item <?= setActive('serah_terima', $current_page) ?>">Serah Terima</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'inventaris')): 
        $inv_pages = ['barang_pinjam', 'barang_kembali', 'barang_lapor', 'kendaraan_pakai', 'kendaraan_kembali', 'kendaraan_lapor'];
        $is_inv = in_array($current_page, $inv_pages); 
    ?>
    <a href="<?= $is_locked ? '#' : '#menuInventaris' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_inv ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-boxes" style="color: #f6c23e;"></i></span><span>Inventaris</span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_inv && !$is_locked ? 'show' : '' ?>" id="menuInventaris">
        <div class="submenu-container">
            <a href="index.php?page=barang_pinjam" class="submenu-item <?= setActive('barang_pinjam', $current_page) ?>">Peminjaman Barang</a>
            <a href="index.php?page=barang_kembali" class="submenu-item <?= setActive('barang_kembali', $current_page) ?>">Pengembalian Barang</a>
            <a href="index.php?page=barang_lapor" class="submenu-item <?= setActive('barang_lapor', $current_page) ?>">Lapor Masalah Barang</a>
            <div class="my-2" style="border-top: 1px solid rgba(255,255,255,0.05); margin: 0 15px;"></div>
            <a href="index.php?page=kendaraan_pakai" class="submenu-item <?= setActive('kendaraan_pakai', $current_page) ?>">Penggunaan Kendaraan</a>
            <a href="index.php?page=kendaraan_kembali" class="submenu-item <?= setActive('kendaraan_kembali', $current_page) ?>">Pengembalian Kendaraan</a>
            <a href="index.php?page=kendaraan_lapor" class="submenu-item <?= setActive('kendaraan_lapor', $current_page) ?>">Lapor Masalah Kendaraan</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'kunjungan')): 
        $visit_pages = ['visit_input', 'visit_riwayat'];
        $is_visit = in_array($current_page, $visit_pages);
    ?>
    <a href="<?= $is_locked ? '#' : '#menuVisitor' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_visit ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-users" style="color: #1cc88a;"></i></span><span>Kunjungan</span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_visit && !$is_locked ? 'show' : '' ?>" id="menuVisitor">
        <div class="submenu-container">
            <a href="index.php?page=visit_input" class="submenu-item <?= setActive('visit_input', $current_page) ?>">Input Tamu</a>
            <a href="index.php?page=visit_riwayat" class="submenu-item <?= setActive('visit_riwayat', $current_page) ?>">Riwayat Tamu</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'presensi_saya')): 
        $my_pre_pages = ['presensi_masuk', 'presensi_pulang', 'presensi_lembur', 'presensi_izin'];
        $is_my_pre = in_array($current_page, $my_pre_pages);
    ?>
    <a href="<?= $is_locked ? '#' : '#menuPresensi' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_my_pre ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-user-clock" style="color: #6f42c1;"></i></span><span>Presensi Saya</span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_my_pre && !$is_locked ? 'show' : '' ?>" id="menuPresensi">
        <div class="submenu-container">
            <a href="index.php?page=presensi_masuk" class="submenu-item <?= setActive('presensi_masuk', $current_page) ?>">Presensi Masuk</a>
            <a href="index.php?page=presensi_pulang" class="submenu-item <?= setActive('presensi_pulang', $current_page) ?>">Presensi Pulang</a>
            <a href="index.php?page=presensi_lembur" class="submenu-item <?= setActive('presensi_lembur', $current_page) ?>">Presensi Lembur</a>
            <a href="index.php?page=presensi_izin" class="submenu-item <?= setActive('presensi_izin', $current_page) ?>">Izin & Sakit</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'laporan')): 
	$mon_pages = ['monitoring_presensi', 'monitoring_lembur', 'monitoring_izin', 'monitoring_patroli', 'monitoring_serah_terima', 'live_cctv', 'cctv_settings'];
        $is_lap = in_array($current_page, $lap_pages);
    ?>
    <a href="<?= $is_locked ? '#' : '#menuLaporan' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_lap ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-file-invoice" style="color: #fd7e14;"></i></span><span>Laporan</span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_lap && !$is_locked ? 'show' : '' ?>" id="menuLaporan">
        <div class="submenu-container">
            <a href="index.php?page=laporan_patroli" class="submenu-item <?= setActive('laporan_patroli', $current_page) ?>">Laporan Patroli</a>
            <a href="index.php?page=laporan_presensi" class="submenu-item <?= setActive('laporan_presensi', $current_page) ?>">Laporan Presensi</a>
            <a href="index.php?page=laporan_inventaris" class="submenu-item <?= setActive('laporan_inventaris', $current_page) ?>">Laporan Inventaris</a>
            <a href="index.php?page=laporan_visitor" class="submenu-item <?= setActive('laporan_visitor', $current_page) ?>">Laporan Visitor</a>
            <a href="index.php?page=laporan_aktivitas" class="submenu-item <?= setActive('laporan_aktivitas', $current_page) ?>">Laporan Aktivitas</a>
            <a href="index.php?page=laporan_sos" class="submenu-item <?= setActive('laporan_sos', $current_page) ?>">Laporan SOS</a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($role === 'admin' || $role === 'administrator'): ?>
        <hr class="my-3" style="border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="px-3 mb-2 small text-uppercase text-white-50 fw-bold" style="font-size: 0.65rem;">System Security</div>
    </a>
        <a href="<?= $is_locked ? '#' : 'index.php?page=sop_handbook' ?>" class="nav-link-custom <?= setActive('sop_handbook', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
            <div><span class="icon-box"><i class="fas fa-book-reader"></i></span><span>Digital SOP</span></div>
        </a>
        
        <a href="<?= $is_locked ? '#' : 'index.php?page=approval_user' ?>" class="nav-link-custom <?= setActive('approval_user', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
            <div><span class="icon-box"><i class="fas fa-user-check" style="color: #20c997;"></i></span><span>Approval User</span></div>
        </a>

        <a href="<?= $is_locked ? '#' : 'index.php?page=broadcast' ?>" class="nav-link-custom <?= setActive('broadcast', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
            <div><span class="icon-box"><i class="fas fa-bullhorn" style="color: #f6c23e;"></i></span><span>Broadcast Pesan</span></div>
        </a>
        <a href="<?= $is_locked ? '#' : 'index.php?page=log_aktivitas' ?>" class="nav-link-custom <?= setActive('log_aktivitas', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
            <div><span class="icon-box"><i class="fas fa-history" style="color: #adb5bd;"></i></span><span>Log Aktivitas</span></div>
        </a>
    <?php endif; ?>

<hr class="my-4" style="border-top: 1px solid rgba(255,255,255,0.1);">
<a href="logout.php" class="nav-link-custom btn-logout">
    <div><span class="icon-box"><i class="fas fa-sign-out-alt"></i></span><span>Logout</span></div>
</a>
</div>