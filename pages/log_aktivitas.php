<?php
// Proteksi: Hanya Administrator yang boleh masuk
if (strtolower($role) !== 'admin' && strtolower($role) !== 'administrator') {
    echo "<script>location.href='index.php?page=dashboard';</script>";
    exit;
}

require_once 'config/audit.php';

$loginSearch = trim($_GET['login_q'] ?? '');
$loginStatus = trim($_GET['login_status'] ?? '');
$loginFrom   = trim($_GET['login_from'] ?? '');
$loginTo     = trim($_GET['login_to'] ?? '');

$sqlLogin = "SELECT * FROM login_logs WHERE 1=1";
$paramsLogin = [];
if ($loginSearch !== '') {
    $sqlLogin .= " AND (username LIKE :q OR ip_address LIKE :q OR keterangan LIKE :q)";
    $paramsLogin['q'] = "%$loginSearch%";
}
if ($loginStatus !== '') {
    $sqlLogin .= " AND status = :st";
    $paramsLogin['st'] = $loginStatus;
}
if ($loginFrom !== '') {
    $sqlLogin .= " AND DATE(created_at) >= :lfrom";
    $paramsLogin['lfrom'] = $loginFrom;
}
if ($loginTo !== '') {
    $sqlLogin .= " AND DATE(created_at) <= :lto";
    $paramsLogin['lto'] = $loginTo;
}
$sqlLogin .= " ORDER BY created_at DESC LIMIT 200";
$stmtLogin = $pdo->prepare($sqlLogin);
$stmtLogin->execute($paramsLogin);
$logs = $stmtLogin->fetchAll();

if (!function_exists('humanizeAuditEvent')) {
    function humanizeAuditEvent($event) {
        $map = [
            'auth.login' => ['text' => 'Login User', 'badge' => 'primary'],
            'presensi.masuk' => ['text' => 'Presensi Masuk', 'badge' => 'success'],
            'presensi.pulang' => ['text' => 'Presensi Pulang', 'badge' => 'info'],
            'presensi.lembur' => ['text' => 'Pengajuan Lembur', 'badge' => 'warning'],
            'lembur.approval' => ['text' => 'Approval Lembur', 'badge' => 'primary'],
            'izin.submit' => ['text' => 'Pengajuan Izin/Sakit', 'badge' => 'warning'],
            'izin.approval' => ['text' => 'Approval Izin', 'badge' => 'primary'],
            'patroli.scan' => ['text' => 'Scan Titik Patroli', 'badge' => 'success'],
            'patroli.temuan' => ['text' => 'Temuan Insiden Patroli', 'badge' => 'danger'],
            'barang.pinjam' => ['text' => 'Pinjam Barang', 'badge' => 'info'],
            'barang.kembali' => ['text' => 'Kembalikan Barang', 'badge' => 'success'],
            'barang.masalah' => ['text' => 'Lapor Masalah Barang', 'badge' => 'danger'],
            'kendaraan.pakai' => ['text' => 'Pakai Kendaraan', 'badge' => 'info'],
            'kendaraan.kembali' => ['text' => 'Kembalikan Kendaraan', 'badge' => 'success'],
            'kendaraan.masalah' => ['text' => 'Lapor Masalah Kendaraan', 'badge' => 'danger'],
            'visitor.checkin' => ['text' => 'Check-In Tamu', 'badge' => 'success'],
            'visitor.checkout' => ['text' => 'Check-Out Tamu', 'badge' => 'secondary'],
            'panic_button' => ['text' => 'Sinyal SOS Darurat', 'badge' => 'danger'],
            'chat.rate_limited' => ['text' => 'Chat Rate Limited', 'badge' => 'danger'],
            'forum.topik.delete' => ['text' => 'Hapus Topik Forum', 'badge' => 'warning'],
        ];
        $lower = strtolower($event);
        if (isset($map[$lower])) return $map[$lower];
        foreach ($map as $key => $val) {
            if (strpos($lower, $key) === 0) return $val;
        }
        return ['text' => ucwords(str_replace(['.', '_'], ' ', $event)), 'badge' => 'secondary'];
    }
}

$auditEvent = trim($_GET['audit_event'] ?? '');
$auditUserId = trim($_GET['audit_user_id'] ?? '');
$auditFrom = trim($_GET['audit_from'] ?? '');
$auditTo = trim($_GET['audit_to'] ?? '');
$auditPreset = trim($_GET['audit_preset'] ?? '');
$auditMode = trim($_GET['audit_mode'] ?? '');

if ($auditPreset !== '' && $auditMode === '') {
    $auditMode = 'anomaly';
}

