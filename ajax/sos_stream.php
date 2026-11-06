<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../config/database.php';

// Disable time limit for SSE stream connection loop
@set_time_limit(0);

$lastCheck = time();

while (true) {
    if (connection_aborted()) {
        break;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications 
                               WHERE category = 'alert' 
                               AND is_read = 0 
                               AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stmt->execute();
        $count = (int)$stmt->fetchColumn();

        $data = json_encode(['emergency' => ($count > 0), 'count' => $count, 'ts' => date('c')]);
        echo "data: {$data}\n\n";
        @ob_flush();
        @flush();
    } catch (Exception $e) {
        $data = json_encode(['emergency' => false, 'error' => true]);
        echo "data: {$data}\n\n";
        @ob_flush();
        @flush();
    }

    sleep(3);
}
