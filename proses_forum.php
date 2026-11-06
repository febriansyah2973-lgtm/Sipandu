<?php
require_once 'config/database.php';
require_once 'config/helper_notif.php'; // Panggil Helper Notifikasi
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/audit.php';

auth_session_start();
$my_id = auth_require_login('login.php');

$act = $_GET['act'] ?? $_POST['act'] ?? '';
$my_name = auth_current_name();

try {
    // --- 1. BUAT TOPIK BARU ---
    if ($act == 'buat_topik') {
        if (!csrf_validate_request()) {
            http_response_code(419);
            exit('CSRF token tidak valid');
        }

        $judul    = trim((string)($_POST['judul'] ?? ''));
        $kategori = (int)($_POST['kategori'] ?? 0);
        $konten   = trim((string)($_POST['konten'] ?? ''));

        if ($judul === '' || $kategori <= 0 || $konten === '') {
            audit_log_event('forum.topik.invalid', ['kategori' => $kategori]);
            header("Location: index.php?page=forum_diskusi&msg=invalid");
            exit;
        }

        $sql = "INSERT INTO forum_topics (category_id, user_id, title, content, views, created_at) 
                VALUES (?, ?, ?, ?, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kategori, $my_id, $judul, $konten]);
        $topic_id = (int)$pdo->lastInsertId();
        audit_log_event('forum.topik.create', ['topic_id' => $topic_id, 'kategori' => $kategori]);

        // Opsional: Kirim Notif ke Admin kalau ada topik 'Laporan Lapangan' (Urgent)
        if ($kategori == 2) { // Asumsi ID 2 adalah Laporan
            $admins = getAdminIds($pdo);
            sendNotification($pdo, $admins, 'warning', 'Topik Laporan Baru', "$my_name memposting laporan di forum.", 'index.php?page=forum_diskusi');
        }

        header("Location: index.php?page=forum_diskusi&msg=created");
    }

    // --- 2. KIRIM KOMENTAR ---
    elseif ($act == 'kirim_komentar') {
        if (!csrf_validate_request()) {
            http_response_code(419);
            exit('CSRF token tidak valid');
        }

        $topic_id = (int)($_POST['topic_id'] ?? 0);
        $isi      = trim((string)($_POST['isi_komentar'] ?? ''));

        if ($topic_id <= 0 || $isi === '') {
            audit_log_event('forum.komentar.invalid', ['topic_id' => $topic_id]);
            header("Location: index.php?page=forum_diskusi&msg=invalid");
            exit;
        }

        // Simpan Komentar
        $sql = "INSERT INTO forum_replies (topic_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$topic_id, $my_id, $isi]);
        audit_log_event('forum.komentar.create', ['topic_id' => $topic_id]);

        // --- LOGIC NOTIFIKASI BALASAN ---
        // 1. Cari tahu siapa pemilik topik ini
        $stmtOwner = $pdo->prepare("SELECT user_id, title FROM forum_topics WHERE id = ?");
        $stmtOwner->execute([$topic_id]);
        $topic = $stmtOwner->fetch();

        // 2. Jika yang komen BUKAN pemilik topik, kirim notif ke pemilik
        if ($topic && $topic['user_id'] != $my_id) {
            sendNotification(
                $pdo,
                $topic['user_id'],      // Kirim ke pembuat topik
                'info',                 // Warna biru
                'Komentar Baru ??',
                "$my_name mengomentari postingan Anda: '{$topic['title']}'",
                "index.php?page=forum_detail&id=$topic_id" // Link langsung ke topik
            );
        }

        header("Location: index.php?page=forum_detail&id=$topic_id&msg=replied");
    }

    // --- 3. HAPUS TOPIK (Admin Only) ---
    elseif ($act == 'hapus_topik') {
        auth_require_roles(['admin', 'administrator']);
        if (!csrf_validate_request()) {
            http_response_code(419);
            exit('CSRF token tidak valid');
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            audit_log_event('forum.topik.delete_invalid', ['topic_id' => $id]);
            header("Location: index.php?page=forum_diskusi&msg=invalid");
            exit;
        }

        $pdo->prepare("DELETE FROM forum_topics WHERE id=?")->execute([$id]);
        audit_log_event('forum.topik.delete', ['topic_id' => $id]);
        header("Location: index.php?page=forum_diskusi&msg=deleted");
        exit;
    }

} catch (Exception $e) {
    header("Location: index.php?page=forum_diskusi&msg=error");
    exit;
}

header("Location: index.php?page=forum_diskusi");
exit;
?>