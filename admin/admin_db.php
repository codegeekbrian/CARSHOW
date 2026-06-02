<?php
require_once dirname(__DIR__) . '/include/db.php';
require_once dirname(__DIR__) . '/include/cache.php';
require_once dirname(__DIR__) . '/include/session_helpers.php';
require_once dirname(__DIR__) . '/include/csrf.php';
require_once __DIR__ . '/admin_auth_throttle.php';

function getAdminRoleBySlug(PDO $db, string $slug): ?array
{
    $stmt = $db->prepare('SELECT * FROM admin_roles WHERE slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    return $role ?: null;
}

function getRolePermissions(PDO $db, int $roleId): array
{
    $stmt = $db->prepare('
        SELECT p.permission_key
        FROM admin_role_permissions rp
        JOIN admin_permissions p ON p.id = rp.permission_id
        WHERE rp.role_id = ?
        ORDER BY p.permission_key
    ');
    $stmt->execute([$roleId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function loadAdminUserForSession(PDO $db): ?array
{
    if (!empty($_SESSION['admin_user_id'])) {
        $stmt = $db->prepare('
            SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.level AS role_level
            FROM admin_users u
            JOIN admin_roles r ON r.id = u.role_id
            WHERE u.id = ? AND u.is_active = 1 AND r.is_active = 1
            LIMIT 1
        ');
        $stmt->execute([(int)$_SESSION['admin_user_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            return $admin;
        }
    }

    if (!empty($_SESSION['admin_username'])) {
        $stmt = $db->prepare('
            SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.level AS role_level
            FROM admin_users u
            JOIN admin_roles r ON r.id = u.role_id
            WHERE u.username = ? AND u.is_active = 1 AND r.is_active = 1
            LIMIT 1
        ');
        $stmt->execute([$_SESSION['admin_username']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            return $admin;
        }
    }

    return null;
}

function applyAdminSession(PDO $db, array $admin): void
{
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = (int)$admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_display_name'] = $admin['display_name'] ?: $admin['username'];
    $_SESSION['admin_position'] = $admin['position_title'] ?: $admin['role_name'];
    $_SESSION['admin_role_slug'] = $admin['role_slug'];
    $_SESSION['admin_permissions'] = getRolePermissions($db, (int)$admin['role_id']);
    $_SESSION['admin_last_activity'] = time();
}

function hydrate_admin_session_from_db(PDO $db): bool
{
    if (!is_authenticated()) {
        return false;
    }

    if (!empty($_SESSION['admin_role_slug']) && isset($_SESSION['admin_permissions']) && is_array($_SESSION['admin_permissions'])) {
        return true;
    }

    try {
        $admin = loadAdminUserForSession($db);
        if (!$admin) {
            return false;
        }
        applyAdminSession($db, $admin);
        return true;
    } catch (Throwable $e) {
        error_log('Admin session hydration failed: ' . $e->getMessage());
        return false;
    }
}

function admin_can(string $permission): bool
{
    if (!is_authenticated()) {
        return false;
    }

    if (($_SESSION['admin_role_slug'] ?? '') === 'owner') {
        return true;
    }

    if (in_array('*', $_SESSION['admin_permissions'] ?? [], true)) {
        return true;
    }

    return in_array($permission, $_SESSION['admin_permissions'] ?? [], true);
}

function require_permission(string $permission): void
{
    require_auth();
    if (!admin_can($permission)) {
        http_response_code(403);
        render_admin_forbidden();
        exit;
    }
}

function render_admin_forbidden(): void
{
    $title = 'Access restricted';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . h($title) . ' - ' . h(SITE_NAME) . '</title>';
    echo '<link rel="stylesheet" href="../assets/css/main.css"><link rel="stylesheet" href="../assets/css/admin.css"></head>';
    echo '<body class="admin-body admin-login-body"><main class="login-container">';
    echo '<section class="login-form"><p class="admin-kicker">Dashboard access</p><h2>' . h($title) . '</h2>';
    echo '<p>Your staff position does not include this dashboard area.</p>';
    echo '<div class="form-row"><a class="btn" href="index.php">Dashboard</a><a class="btn btn-secondary" href="logout.php">Log out</a></div>';
    echo '</section></main></body></html>';
}

function current_admin_user_id(): ?int
{
    return isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : null;
}

function recordAdminAuditLog(PDO $db, string $action, ?string $entityType = null, ?int $entityId = null, ?string $summary = null): void
{
    try {
        $stmt = $db->prepare('
            INSERT INTO admin_audit_logs (admin_user_id, action, entity_type, entity_id, summary, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            current_admin_user_id(),
            $action,
            $entityType,
            $entityId,
            $summary,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('Admin audit log failed: ' . $e->getMessage());
    }
}

function is_authenticated(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_auth(): void
{
    global $db;

    enforce_admin_session_timeout();
    if (!is_authenticated()) {
        header('Location: login.php');
        exit();
    }

    if (!hydrate_admin_session_from_db($db)) {
        destroy_session_fully();
        if (session_status() === PHP_SESSION_NONE) {
            start_app_session();
        }
        header('Location: login.php');
        exit();
    }
}

function authenticate(string $username, string $password): bool
{
    global $db;

    try {
        $stmt = $db->prepare('
            SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.level AS role_level
            FROM admin_users u
            JOIN admin_roles r ON r.id = u.role_id
            WHERE u.username = ? AND u.is_active = 1 AND r.is_active = 1
            LIMIT 1
        ');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            applyAdminSession($db, $admin);
            $db->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$admin['id']]);
            return true;
        }
    } catch (Throwable $e) {
        error_log('Admin DB auth fallback: ' . $e->getMessage());
    }

    $admin_user = $_ENV['ADMIN_USERNAME'] ?? 'admin';
    $admin_hash = $_ENV['ADMIN_PASSWORD_HASH'] ?? '';

    if ($admin_hash === '') {
        return false;
    }

    if ($username === $admin_user && password_verify($password, $admin_hash)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_display_name'] = $username;
        $_SESSION['admin_position'] = 'Owner';
        $_SESSION['admin_role_slug'] = 'owner';
        $_SESSION['admin_permissions'] = ['*'];
        $_SESSION['admin_last_activity'] = time();
        return true;
    }
    return false;
}

function logout(): void
{
    destroy_session_fully();
}

function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($token)) {
            http_response_code(403);
            die('Invalid CSRF token');
        }
    }
}
