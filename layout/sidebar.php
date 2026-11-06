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
    return $locked ? 'disabled-menu' : '';
}
?>

<div class="mt-3 px-2"> 

    <?php if (checkMenu($role, 'dashboard')): ?>
    <a href="<?= $is_locked ? '#' : 'index.php?page=dashboard' ?>" class="nav-link-custom <?= setActive('dashboard', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-tachometer-alt"></i></span><span><?= t('dashboard') ?></span></div>
    </a>
    <?php endif; ?>

    <?php if (checkMenu($role, 'master_data')): 
        $master_pages = ['master_personil', 'master_barang', 'master_kendaraan', 'master_cabang', 'master_lokasi', 'master_shift'];
        $is_master = in_array($current_page, $master_pages); 
    ?>
    <a href="<?= $is_locked ? '#' : '#menuMaster' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_master ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-database"></i></span><span><?= t('master_data') ?></span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_master && !$is_locked ? 'show' : '' ?>" id="menuMaster">
        <div class="submenu-container master-submenu">
            <a href="index.php?page=master_personil" class="submenu-item <?= setActive('master_personil', $current_page) ?>">
                <span class="submenu-number">01</span>
                <span><?= t('data_personil') ?></span>
            </a>
            <a href="index.php?page=master_barang" class="submenu-item <?= setActive('master_barang', $current_page) ?>">
                <span class="submenu-number">02</span>
                <span><?= t('data_barang') ?></span>
            </a>
            <a href="index.php?page=master_kendaraan" class="submenu-item <?= setActive('master_kendaraan', $current_page) ?>">
                <span class="submenu-number">03</span>
                <span><?= t('data_kendaraan') ?></span>
            </a>
            <a href="index.php?page=master_cabang" class="submenu-item <?= setActive('master_cabang', $current_page) ?>">
                <span class="submenu-number">04</span>
                <span><?= t('data_cabang') ?></span>
            </a>
            <a href="index.php?page=master_lokasi" class="submenu-item <?= setActive('master_lokasi', $current_page) ?>">
                <span class="submenu-number">05</span>
                <span><?= t('data_lokasi') ?></span>
            </a>
            <a href="index.php?page=master_shift" class="submenu-item <?= setActive('master_shift', $current_page) ?>">
                <span class="submenu-number">06</span>
                <span><?= t('data_shift') ?></span>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'monitoring')): 
        $mon_pages = ['monitoring_presensi', 'monitoring_lembur', 'monitoring_izin', 'monitoring_patroli', 'live_cctv', 'cctv_setting', 'cctv_settings', 'monitoring_serah_terima'];
        $is_mon = in_array($current_page, $mon_pages);
    ?>
    <a href="<?= $is_locked ? '#' : '#menuMonitoring' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_mon ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-desktop"></i></span><span><?= t('monitoring') ?></span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_mon && !$is_locked ? 'show' : '' ?>" id="menuMonitoring">
        <div class="submenu-container">
            <?php if ($role == 'admin' || $role == 'pengawas' || $role == 'petugas' || $role == 'petugas lapangan' || $role == 'satpam'): ?>
                <a href="index.php?page=monitoring_presensi" class="submenu-item <?= setActive('monitoring_presensi', $current_page) ?>"><?= t('monitoring_presensi') ?></a>
                <a href="index.php?page=monitoring_lembur" class="submenu-item <?= setActive('monitoring_lembur', $current_page) ?>"><?= t('approval_lembur') ?></a>
                <a href="index.php?page=monitoring_izin" class="submenu-item <?= setActive('monitoring_izin', $current_page) ?>"><?= t('approval_izin') ?></a>
                <a href="index.php?page=monitoring_patroli" class="submenu-item <?= setActive('monitoring_patroli', $current_page) ?>"><?= t('monitoring_patroli') ?></a>
		<a href="index.php?page=monitoring_serah_terima" class="submenu-item <?= setActive('monitoring_serah_terima', $current_page) ?>"><?= t('monitoring_serah_terima') ?></a>
            <?php endif; ?>
            <a href="index.php?page=live_cctv" class="submenu-item <?= setActive('live_cctv', $current_page) ?>"><?= t('live_cctv') ?></a>
            <?php if ($role == 'admin'): ?>
                <a href="index.php?page=cctv_setting" class="submenu-item <?= setActive('cctv_setting', $current_page) ?>"><?= t('cctv_setting') ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'patroli')): 
        $patroli_pages = ['patroli_scan', 'patroli_lapor', 'patroli_temuan', 'jadwal_patroli', 'serah_terima']; 
        $is_patroli = in_array($current_page, $patroli_pages); 
    ?>
    <a href="<?= $is_locked ? '#' : '#menuPatroli' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_patroli ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-calendar-check"></i></span><span><?= t('patroli') ?></span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_patroli && !$is_locked ? 'show' : '' ?>" id="menuPatroli">
        <div class="submenu-container">
            <a href="index.php?page=jadwal_patroli" class="submenu-item <?= setActive('jadwal_patroli', $current_page) ?>"><?= t('jadwal_patroli') ?></a>
            <a href="index.php?page=patroli_scan" class="submenu-item <?= setActive('patroli_scan', $current_page) ?>"><?= t('patroli_scan') ?></a>
            <a href="index.php?page=patroli_lapor" class="submenu-item <?= setActive('patroli_lapor', $current_page) ?>"><?= t('patroli_lapor') ?></a>
            <a href="index.php?page=patroli_temuan" class="submenu-item <?= setActive('patroli_temuan', $current_page) ?>"><?= t('patroli_temuan') ?></a>
	    <a href="index.php?page=serah_terima" class="submenu-item <?= setActive('serah_terima', $current_page) ?>"><?= t('serah_terima') ?></a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'inventaris')): 
        $inv_pages = ['barang_pinjam', 'barang_kembali', 'barang_lapor', 'kendaraan_pakai', 'kendaraan_kembali', 'kendaraan_lapor'];
        $is_inv = in_array($current_page, $inv_pages); 
    ?>
    <a href="<?= $is_locked ? '#' : '#menuInventaris' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_inv ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-boxes"></i></span><span><?= t('inventaris') ?></span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_inv && !$is_locked ? 'show' : '' ?>" id="menuInventaris">
        <div class="submenu-container">
            <a href="index.php?page=barang_pinjam" class="submenu-item <?= setActive('barang_pinjam', $current_page) ?>"><?= t('peminjaman_barang') ?></a>
            <a href="index.php?page=barang_kembali" class="submenu-item <?= setActive('barang_kembali', $current_page) ?>"><?= t('pengembalian_barang') ?></a>
            <a href="index.php?page=barang_lapor" class="submenu-item <?= setActive('barang_lapor', $current_page) ?>"><?= t('lapor_barang') ?></a>
            <div class="submenu-divider"></div>
            <a href="index.php?page=kendaraan_pakai" class="submenu-item <?= setActive('kendaraan_pakai', $current_page) ?>"><?= t('penggunaan_kendaraan') ?></a>
            <a href="index.php?page=kendaraan_kembali" class="submenu-item <?= setActive('kendaraan_kembali', $current_page) ?>"><?= t('pengembalian_kendaraan') ?></a>
            <a href="index.php?page=kendaraan_lapor" class="submenu-item <?= setActive('kendaraan_lapor', $current_page) ?>"><?= t('lapor_kendaraan') ?></a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'kunjungan')): 
        $visit_pages = ['visit_input', 'visit_riwayat'];
        $is_visit = in_array($current_page, $visit_pages);
    ?>
    <a href="<?= $is_locked ? '#' : '#menuVisitor' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_visit ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-users"></i></span><span><?= t('kunjungan') ?></span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_visit && !$is_locked ? 'show' : '' ?>" id="menuVisitor">
        <div class="submenu-container">
            <a href="index.php?page=visit_input" class="submenu-item <?= setActive('visit_input', $current_page) ?>"><?= t('input_tamu') ?></a>
            <a href="index.php?page=visit_riwayat" class="submenu-item <?= setActive('visit_riwayat', $current_page) ?>"><?= t('riwayat_tamu') ?></a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'presensi_saya')): 
        $my_pre_pages = ['presensi_masuk', 'presensi_pulang', 'presensi_lembur', 'presensi_izin'];
        $is_my_pre = in_array($current_page, $my_pre_pages);
    ?>
    <a href="<?= $is_locked ? '#' : '#menuPresensi' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_my_pre ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-user-clock"></i></span><span><?= t('presensi_saya') ?></span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_my_pre && !$is_locked ? 'show' : '' ?>" id="menuPresensi">
        <div class="submenu-container">
            <a href="index.php?page=presensi_masuk" class="submenu-item <?= setActive('presensi_masuk', $current_page) ?>"><?= t('presensi_masuk') ?></a>
            <a href="index.php?page=presensi_pulang" class="submenu-item <?= setActive('presensi_pulang', $current_page) ?>"><?= t('presensi_pulang') ?></a>
            <a href="index.php?page=presensi_lembur" class="submenu-item <?= setActive('presensi_lembur', $current_page) ?>"><?= t('presensi_lembur') ?></a>
            <a href="index.php?page=presensi_izin" class="submenu-item <?= setActive('presensi_izin', $current_page) ?>"><?= t('izin_sakit') ?></a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (checkMenu($role, 'laporan')): 
        $lap_pages = ['laporan_patroli', 'laporan_presensi', 'laporan_inventaris', 'laporan_visitor', 'laporan_aktivitas', 'laporan_sos'];
        $is_lap = in_array($current_page, $lap_pages);
    ?>
    <a href="<?= $is_locked ? '#' : '#menuLaporan' ?>" data-bs-toggle="<?= $is_locked ? '' : 'collapse' ?>" class="nav-link-custom <?= $is_lap ? 'active-menu' : '' ?>" <?= lockLink($is_locked) ?>>
        <div><span class="icon-box"><i class="fas fa-file-invoice"></i></span><span><?= t('laporan') ?></span></div>
        <i class="fas fa-chevron-down small"></i>
    </a>
    <div class="collapse <?= $is_lap && !$is_locked ? 'show' : '' ?>" id="menuLaporan">
        <div class="submenu-container">
            <a href="index.php?page=laporan_patroli" class="submenu-item <?= setActive('laporan_patroli', $current_page) ?>"><?= t('laporan_patroli') ?></a>
            <a href="index.php?page=laporan_presensi" class="submenu-item <?= setActive('laporan_presensi', $current_page) ?>"><?= t('laporan_presensi') ?></a>
            <a href="index.php?page=laporan_inventaris" class="submenu-item <?= setActive('laporan_inventaris', $current_page) ?>"><?= t('laporan_inventaris') ?></a>
            <a href="index.php?page=laporan_visitor" class="submenu-item <?= setActive('laporan_visitor', $current_page) ?>"><?= t('laporan_visitor') ?></a>
            <a href="index.php?page=laporan_aktivitas" class="submenu-item <?= setActive('laporan_aktivitas', $current_page) ?>"><?= t('laporan_aktivitas') ?></a>
            <a href="index.php?page=laporan_sos" class="submenu-item <?= setActive('laporan_sos', $current_page) ?>"><?= t('laporan_sos') ?></a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($role === 'admin' || $role === 'administrator'): ?>
        <hr class="my-3 sidebar-divider">
        <div class="px-3 mb-2 small text-uppercase fw-bold sidebar-security-label"><?= t('system_security') ?></div>
        <a href="<?= $is_locked ? '#' : 'index.php?page=sop_handbook' ?>" class="nav-link-custom <?= setActive('sop_handbook', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
            <div><span class="icon-box"><i class="fas fa-book-reader"></i></span><span><?= t('sop_handbook') ?></span></div>
        </a>
        
        <a href="<?= $is_locked ? '#' : 'index.php?page=approval_user' ?>" class="nav-link-custom <?= setActive('approval_user', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
            <div><span class="icon-box"><i class="fas fa-user-check"></i></span><span><?= t('approval_user') ?></span></div>
        </a>

        <a href="<?= $is_locked ? '#' : 'index.php?page=broadcast' ?>" class="nav-link-custom <?= setActive('broadcast', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
            <div><span class="icon-box"><i class="fas fa-bullhorn"></i></span><span><?= t('broadcast') ?></span></div>
        </a>
        <a href="<?= $is_locked ? '#' : 'index.php?page=log_aktivitas' ?>" class="nav-link-custom <?= setActive('log_aktivitas', $current_page, 'active-menu') ?>" <?= lockLink($is_locked) ?>>
            <div><span class="icon-box"><i class="fas fa-history"></i></span><span><?= t('log_aktivitas') ?></span></div>
        </a>
    <?php endif; ?>

<hr class="my-4 sidebar-divider">
<a href="logout.php" class="nav-link-custom btn-logout">
    <div><span class="icon-box"><i class="fas fa-sign-out-alt"></i></span><span><?= t('logout') ?></span></div>
</a>
</div>