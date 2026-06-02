<?php
require_once __DIR__ . '/admin_db.php';
require_permission('app_users.view');
require_csrf();
ensurePublicUserAccountColumns($db);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('users.manage');

    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId > 0) {
        if ($action === 'block') {
            setPublicUserBlocked($db, $userId, true, $_POST['block_reason'] ?? null);
            recordAdminAuditLog($db, 'public_user.block', 'user', $userId, 'Blocked public app user');
            $message = 'User blocked.';
        } elseif ($action === 'unblock') {
            setPublicUserBlocked($db, $userId, false);
            recordAdminAuditLog($db, 'public_user.unblock', 'user', $userId, 'Unblocked public app user');
            $message = 'User unblocked.';
        } elseif ($action === 'archive') {
            archivePublicUser($db, $userId);
            recordAdminAuditLog($db, 'public_user.archive', 'user', $userId, 'Archived public app user');
            $message = 'User archived.';
        } elseif ($action === 'restore') {
            restorePublicUser($db, $userId);
            recordAdminAuditLog($db, 'public_user.restore', 'user', $userId, 'Restored public app user');
            $message = 'User restored.';
        }
    }
}

$search = trim($_GET['search'] ?? '');
$includeArchived = ($_GET['include_archived'] ?? '') === '1';
$params = [];
$whereParts = [];

if ($search !== '') {
    $whereParts[] = '(name LIKE ? OR email LIKE ?)';
    $params = ["%{$search}%", "%{$search}%"];
}

if (!$includeArchived) {
    $whereParts[] = 'archived_at IS NULL';
}

$where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

try {
    $stmt = $db->prepare("
        SELECT u.*,
               COUNT(DISTINCT s.id) AS saved_count,
               COUNT(DISTINCT c.id) AS compared_count
        FROM users u
        LEFT JOIN saved_vehicles s ON s.user_id = u.id
        LEFT JOIN vehicle_comparisons c ON c.user_id = u.id
        {$where}
        GROUP BY u.id
        ORDER BY COALESCE(u.archived_at, u.created_at) DESC, u.created_at DESC
        LIMIT 120
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $stmt = $db->prepare("SELECT u.*, 0 AS saved_count, 0 AS compared_count FROM users u {$where} ORDER BY u.created_at DESC LIMIT 120");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'App Users';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content">
        <div class="admin-page-heading">
            <div>
                <p class="admin-kicker">Customer accounts</p>
                <h2 class="section-title">App Users</h2>
            </div>
            <form method="GET" class="admin-search-form" aria-label="Filter app users">
                <div class="admin-search-field">
                    <label for="app-user-search">Search users</label>
                    <input type="search" id="app-user-search" name="search" value="<?php echo h($search); ?>" placeholder="Search name or email" autocomplete="off">
                </div>
                <label class="admin-checkline">
                    <input type="checkbox" name="include_archived" value="1" <?php echo $includeArchived ? 'checked' : ''; ?>>
                    Archived
                </label>
                <button type="submit" class="btn btn-sm">Search</button>
            </form>
        </div>
        <?php if ($message): ?><div class="message success"><?php echo h($message); ?></div><?php endif; ?>

        <?php if (empty($users)): ?>
            <div class="empty-state">No registered users found.</div>
        <?php else: ?>
            <div class="table-responsive admin-scroll-panel">
                <table class="admin-table-stack">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Saved</th>
                            <th>Compared</th>
                            <th>Joined</th>
                            <?php if (admin_can('users.manage')): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td data-label="Name"><?php echo h($user['name']); ?></td>
                                <td data-label="Email"><?php echo h($user['email']); ?></td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?php echo h(strtolower(publicUserStatusLabel($user))); ?>">
                                        <?php echo h(publicUserStatusLabel($user)); ?>
                                    </span>
                                    <?php if (!empty($user['block_reason'])): ?>
                                        <small class="text-muted d-block"><?php echo h($user['block_reason']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Saved"><?php echo (int)$user['saved_count']; ?></td>
                                <td data-label="Compared"><?php echo (int)$user['compared_count']; ?></td>
                                <td data-label="Joined"><?php echo h($user['created_at']); ?></td>
                                <?php if (admin_can('users.manage')): ?>
                                    <td data-label="Actions">
                                        <div class="admin-inline-actions">
                                            <?php if (!empty($user['archived_at'])): ?>
                                                <form method="POST">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                    <button class="btn btn-sm" name="action" value="restore">Restore</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="admin-user-action-form">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                    <?php if ((int)($user['is_blocked'] ?? 0) === 1): ?>
                                                        <button class="btn btn-sm" name="action" value="unblock">Unblock</button>
                                                    <?php else: ?>
                                                        <input name="block_reason" aria-label="Block reason for <?php echo h($user['name']); ?>" placeholder="Reason">
                                                        <button class="btn btn-sm btn-secondary" name="action" value="block">Block</button>
                                                    <?php endif; ?>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('Archive this user? Their history stays available for business records.');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                    <button class="btn btn-sm btn-danger" name="action" value="archive">Archive</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
