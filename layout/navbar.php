<style>
    :root {
        --nav-height: 70px;
    }

    /* Penyesuaian Navbar */
    .navbar {
        height: var(--nav-height);
        z-index: 1030;
    }

    /* Styling Umum Dropdown */
    .dropdown-menu {
        border-radius: 12px !important;
        margin-top: 15px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        animation: slideIn 0.2s ease-out;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Efek Hover Menu */
    .dropdown-item {
        transition: all 0.2s ease;
        padding: 10px 20px;
        font-size: 0.9rem;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        padding-left: 25px !important;
    }

    .dropdown-item.text-danger:hover {
        background-color: #fff5f5;
    }

    /* Icon Styling */
    .btn-header-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .btn-header-icon:hover {
        background-color: #f1f3f5 !important;
        transform: translateY(-2px);
    }

    /* Scrollbar Halus untuk Notifikasi */
    .notif-scroll {
        max-height: 350px;
        overflow-y: auto;
    }

    .notif-scroll::-webkit-scrollbar {
        width: 4px;
    }

    .notif-scroll::-webkit-scrollbar-thumb {
        background: #e0e0e0;
        border-radius: 10px;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-4 sticky-top">
    <div class="d-flex align-items-center w-100">
        
        <?php if (!$is_locked): ?>
        <button class="btn btn-light border me-3 d-md-none" type="button" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <?php endif; ?>

        <span class="navbar-brand mb-0 h1 fw-bold fs-6 text-uppercase text-dark tracking-wide d-flex align-items-center">
            <i class="fas fa-chevron-right me-2 text-primary" style="font-size: 0.8rem;"></i>
            <span data-i18n="<?= $page ?>"><?= str_replace('_', ' ', $page) ?></span>
        </span>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">
            
            <?php if (!$is_locked): ?>
                
                <a href="index.php?page=forum_diskusi" class="btn btn-light border-0 rounded-circle btn-header-icon" title="Forum Diskusi">
                    <i class="fas fa-comments text-secondary"></i>
                </a>

                <div class="dropdown me-1">
                    <a href="#" class="text-secondary position-relative btn btn-light border-0 rounded-circle btn-header-icon" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" onclick="markAsRead()">
                        <i class="fas fa-bell"></i>
                        <?php if (isset($notif_count) && $notif_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" id="notifBadge" style="font-size: 0.6rem; padding: 0.35em 0.5em;">
                                <?= $notif_count > 99 ? '99+' : $notif_count ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end border-0 overflow-hidden" aria-labelledby="notifDropdown" style="width: 320px;">
                        <li class="dropdown-header bg-light fw-bold text-dark d-flex justify-content-between align-items-center py-3 px-3">
                            <span class="fs-6">Notifikasi</span>
                            <a href="index.php?page=notifikasi" class="small text-decoration-none text-primary">Lihat Semua</a>
                        </li>
                        
                        <div class="notif-scroll">
                            <?php if (empty($notif_list)): ?>
                                <li class="text-center py-5 text-muted">
                                    <i class="far fa-bell-slash fa-2x mb-3 text-secondary opacity-25"></i><br>
                                    <small>Belum ada notifikasi baru.</small>
                                </li>
                            <?php else: ?>
                                <?php foreach ($notif_list as $n): ?>
                                    <li>
                                        <a class="dropdown-item py-3 px-3 d-flex align-items-start gap-3 border-bottom <?= $n['is_read'] == 0 ? 'bg-light' : '' ?>" href="<?= $n['link'] ?? '#' ?>">
                                            <div class="mt-1">
                                                <?php if($n['category'] == 'success'): ?>
                                                    <i class="fas fa-check-circle text-success fs-5"></i>
                                                <?php elseif($n['category'] == 'alert'): ?>
                                                    <i class="fas fa-exclamation-triangle text-danger fs-5"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-info-circle text-primary fs-5"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div style="min-width: 0;">
                                                <div class="small fw-bold text-dark text-truncate"><?= htmlspecialchars($n['title']) ?></div>
                                                <div class="small text-muted text-truncate"><?= htmlspecialchars($n['message']) ?></div>
                                                <div class="text-secondary mt-1" style="font-size: 0.65rem;">
                                                    <i class="far fa-clock me-1"></i><?= date('d M, H:i', strtotime($n['created_at'])) ?>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </ul>
                </div>

            <?php endif; ?>

            <div class="d-none d-md-block text-end lh-1 border-start ps-3 me-2">
                <div class="fw-bold text-dark" id="nav-jam" style="font-size: 0.9rem; font-family: 'Courier New', Courier, monospace;">--:--:-- WIB</div>
                <small class="text-secondary" style="font-size: 0.75rem;"><?= date('d M Y') ?></small>
            </div>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center link-dark text-decoration-none" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary-subtle border border-primary-subtle rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; transition: all 0.3s;">
                        <i class="fas fa-user-shield text-primary"></i>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 mt-3 overflow-hidden" aria-labelledby="dropdownUser" style="width: 280px;">
                    <li class="px-4 py-3 border-bottom" style="background-color: #f8f9fa;">
                        <div class="small text-muted mb-1">Masuk sebagai:</div>
                        <span class="d-block fw-bold text-dark" style="font-size: 1rem; line-height: 1.2;"><?= $_SESSION['nama_lengkap'] ?></span>
                        <span class="badge rounded-pill bg-primary-subtle text-primary fw-medium mt-2" style="font-size: 0.7rem; letter-spacing: 0.5px; padding: 5px 10px;">
                            <i class="fas fa-shield-alt me-1"></i> <?= strtoupper($_SESSION['role']) ?>
                        </span>
                    </li>
                    
                    <?php if (!$is_locked): ?>
                    <li>
                        <a class="dropdown-item d-flex align-items-center mt-2" href="index.php?page=akun_saya">
                            <i class="fas fa-cog me-3 text-secondary" style="width: 20px;"></i> 
                            <span>Pengaturan Akun</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li><hr class="dropdown-divider my-2 opacity-50"></li>

                    <li>
<a class="dropdown-item d-flex align-items-center mb-2 text-danger btn-logout" href="logout.php">
    <i class="fas fa-sign-out-alt me-3" style="width: 20px;"></i> 
    <span class="fw-bold">Logout</span>
</a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</nav>

<script>
    function updateJam() {
        const now = new Date();
        const jam = String(now.getHours()).padStart(2, '0');
        const menit = String(now.getMinutes()).padStart(2, '0');
        const detik = String(now.getSeconds()).padStart(2, '0');
        const element = document.getElementById('nav-jam');
        if(element) element.textContent = `${jam}:${menit}:${detik} WIB`;
    }
    setInterval(updateJam, 1000);
    updateJam();
</script>