<?php

require_once __DIR__ . '/auth.php';

if (!function_exists('audit_mask_context_value')) {
    function audit_mask_context_value($key, $value) {
        $maskedKeys = [
            'password', 'pass', 'token', 'secret', 'api_key', 'apikey',
            'authorization', 'cookie', 'remember_token', 'csrf_token'
        ];

        $k = strtolower((string)$key);
        foreach ($maskedKeys as $needle) {
            if ($k === $needle || strpos($k, $needle) !== false) {
                return '[REDACTED]';
            }
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $subKey => $subValue) {
                $clean[$subKey] = audit_mask_context_value((string)$subKey, $subValue);
            }
            return $clean;
        }

        return $value;
    }
}

if (!function_exists('audit_mask_context')) {
    function audit_mask_context(array $context) {
        $clean = [];
        foreach ($context as $key => $value) {
            $clean[$key] = audit_mask_context_value((string)$key, $value);
        }
        return $clean;
    }
}

if (!function_exists('audit_log_file_path')) {
    function audit_log_file_path() {
        return __DIR__ . DIRECTORY_SEPARATOR . 'audit.log';
    }
}

if (!function_exists('audit_archive_files')) {
    function audit_archive_files() {
        audit_prune_old_archives();

        $pattern = __DIR__ . DIRECTORY_SEPARATOR . 'audit-*.log';
        $files = glob($pattern);
        if (!is_array($files)) {
            return [];
        }

        rsort($files);
        $result = [];
        foreach ($files as $filePath) {
            $name = basename($filePath);
            $result[] = [
                'name' => $name,
                'path' => $filePath,
                'size_bytes' => (int)@filesize($filePath),
                'modified_at' => @filemtime($filePath) ?: null,
            ];
        }

        return $result;
    }
}

if (!function_exists('audit_prune_old_archives')) {
    function audit_prune_old_archives($maxAgeDays = null) {
        if ($maxAgeDays === null) {
            $env = getenv('AUDIT_RETENTION_DAYS');
            $maxAgeDays = is_string($env) && $env !== '' ? (int)$env : 30;
        }

        $maxAgeDays = max(1, min(3650, (int)$maxAgeDays));
        $cutoff = time() - ($maxAgeDays * 86400);

        $archives = glob(__DIR__ . DIRECTORY_SEPARATOR . 'audit-*.log');
        if (!is_array($archives)) {
            return;
        }

        foreach ($archives as $filePath) {
            $mtime = @filemtime($filePath);
            if ($mtime !== false && $mtime < $cutoff) {
                @unlink($filePath);
            }
        }
    }
}

if (!function_exists('audit_rotate_if_needed')) {
    function audit_rotate_if_needed($maxBytes = 2097152) {
        audit_prune_old_archives();

        $logFile = audit_log_file_path();

        if (!file_exists($logFile)) {
            return;
        }

        $size = @filesize($logFile);
        if ($size === false || $size < $maxBytes) {
            return;
        }

        $archive = __DIR__ . DIRECTORY_SEPARATOR . 'audit-' . date('Ymd-His') . '.log';
        @rename($logFile, $archive);

        // Keep latest 10 archived files to avoid uncontrolled growth.
        $archives = glob(__DIR__ . DIRECTORY_SEPARATOR . 'audit-*.log');
        if (is_array($archives) && count($archives) > 10) {
            sort($archives);
            $toDelete = array_slice($archives, 0, count($archives) - 10);
            foreach ($toDelete as $oldFile) {
                @unlink($oldFile);
            }
        }

        audit_prune_old_archives();
    }
}

if (!function_exists('audit_file_info')) {
    function audit_file_info() {
        $logFile = audit_log_file_path();
        if (!file_exists($logFile)) {
            return [
                'exists' => false,
                'size_bytes' => 0,
                'modified_at' => null
            ];
        }

        return [
            'exists' => true,
            'size_bytes' => (int)@filesize($logFile),
            'modified_at' => @filemtime($logFile) ?: null
        ];
    }
}

if (!function_exists('audit_read_entries')) {
    function audit_read_entries($limit = 200, array $filters = [], $offset = 0) {
        $logFile = audit_log_file_path();
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $eventFilter = strtolower(trim((string)($filters['event'] ?? '')));
        $userIdFilter = isset($filters['user_id']) && $filters['user_id'] !== '' ? (int)$filters['user_id'] : null;
        $fromFilter = !empty($filters['from']) ? strtotime((string)$filters['from'] . ' 00:00:00') : null;
        $toFilter = !empty($filters['to']) ? strtotime((string)$filters['to'] . ' 23:59:59') : null;

        $result = [];
        $matched = 0;
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $row = json_decode($lines[$i], true);
            if (!is_array($row)) {
                continue;
            }

            if (isset($row['context']) && is_array($row['context'])) {
                $row['context'] = audit_mask_context($row['context']);
            }

            $event = strtolower((string)($row['event'] ?? ''));
            $userId = isset($row['user_id']) ? (int)$row['user_id'] : 0;
            $ts = isset($row['ts']) ? strtotime((string)$row['ts']) : false;

            if ($eventFilter !== '' && strpos($event, $eventFilter) === false) {
                continue;
            }

            if ($userIdFilter !== null && $userId !== $userIdFilter) {
                continue;
            }

            if ($fromFilter !== null && $ts !== false && $ts < $fromFilter) {
                continue;
            }

            if ($toFilter !== null && $ts !== false && $ts > $toFilter) {
                continue;
            }

            if ($matched < $offset) {
                $matched++;
                continue;
            }

            $result[] = $row;
            $matched++;
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }
}

if (!function_exists('audit_count_entries')) {
    function audit_count_entries(array $filters = []) {
        $logFile = audit_log_file_path();
        if (!file_exists($logFile)) {
            return 0;
        }

        $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return 0;
        }

        $eventFilter = strtolower(trim((string)($filters['event'] ?? '')));
        $userIdFilter = isset($filters['user_id']) && $filters['user_id'] !== '' ? (int)$filters['user_id'] : null;
        $fromFilter = !empty($filters['from']) ? strtotime((string)$filters['from'] . ' 00:00:00') : null;
        $toFilter = !empty($filters['to']) ? strtotime((string)$filters['to'] . ' 23:59:59') : null;

        $count = 0;
        foreach ($lines as $line) {
            $row = json_decode($line, true);
            if (!is_array($row)) {
                continue;
            }

            $event = strtolower((string)($row['event'] ?? ''));
            $userId = isset($row['user_id']) ? (int)$row['user_id'] : 0;
            $ts = isset($row['ts']) ? strtotime((string)$row['ts']) : false;

            if ($eventFilter !== '' && strpos($event, $eventFilter) === false) {
                continue;
            }

            if ($userIdFilter !== null && $userId !== $userIdFilter) {
                continue;
            }

            if ($fromFilter !== null && $ts !== false && $ts < $fromFilter) {
                continue;
            }

            if ($toFilter !== null && $ts !== false && $ts > $toFilter) {
                continue;
            }

            $count++;
        }

        return $count;
    }
}

if (!function_exists('audit_log_event')) {
    function audit_log_event($event, array $context = []) {
        auth_session_start();

        audit_rotate_if_needed();
        $context = audit_mask_context($context);

        $entry = [
            'ts' => date('c'),
            'event' => (string)$event,
            'user_id' => auth_current_user_id(),
            'role' => auth_current_role(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'context' => $context,
        ];

        $logFile = audit_log_file_path();
        $line = json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL;

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
