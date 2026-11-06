
<nav class="navbar navbar-expand-lg navbar-light navbar-custom px-4 sticky-top">
    <div class="d-flex align-items-center w-100">
        
        <?php if (!$is_locked): ?>
        <button class="btn btn-header-icon border me-3 d-md-none" type="button" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <?php endif; ?>

        <span class="navbar-brand mb-0 h1 fw-bold fs-6 text-uppercase tracking-wide d-flex align-items-center gap-2">
            <img src="assets/img/logo-target.jpeg" alt="Target Integrated Security Services" style="width: 28px; height: auto;">
            <i class="fas fa-chevron-right me-2 navbar-brand-icon"></i>
            <span data-i18n="<?= $page ?>"><?= t($page, ucwords(str_replace('_', ' ', $page))) ?></span>
        </span>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">
            
            <?php if (!$is_locked): ?>
                
                <a href="index.php?page=forum_diskusi" class="btn btn-header-icon rounded-circle" title="Forum Diskusi">
                    <i class="fas fa-comments"></i>
                </a>

                <div class="dropdown me-1">
                    <a href="#" class="position-relative btn btn-header-icon rounded-circle" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" onclick="markAsRead()">
                        <i class="fas fa-bell"></i>
                        <?php if (isset($notif_count) && $notif_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white border border-light badge-notif" id="notifBadge">
                                <?= $notif_count > 99 ? '99+' : $notif_count ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end border-0 overflow-hidden dropdown-user-menu shadow-lg" aria-labelledby="notifDropdown">
                        <li class="dropdown-header text-primary fw-bold d-flex justify-content-between align-items-center py-3 px-3 border-bottom">
                            <span class="fs-6"><?= t('notifikasi') ?></span>
                            <a href="index.php?page=notifikasi" class="small text-decoration-none text-primary fw-semibold"><?= t('lihat_semua') ?></a>
                        </li>
                        
                        <div class="notif-scroll">
                            <?php if (empty($notif_list)): ?>
                                <li class="text-center py-5 text-muted">
                                    <i class="far fa-bell-slash fa-2x mb-3 text-secondary opacity-25"></i><br>
                                    <small><?= t('belum_ada_notif') ?></small>
                                </li>
                            <?php else: ?>
                                <?php foreach ($notif_list as $n): ?>
                                    <li>
                                        <a class="dropdown-item py-3 px-3 d-flex align-items-start gap-3 border-bottom" href="<?= $n['link'] ?? '#' ?>">
                                            <div class="mt-1">
                                                <?php if($n['category'] == 'success'): ?>
                                                    <i class="fas fa-check-circle text-success fs-5"></i>
                                                <?php elseif($n['category'] == 'alert'): ?>
                                                    <i class="fas fa-exclamation-triangle text-danger fs-5"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-info-circle text-info fs-5"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="no-wrap">
                                                <div class="small fw-bold text-dark text-truncate"><?= htmlspecialchars($n['title']) ?></div>
                                                <div class="small text-muted text-truncate"><?= htmlspecialchars($n['message']) ?></div>
                                                <div class="text-muted mt-1 small-text-muted">
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

            <div class="d-none d-md-block text-end lh-1 border-start ps-3 me-2 border-secondary-subtle">
                <div class="fw-bold text-dark text-clock" id="nav-jam">--:--:-- WIB</div>
                <small class="text-muted small-text-muted"><?= date('d M Y') ?></small>
            </div>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle d-flex align-items-center justify-content-center avatar-badge" style="width: 38px; height: 38px;">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 mt-3 overflow-hidden dropdown-user-menu shadow-lg" aria-labelledby="dropdownUser">
                    <li class="px-4 py-3 border-bottom dropdown-user-header">
                        <div class="small text-muted mb-1">Masuk sebagai:</div>
                        <span class="d-block fw-bold text-dark dropdown-user-title"><?= $_SESSION['nama_lengkap'] ?></span>
                        <span class="badge rounded-pill bg-primary-subtle text-primary fw-medium mt-2 dropdown-user-role">
                            <i class="fas fa-shield-alt me-1"></i> <?= strtoupper($_SESSION['role']) ?>
                        </span>
                    </li>
                    
                    <?php if (!$is_locked): ?>
                    <li>
                        <a class="dropdown-item d-flex align-items-center mt-2" href="index.php?page=akun_saya">
                            <i class="fas fa-cog me-3 text-primary logout-icon"></i> 
                            <span>Pengaturan Akun</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li><hr class="dropdown-divider my-2 opacity-25"></li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center mb-2 text-danger btn-logout" href="logout.php">
                            <i class="fas fa-sign-out-alt me-3 logout-icon"></i> 
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