$isAnomalyMode = ($auditMode === 'anomaly');

$auditPage = max(1, (int)($_GET['audit_page'] ?? 1));
$auditPerPage = 50;

$auditFilters = [
    'event' => $auditEvent,
    'user_id' => $auditUserId,
    'from' => $auditFrom,
    'to' => $auditTo,
];

if (!function_exists('isAnomalyAuditEvent')) {
    function isAnomalyAuditEvent($eventName) {
        $event = strtolower((string)$eventName);
        return (
            strpos($event, 'chat.rate_limited') === 0 ||
            strpos($event, 'chat.upload_blocked') === 0 ||
            strpos($event, 'visitor.submit.invalid') === 0 ||
            strpos($event, 'forum.topik.delete') === 0
        );
    }
}

if (!function_exists('matchesAuditPreset')) {
    function matchesAuditPreset($eventName, $preset) {
        $event = strtolower((string)$eventName);
        $preset = strtolower(trim((string)$preset));

        if ($preset === '' || $preset === 'all') {
            return true;
        }

        if ($preset === 'chat_abuse') {
            return strpos($event, 'chat.rate_limited') === 0 || strpos($event, 'chat.upload_blocked') === 0;
        }

        if ($preset === 'visitor_invalid') {
            return strpos($event, 'visitor.submit.invalid') === 0;
        }

        if ($preset === 'forum_delete') {
            return strpos($event, 'forum.topik.delete') === 0;
        }

        return true;
    }
}

$archives = audit_archive_files();

if (isset($_GET['audit_download'])) {
    $requested = trim((string)$_GET['audit_download']);

    if ($requested === 'current') {
        $path = audit_log_file_path();
        $name = 'audit.log';
    } else {
        $path = null;
        $name = '';
        foreach ($archives as $archive) {
            if ($archive['name'] === $requested) {
                $path = $archive['path'];
                $name = $archive['name'];
                break;
            }
        }
    }

    if ($path && file_exists($path)) {
        // Buang HTML sidebar/navbar yang sudah tertampung di buffer agar header() tidak gagal.
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }
}

$auditEntries = [];
$exportEntries = [];

if ($isAnomalyMode) {
    $sourceFilters = [
        'event' => '',
        'user_id' => $auditUserId,
        'from' => $auditFrom,
        'to' => $auditTo,
    ];
    $anomalyPool = audit_read_entries(5000, $sourceFilters, 0);
    $filteredAnomalies = [];
    foreach ($anomalyPool as $entry) {
        $eventName = (string)($entry['event'] ?? '');
        if (!isAnomalyAuditEvent($eventName)) {
            continue;
        }

        if (!matchesAuditPreset($eventName, $auditPreset)) {
            continue;
        }

        if ($auditEvent !== '' && stripos($eventName, $auditEvent) === false) {
            continue;
        }

        $filteredAnomalies[] = $entry;
    }

    $auditTotal = count($filteredAnomalies);
    $auditTotalPages = max(1, (int)ceil($auditTotal / $auditPerPage));
    if ($auditPage > $auditTotalPages) {
        $auditPage = $auditTotalPages;
    }
    $auditOffset = ($auditPage - 1) * $auditPerPage;

    $auditEntries = array_slice($filteredAnomalies, $auditOffset, $auditPerPage);
    $exportEntries = $filteredAnomalies;
} else {
    $auditTotal = audit_count_entries($auditFilters);
    $auditTotalPages = max(1, (int)ceil($auditTotal / $auditPerPage));
    if ($auditPage > $auditTotalPages) {
        $auditPage = $auditTotalPages;
    }
    $auditOffset = ($auditPage - 1) * $auditPerPage;

    $auditEntries = audit_read_entries($auditPerPage, $auditFilters, $auditOffset);
    $exportEntries = audit_read_entries(5000, $auditFilters, 0);
}

if (($_GET['audit_export'] ?? '') === 'csv') {
    $filename = 'audit-log-' . date('Ymd-His') . '.csv';
    // Buang HTML sidebar/navbar yang sudah tertampung di buffer agar header() tidak gagal.
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['timestamp', 'event', 'user_id', 'role', 'ip', 'context'], ',', '"', '\\');

    foreach ($exportEntries as $entry) {
        $contextJson = json_encode($entry['context'] ?? [], JSON_UNESCAPED_SLASHES);
        if ($contextJson === false) {
            $contextJson = '{}';
        }

        fputcsv($out, [
            (string)($entry['ts'] ?? ''),
            (string)($entry['event'] ?? ''),
            (int)($entry['user_id'] ?? 0),
            (string)($entry['role'] ?? ''),
            (string)($entry['ip'] ?? ''),
            $contextJson,
        ], ',', '"', '\\');
    }

    fclose($out);
    exit;
}

