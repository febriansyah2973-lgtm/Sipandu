<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_user'])) { exit; }

$act   = $_POST['act'] ?? $_GET['act'] ?? '';
$my_id = $_SESSION['id_user'];
$uploadDir = "../assets/uploads/chat/";

// Buat folder jika belum ada
if (!file_exists($uploadDir)) { mkdir($uploadDir, 0777, true); }

try {
    // --- 1. KIRIM PESAN (+ FILE) ---
    if ($act == 'send') {
        $msg      = htmlspecialchars($_POST['message'] ?? '');
        $reply_id = !empty($_POST['reply_id']) ? $_POST['reply_id'] : NULL;
        $attach   = NULL;

        // LOGIKA UPLOAD FILE
        if (!empty($_FILES['file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
            
            if (in_array($ext, $allowed)) {
                $newName = time() . "_" . uniqid() . "." . $ext;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $newName)) {
                    $attach = $newName;
                }
            }
        }

        // Simpan jika ada pesan ATAU ada file
        if (!empty($msg) || !empty($attach)) {
            $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, reply_to_id, message, attachment, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$my_id, $reply_id, $msg, $attach]);
        }
        echo "sent";
    }

    // --- 2. HAPUS PESAN ---
    elseif ($act == 'delete') {
        $id = $_POST['id'];
        
        // Hapus file fisik dulu jika ada
        $stmt = $pdo->prepare("SELECT attachment FROM chat_messages WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $my_id]);
        $file = $stmt->fetchColumn();
        
        if ($file && file_exists($uploadDir . $file)) { unlink($uploadDir . $file); }

        // Hapus data di DB
        $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $my_id]);
        echo "deleted";
    }

    // --- 3. LOAD PESAN ---
    elseif ($act == 'load') {
        $sql = "SELECT c.*, u.nama_lengkap, u.foto, u.role,
                       r.message AS reply_msg, r.attachment AS reply_att, ru.nama_lengkap AS reply_user
                FROM chat_messages c 
                JOIN users u ON c.user_id = u.id 
                LEFT JOIN chat_messages r ON c.reply_to_id = r.id
                LEFT JOIN users ru ON r.user_id = ru.id
                ORDER BY c.created_at ASC"; 
        
        $chats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($chats as $c) {
            $is_me = ($c['user_id'] == $my_id);
            $align = $is_me ? 'justify-content-end' : 'justify-content-start';
            $bg    = $is_me ? 'bg-primary text-white' : 'bg-white text-dark border';
            $time  = date('H:i', strtotime($c['created_at']));
            $img   = $c['foto'] ? "assets/img/users/{$c['foto']}" : "https://ui-avatars.com/api/?name={$c['nama_lengkap']}&background=random";

            // HTML UNTUK FILE ATTACHMENT
            $file_html = '';
            if ($c['attachment']) {
                $file_path = "assets/uploads/chat/" . $c['attachment'];
                $ext = strtolower(pathinfo($c['attachment'], PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    // Jika Gambar: Tampilkan Preview
                    $file_html = "<div class='mb-2'><a href='$file_path' target='_blank'><img src='$file_path' class='img-fluid rounded border' style='max-height: 200px;'></a></div>";
                } else {
                    // Jika Dokumen: Tampilkan Link Download
                    $icon = ($ext == 'pdf') ? 'fa-file-pdf text-danger' : 'fa-file-alt text-secondary';
                    $file_html = "
                    <div class='mb-2 p-2 rounded bg-light border d-flex align-items-center gap-2'>
                        <i class='fas $icon fa-lg'></i>
                        <div class='overflow-hidden'>
                            <a href='$file_path' target='_blank' class='d-block text-dark fw-bold text-decoration-none text-truncate' style='font-size:0.8rem; max-width:150px;'>Download File</a>
                            <small class='text-muted text-uppercase' style='font-size:0.6rem;'>$ext File</small>
                        </div>
                    </div>";
                }
            }

            // HTML KOTAK REPLY
            $reply_html = '';
            if ($c['reply_to_id']) {
                $rep_content = !empty($c['reply_msg']) ? $c['reply_msg'] : ($c['reply_att'] ? '<i>[Mengirim File]</i>' : '');
                $reply_html = "
                <div class='mb-1 p-2 rounded small border-start border-4 border-warning' style='background: rgba(0,0,0,0.05); font-size: 0.8rem;'>
                    <strong class='d-block text-warning'>{$c['reply_user']}</strong>
                    <span class='text-truncate d-block' style='max-width: 200px; opacity: 0.8;'>$rep_content</span>
                </div>";
            }

            // DROPDOWN MENU
            $actions = "
            <div class='dropdown d-inline-block ms-2 align-self-center'>
                <button class='btn btn-sm btn-link text-secondary p-0' data-bs-toggle='dropdown' style='opacity:0.5'><i class='fas fa-ellipsis-v fa-xs'></i></button>
                <ul class='dropdown-menu dropdown-menu-end shadow-sm py-1' style='font-size:0.85rem'>
                    <li><a class='dropdown-item' href='#' onclick='replyMessage({$c['id']}, \"{$c['nama_lengkap']}\", \"" . htmlspecialchars(substr($c['message'], 0, 30)) . "\")'><i class='fas fa-reply me-2 text-primary'></i>Balas</a></li>
                    " . ($is_me ? "<li><a class='dropdown-item text-danger' href='#' onclick='deleteMessage({$c['id']})'><i class='fas fa-trash me-2'></i>Hapus</a></li>" : "") . "
                </ul>
            </div>";

            echo "
            <div class='d-flex $align mb-3 animate-fade-up group-chat-item'>
                " . (!$is_me ? "<img src='$img' class='rounded-circle me-2 align-self-end' width='30' height='30'>" : "") . "
                <div class='d-flex " . ($is_me ? "flex-row-reverse" : "flex-row") . "'>
                    <div class='d-flex flex-column " . ($is_me ? "align-items-end" : "align-items-start") . "' style='max-width: 100%;'>
                        " . (!$is_me ? "<small class='fw-bold text-secondary mb-1' style='font-size:0.75rem'>{$c['nama_lengkap']}</small>" : "") . "
                        <div class='p-3 rounded-4 shadow-sm $bg position-relative' style='min-width: 150px;'>
                            $reply_html
                            $file_html
                            <div style='word-wrap: break-word;'>{$c['message']}</div>
                            <small class='d-block mt-1 text-end' style='font-size: 0.65rem; opacity: 0.7;'>$time</small>
                        </div>
                    </div>
                    $actions
                </div>
            </div>";
        }
    }
} catch (Exception $e) { echo "error"; }
?>