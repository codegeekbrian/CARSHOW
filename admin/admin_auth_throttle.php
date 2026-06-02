<?php
/**
 * Admin login throttling helpers.
 */

function admin_login_username_hash(string $username): string
{
    return hash('sha256', strtolower(trim($username)));
}

function admin_login_ip_address(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 0, 45);
}

function admin_login_is_locked(PDO $db, string $username): bool
{
    try {
        $stmt = $db->prepare('
            SELECT locked_until
            FROM admin_login_attempts
            WHERE username_hash = ? AND ip_address = ?
            LIMIT 1
        ');
        $stmt->execute([admin_login_username_hash($username), admin_login_ip_address()]);
        $lockedUntil = $stmt->fetchColumn();
        return $lockedUntil !== false && $lockedUntil !== null && strtotime((string)$lockedUntil) > time();
    } catch (Throwable $e) {
        error_log('Admin login throttle check failed: ' . $e->getMessage());
        return false;
    }
}

function record_admin_login_failure(PDO $db, string $username): void
{
    try {
        $hash = admin_login_username_hash($username);
        $ip = admin_login_ip_address();
        $stmt = $db->prepare('
            SELECT id, attempt_count, locked_until
            FROM admin_login_attempts
            WHERE username_hash = ? AND ip_address = ?
            LIMIT 1
        ');
        $stmt->execute([$hash, $ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $attemptCount = 1;
        if ($row && (empty($row['locked_until']) || strtotime((string)$row['locked_until']) <= time())) {
            $attemptCount = min(3, (int)$row['attempt_count'] + 1);
        }
        $lockedUntil = $attemptCount >= 3 ? date('Y-m-d H:i:s', time() + 900) : null;

        if ($row) {
            $update = $db->prepare('
                UPDATE admin_login_attempts
                SET attempt_count = ?, locked_until = ?, last_attempt_at = current_timestamp()
                WHERE id = ?
            ');
            $update->execute([$attemptCount, $lockedUntil, (int)$row['id']]);
            return;
        }

        $insert = $db->prepare('
            INSERT INTO admin_login_attempts (username_hash, ip_address, attempt_count, locked_until)
            VALUES (?, ?, ?, ?)
        ');
        $insert->execute([$hash, $ip, $attemptCount, $lockedUntil]);
    } catch (Throwable $e) {
        error_log('Admin login throttle update failed: ' . $e->getMessage());
    }
}

function clear_admin_login_failures(PDO $db, string $username): void
{
    try {
        $stmt = $db->prepare('DELETE FROM admin_login_attempts WHERE username_hash = ? AND ip_address = ?');
        $stmt->execute([admin_login_username_hash($username), admin_login_ip_address()]);
    } catch (Throwable $e) {
        error_log('Admin login throttle clear failed: ' . $e->getMessage());
    }
}
