<?php
session_start();
require_once 'config/database.php';
require_once 'config/helper_notif.php'; // Panggil Helper Notifikasi

if (!isset($_SESSION['id_user'])) { header("Location: login.php"); exit; }

$act = $_GET['act'] ?? $_POST['act'] ?? '';
$my_id = $_SESSION['id_user'];
$my_name = $_SESSION['nama_lengkap'];

try {
    // --- 1. BUAT TOPIK BARU ---
    if ($act == 'buat_topik') {
        $judul    = htmlspecialchars($_POST['judul']);
        $kategori = $_POST['kategori'];
        $konten   = htmlspecialchars($_POST['konten']);

        $sql = "INSERT INTO forum_topics (category_id, user_id, title, content, views, created_at) 
                VALUES (?, ?, ?, ?, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kategori, $my_id, $judul, $konten]);

        // Opsional: Kirim Notif ke Admin kalau ada topik 'Laporan Lapangan' (Urgent)
        if ($kategori == 2) { // Asumsi ID 2 adalah Laporan
            $admins = getAdminIds($pdo);
            sendNotification($pdo, $admins, 'warning', 'Topik Laporan Baru', "$my_name memposting laporan di forum.", 'index.php?page=forum_diskusi');
        }

        header("Location: index.php?page=forum_diskusi&msg=created");
    }

    // --- 2. KIRIM KOMENTAR ---
    elseif ($act == 'kirim_komentar') {
        $topic_id = $_POST['topic_id'];
        $isi      = htmlspecialchars($_POST['isi_komentar']);

        // Simpan Komentar
        $sql = "INSERT INTO forum_replies (topic_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$topic_id, $my_id, $isi]);

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
        if ($_SESSION['role'] !== 'admin') { exit; }
        $id = $_GET['id'];
        $pdo->prepare("DELETE FROM forum_topics WHERE id=?")->execute([$id]);
        header("Location: index.php?page=forum_diskusi&msg=deleted");
    }

} catch (Exception $e) {
    die("Error Forum: " . $e->getMessage());
}
?>