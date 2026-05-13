<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/include/db.php';
require_once __DIR__ . '/config.php';

function is_authenticated(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_auth(): void {
    if (!is_authenticated()) {
        header('Location: login.php');
        exit();
    }
}

function authenticate(string $username, string $password): bool {
    $admin_user = $_ENV['ADMIN_USERNAME'] ?? ADMIN_USERNAME;
    $admin_hash = $_ENV['ADMIN_PASSWORD_HASH'] ?? ADMIN_PASSWORD_HASH;
    
    if ($username === $admin_user && password_verify($password, $admin_hash)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        return true;
    }
    return false;
}

function logout(): void {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    session_destroy();
}

function init_csrf(): void {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function verify_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function validate_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf($token)) {
            http_response_code(403);
            die('Invalid CSRF token');
        }
    }
}

init_csrf();