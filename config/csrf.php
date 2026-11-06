<?php

if (!function_exists('ensure_csrf_token')) {
    function ensure_csrf_token() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_token_value')) {
    function csrf_token_value() {
        return ensure_csrf_token();
    }
}

if (!function_exists('csrf_validate_request')) {
    function csrf_validate_request($tokenField = 'csrf_token') {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            return false;
        }

        $requestToken = $_POST[$tokenField] ?? $_GET[$tokenField] ?? '';
        if (!is_string($requestToken) || $requestToken === '') {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $requestToken);
    }
}
