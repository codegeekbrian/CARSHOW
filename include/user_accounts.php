<?php
/**
 * Public user account moderation helpers.
 */

function publicUserHasColumn(PDO $db, string $column): bool
{
    $stmt = $db->prepare('
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "users" AND COLUMN_NAME = ?
    ');
    $stmt->execute([$column]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensurePublicUserAccountColumns(PDO $db): void
{
    if (!publicUserHasColumn($db, 'is_blocked')) {
        $db->exec('ALTER TABLE users ADD COLUMN is_blocked tinyint(1) NOT NULL DEFAULT 0 AFTER password');
    }

    if (!publicUserHasColumn($db, 'block_reason')) {
        $db->exec('ALTER TABLE users ADD COLUMN block_reason varchar(255) DEFAULT NULL AFTER is_blocked');
    }

    if (!publicUserHasColumn($db, 'archived_at')) {
        $db->exec('ALTER TABLE users ADD COLUMN archived_at timestamp NULL DEFAULT NULL AFTER block_reason');
    }
}

function fetchPublicUserForLogin(PDO $db, string $email): ?array
{
    $hasModeration = publicUserHasColumn($db, 'is_blocked') && publicUserHasColumn($db, 'archived_at');
    $columns = $hasModeration
        ? 'id, name, email, password, is_blocked, block_reason, archived_at'
        : 'id, name, email, password';

    $stmt = $db->prepare("SELECT {$columns} FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    $user['is_blocked'] = (int)($user['is_blocked'] ?? 0);
    $user['archived_at'] = $user['archived_at'] ?? null;
    return $user;
}

function fetchPublicUserById(PDO $db, int $userId): ?array
{
    $hasModeration = publicUserHasColumn($db, 'is_blocked') && publicUserHasColumn($db, 'archived_at');
    $columns = $hasModeration
        ? 'id, name, email, is_blocked, block_reason, archived_at'
        : 'id, name, email';

    $stmt = $db->prepare("SELECT {$columns} FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return null;
    }

    $user['is_blocked'] = (int)($user['is_blocked'] ?? 0);
    $user['archived_at'] = $user['archived_at'] ?? null;
    return $user;
}

function publicUserCanAccess(?array $user): bool
{
    if (!$user) {
        return false;
    }

    return (int)($user['is_blocked'] ?? 0) !== 1 && empty($user['archived_at']);
}

function setPublicUserBlocked(PDO $db, int $userId, bool $blocked, ?string $reason = null): void
{
    ensurePublicUserAccountColumns($db);
    $stmt = $db->prepare('UPDATE users SET is_blocked = ?, block_reason = ? WHERE id = ?');
    $stmt->execute([$blocked ? 1 : 0, $blocked ? trim((string)$reason) ?: null : null, $userId]);
}

function archivePublicUser(PDO $db, int $userId): void
{
    ensurePublicUserAccountColumns($db);
    $stmt = $db->prepare('UPDATE users SET archived_at = COALESCE(archived_at, NOW()) WHERE id = ?');
    $stmt->execute([$userId]);
}

function restorePublicUser(PDO $db, int $userId): void
{
    ensurePublicUserAccountColumns($db);
    $stmt = $db->prepare('UPDATE users SET archived_at = NULL WHERE id = ?');
    $stmt->execute([$userId]);
}

function publicUserStatusLabel(array $user): string
{
    if (!empty($user['archived_at'])) {
        return 'Archived';
    }

    if ((int)($user['is_blocked'] ?? 0) === 1) {
        return 'Blocked';
    }

    return 'Active';
}
