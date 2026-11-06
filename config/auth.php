<?php

if (!function_exists('auth_session_start')) {
    function auth_session_start() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}

if (!function_exists('auth_current_user_id')) {
    function auth_current_user_id() {
        auth_session_start();

        if (isset($_SESSION['id_user'])) {
            return (int)$_SESSION['id_user'];
        }

        if (isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        return 0;
    }
}

if (!function_exists('auth_current_role')) {
    function auth_current_role() {
        auth_session_start();
        return strtolower(trim((string)($_SESSION['role'] ?? '')));
    }
}

if (!function_exists('auth_current_name')) {
    function auth_current_name() {
        auth_session_start();
        return (string)($_SESSION['nama_lengkap'] ?? '');
    }
}

if (!function_exists('auth_require_login')) {
    function auth_require_login($redirect = 'login.php') {
        $userId = auth_current_user_id();
        if ($userId <= 0) {
            header('Location: ' . $redirect);
            exit;
        }

        return $userId;
    }
}

if (!function_exists('auth_require_roles')) {
    function auth_require_roles(array $allowedRoles) {
        $role = auth_current_role();
        if (!in_array($role, $allowedRoles, true)) {
            http_response_code(403);
            exit('Akses ditolak');
        }

        return $role;
    }
}

if (!function_exists('record_login_log')) {
    function record_login_log($pdo, $username, $status, $message) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Kolom aktual di tabel login_logs adalah keterangan & created_at,
        // bukan message & login_time.
        $query = "INSERT INTO login_logs (username, status, keterangan, created_at, ip_address, user_agent) 
                  VALUES (:username, :status, :keterangan, NOW(), :ip_address, :user_agent)";
                  
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':username'   => $username,
                ':status'     => $status,
                ':keterangan' => $message,
                ':ip_address' => $ip_address,
                ':user_agent' => $user_agent
            ]);
        } catch (PDOException $e) {
        }
    }
}