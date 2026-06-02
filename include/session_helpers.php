<?php
/**
 * Shared session utilities (timeout, secure logout).
 */
require_once __DIR__ . '/user_accounts.php';

function session_timeout_seconds(): int
{
    if (!isset($_ENV['SESSION_TIMEOUT'])) {
        require_once __DIR__ . '/env_loader.php';
    }
    if (defined('SESSION_TIMEOUT')) {
        return (int) SESSION_TIMEOUT;
    }
    return (int) ($_ENV['SESSION_TIMEOUT'] ?? 7200);
}

function destroy_session_fully(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        $cookieParams = [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $params['secure'] ?? false,
            'httponly' => $params['httponly'] ?? true,
            'samesite' => 'Lax'
        ];
        // Use options array if supported
        if (PHP_VERSION_ID >= 70300) {
            setcookie(session_name(), '', $cookieParams);
        } else {
            // Fallback for older PHP versions
            setcookie(session_name(), '', $cookieParams['expires'], $cookieParams['path'] . '; samesite=' . $cookieParams['samesite'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
        }
    }
    session_destroy();
}

function start_user_session(int $userId, string $userName): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (headers_sent($file, $line)) {
        throw new RuntimeException("Cannot start user session after output has started at {$file}:{$line}.");
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['last_activity'] = time();
}

function enforce_user_session_timeout(?PDO $db = null): void
{
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    if ($db) {
        try {
            $user = fetchPublicUserById($db, (int)$_SESSION['user_id']);
            if (!publicUserCanAccess($user)) {
                destroy_session_fully();
                if (session_status() === PHP_SESSION_NONE) {
                    if (function_exists('start_app_session')) {
                        start_app_session();
                    } else {
                        session_start();
                    }
                }
                $_SESSION['account_notice'] = 'This account is not currently available. Please contact the dealership team.';
                return;
            }
        } catch (Throwable $e) {
            error_log('Public user session check failed: ' . $e->getMessage());
        }
    }

    $timeout = session_timeout_seconds();
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        destroy_session_fully();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return;
    }
    $_SESSION['last_activity'] = time();
}

function enforce_admin_session_timeout(): void
{
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return;
    }
    $timeout = session_timeout_seconds();
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > $timeout) {
        destroy_session_fully();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['admin_last_activity'] = time();
}