$auditInfo = audit_file_info();

$anomChatRateLimited = audit_count_entries(['event' => 'chat.rate_limited']);
$anomKendaraanOps = audit_count_entries(['event' => 'kendaraan.']);
$anomVisitorOps = audit_count_entries(['event' => 'visitor.']);
$anomForumDelete = audit_count_entries(['event' => 'forum.topik.delete']);

$recentAuditSample = audit_read_entries(500, [], 0);
$recentAnomalies = [];
foreach ($recentAuditSample as $entry) {
    $eventName = (string)($entry['event'] ?? '');
    if (
        strpos($eventName, 'chat.rate_limited') === 0 ||
        strpos($eventName, 'visitor.submit.invalid') === 0 ||
        strpos($eventName, 'forum.topik.delete') === 0
    ) {
        $recentAnomalies[] = $entry;
        if (count($recentAnomalies) >= 8) {
            break;
        }
    }
}

function formatBytes($bytes) {
    $bytes = (int)$bytes;
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

function buildAuditUrl(array $overrides = []) {
    $params = array_merge([
        'page' => 'log_aktivitas',
        'audit_preset' => $_GET['audit_preset'] ?? '',
        'audit_mode' => $_GET['audit_mode'] ?? '',
        'audit_event' => $_GET['audit_event'] ?? '',
        'audit_user_id' => $_GET['audit_user_id'] ?? '',
        'audit_from' => $_GET['audit_from'] ?? '',
        'audit_to' => $_GET['audit_to'] ?? '',
        'audit_page' => $_GET['audit_page'] ?? '1',
    ], $overrides);

    return 'index.php?' . http_build_query($params);
}
?>

<div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up">
    <div>
        <h3 class="fw-bold text-dark mb-1">Audit Aktivitas Sistem</h3>
        <p class="text-secondary mb-0 small">
            Log login + audit keamanan aksi sensitif.
            <?php if ($isAnomalyMode): ?>
                <span class="badge bg-danger-subtle text-danger ms-1">Mode: Anomali Saja</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars(buildAuditUrl(['audit_mode' => ($isAnomalyMode ? '' : 'anomaly'), 'audit_page' => '1'])) ?>"
           class="btn <?= $isAnomalyMode ? 'btn-danger' : 'btn-outline-danger' ?> btn-sm rounded-pill px-3">
            <i class="fas fa-triangle-exclamation me-1"></i> Anomali Saja
        </a>
        <a href="<?= htmlspecialchars(buildAuditUrl(['audit_mode' => 'anomaly', 'audit_preset' => 'chat_abuse', 'audit_page' => '1'])) ?>"
           class="btn <?= $auditPreset === 'chat_abuse' ? 'btn-danger' : 'btn-outline-danger' ?> btn-sm rounded-pill px-3">
            Chat Abuse
        </a>
        <a href="<?= htmlspecialchars(buildAuditUrl(['audit_mode' => 'anomaly', 'audit_preset' => 'visitor_invalid', 'audit_page' => '1'])) ?>"
           class="btn <?= $auditPreset === 'visitor_invalid' ? 'btn-warning text-dark' : 'btn-outline-warning' ?> btn-sm rounded-pill px-3">
            Visitor Invalid
        </a>
        <a href="<?= htmlspecialchars(buildAuditUrl(['audit_mode' => 'anomaly', 'audit_preset' => 'forum_delete', 'audit_page' => '1'])) ?>"
           class="btn <?= $auditPreset === 'forum_delete' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm rounded-pill px-3">
            Forum Delete
        </a>
        <a href="index.php?page=log_aktivitas" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fas fa-rotate me-1"></i> Reset Filter
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Chat Rate Limited</div>
                <div class="fs-4 fw-bold text-danger"><?= (int)$anomChatRateLimited ?></div>
                <div class="small text-muted">Total event `chat.rate_limited`</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Operasional Kendaraan</div>
                <div class="fs-4 fw-bold text-primary"><?= (int)$anomKendaraanOps ?></div>
                <div class="small text-muted">Total event `kendaraan.*`</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Aktivitas Visitor</div>
                <div class="fs-4 fw-bold text-success"><?= (int)$anomVisitorOps ?></div>
                <div class="small text-muted">Total event `visitor.*`</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">Forum Delete</div>
                <div class="fs-4 fw-bold text-warning"><?= (int)$anomForumDelete ?></div>
                <div class="small text-muted">Total event `forum.topik.delete`</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
    <div class="card-header bg-white py-3 border-0">
        <h6 class="mb-0 fw-bold text-dark">Anomali Terkini</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>Event</th>
                        <th>User</th>
                        <th>IP</th>
                        <th>Context</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($recentAnomalies)): ?>
                        <tr><td colspan="5" class="ps-4 py-4 text-muted">Belum ada event anomali pada sampel log terbaru.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentAnomalies as $entry): ?>
                            <?php
                                $ctx = json_encode($entry['context'] ?? [], JSON_UNESCAPED_SLASHES);
                                if ($ctx === false) {
                                    $ctx = '{}';
                                }
                                $evInfo = humanizeAuditEvent($entry['event'] ?? '');
                            ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($entry['ts'] ?? 'now'))) ?></td>
                                <td><span class="badge bg-<?= $evInfo['badge'] ?>-subtle text-<?= $evInfo['badge'] ?> border border-<?= $evInfo['badge'] ?>-subtle px-3 rounded-pill"><?= htmlspecialchars($evInfo['text']) ?></span></td>
                                <td><span class="fw-bold">#<?= (int)($entry['user_id'] ?? 0) ?></span></td>
                                <td class="font-monospace text-primary"><?= htmlspecialchars($entry['ip'] ?? '-') ?></td>
                                <td class="text-muted" style="max-width: 420px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($ctx) ?>">
                                    <?= htmlspecialchars($ctx) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-master border-0 shadow-sm mb-4" style="border-radius: 16px;">
    <div class="card-header bg-white py-3 border-0">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-sign-in-alt text-primary me-2"></i>Riwayat Percobaan Login (Login Logs)</h6>
                <small class="text-muted">Total: <?= count($logs) ?> rekaman aktivitas login</small>
            </div>
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="page" value="log_aktivitas">
                <div class="input-group input-group-sm search-input-group" style="width: 200px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="login_q" class="form-control" value="<?= htmlspecialchars($loginSearch) ?>" placeholder="Cari user / IP...">
                </div>
                <select name="login_status" class="form-select form-select-sm" style="width: 120px;">
                    <option value="">Semua Status</option>
                    <option value="Success" <?= $loginStatus === 'Success' ? 'selected' : '' ?>>Success</option>
                    <option value="Failed" <?= $loginStatus === 'Failed' ? 'selected' : '' ?>>Failed</option>
                    <option value="Frozen" <?= $loginStatus === 'Frozen' ? 'selected' : '' ?>>Frozen</option>
                </select>
                <input type="date" name="login_from" class="form-control form-control-sm" style="width: 130px;" value="<?= htmlspecialchars($loginFrom) ?>" title="Dari Tanggal">
                <input type="date" name="login_to" class="form-control form-control-sm" style="width: 130px;" value="<?= htmlspecialchars($loginTo) ?>" title="Sampai Tanggal">
                <button type="submit" class="btn btn-primary btn-sm px-3">Cari</button>
                <?php if ($loginSearch || $loginStatus || $loginFrom || $loginTo): ?>
                    <a href="index.php?page=log_aktivitas" class="btn btn-light btn-sm text-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-master mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 18%;">Waktu</th>
                        <th style="width: 18%;">Username</th>
                        <th style="width: 18%;">IP Address</th>
                        <th style="width: 14%;">Status</th>
                        <th>Detail Keterangan</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat login sesuai filter.</td></tr>
                    <?php else: foreach ($logs as $log): ?>
                    <tr>
                        <td class="ps-4"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($log['username']) ?></td>
                        <td class="font-monospace text-primary"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                        <td>
                            <?php if($log['status'] == 'Success'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 rounded-pill">SUCCESS</span>
                            <?php elseif($log['status'] == 'Frozen'): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 rounded-pill">FROZEN</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 rounded-pill">FAILED</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($log['keterangan']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-master border-0 shadow-sm" style="border-radius: 16px;">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <h6 class="mb-1 fw-bold text-dark"><i class="fas fa-shield-alt text-primary me-2"></i>Audit Keamanan (Structured Activity Logs)</h6>
                <small class="text-muted">
                    File: config/audit.log |
                    Size: <?= formatBytes($auditInfo['size_bytes']) ?> |
                    Last Update: <?= $auditInfo['modified_at'] ? date('d/m/Y H:i:s', $auditInfo['modified_at']) : '-' ?>
                </small>
                <div class="mt-2 d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-dark btn-sm rounded-pill" href="<?= htmlspecialchars(buildAuditUrl(['audit_download' => 'current'])) ?>">Download audit.log</a>
                    <?php foreach (array_slice($archives, 0, 5) as $archive): ?>
                        <a class="btn btn-outline-secondary btn-sm rounded-pill"
                           href="<?= htmlspecialchars(buildAuditUrl(['audit_download' => $archive['name']])) ?>"
                           title="<?= htmlspecialchars($archive['name']) ?>">
                            <?= htmlspecialchars($archive['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="log_aktivitas">
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Pilih Event</label>
                    <select name="audit_event" class="form-select form-select-sm">
                        <option value="">-- Semua Event --</option>
                        <option value="presensi.masuk" <?= $auditEvent === 'presensi.masuk' ? 'selected' : '' ?>>Presensi Masuk</option>
                        <option value="presensi.pulang" <?= $auditEvent === 'presensi.pulang' ? 'selected' : '' ?>>Presensi Pulang</option>
                        <option value="lembur.approval" <?= $auditEvent === 'lembur.approval' ? 'selected' : '' ?>>Approval Lembur</option>
                        <option value="izin." <?= strpos($auditEvent, 'izin') === 0 ? 'selected' : '' ?>>Pengajuan / Approval Izin</option>
                        <option value="patroli." <?= strpos($auditEvent, 'patroli') === 0 ? 'selected' : '' ?>>Aktivitas Patroli</option>
                        <option value="barang." <?= strpos($auditEvent, 'barang') === 0 ? 'selected' : '' ?>>Aktivitas Barang</option>
                        <option value="kendaraan." <?= strpos($auditEvent, 'kendaraan') === 0 ? 'selected' : '' ?>>Aktivitas Kendaraan</option>
                        <option value="visitor." <?= strpos($auditEvent, 'visitor') === 0 ? 'selected' : '' ?>>Kunjungan Tamu</option>
                        <option value="panic_button" <?= $auditEvent === 'panic_button' ? 'selected' : '' ?>>Panggilan Darurat (SOS)</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">User ID</label>
                    <input type="number" name="audit_user_id" class="form-control form-control-sm" value="<?= htmlspecialchars($auditUserId) ?>" min="1" placeholder="ID" style="width: 80px;">
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Dari</label>
                    <input type="date" name="audit_from" class="form-control form-control-sm" value="<?= htmlspecialchars($auditFrom) ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Sampai</label>
                    <input type="date" name="audit_to" class="form-control form-control-sm" value="<?= htmlspecialchars($auditTo) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
                </div>
                <div class="col-auto">
                    <a class="btn btn-outline-success btn-sm rounded-pill"
                       href="<?= htmlspecialchars(buildAuditUrl(['audit_export' => 'csv', 'audit_page' => '1'])) ?>">
                        Export CSV
                    </a>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>Event</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>IP</th>
                        <th>Context</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($auditEntries)): ?>
                        <tr>
                            <td colspan="6" class="ps-4 py-4 text-muted">Tidak ada data audit sesuai filter.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($auditEntries as $entry): ?>
                            <?php
                                $contextJson = json_encode($entry['context'] ?? [], JSON_UNESCAPED_SLASHES);
                                if ($contextJson === false) {
                                    $contextJson = '{}';
                                }
                            ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($entry['ts'] ?? 'now'))) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($entry['event'] ?? '-') ?></span></td>
                                <td><?= (int)($entry['user_id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($entry['role'] ?? '-') ?></td>
                                <td class="font-monospace"><?= htmlspecialchars($entry['ip'] ?? '-') ?></td>
                                <td class="text-muted" style="max-width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($contextJson) ?>">
                                    <?= htmlspecialchars($contextJson) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-top bg-light">
            <div class="small text-muted">
                Menampilkan <?= count($auditEntries) ?> dari <?= (int)$auditTotal ?> data audit.
            </div>
            <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
                <a class="btn btn-outline-secondary <?= $auditPage <= 1 ? 'disabled' : '' ?>"
                   href="<?= htmlspecialchars(buildAuditUrl(['audit_page' => max(1, $auditPage - 1)])) ?>">Sebelumnya</a>
                <span class="btn btn-outline-secondary disabled">Hal <?= (int)$auditPage ?> / <?= (int)$auditTotalPages ?></span>
                <a class="btn btn-outline-secondary <?= $auditPage >= $auditTotalPages ? 'disabled' : '' ?>"
                   href="<?= htmlspecialchars(buildAuditUrl(['audit_page' => min($auditTotalPages, $auditPage + 1)])) ?>">Berikutnya</a>
            </div>
        </div>
    </div>
</div>