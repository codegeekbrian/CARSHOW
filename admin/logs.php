<?php
require_once __DIR__ . '/admin_db.php';
require_permission('logs.view');

$auditUser = (int)($_GET['admin_user_id'] ?? 0);
$auditAction = trim($_GET['action'] ?? '');
$auditEntity = trim($_GET['entity_type'] ?? '');
$auditFrom = trim($_GET['from'] ?? '');
$auditTo = trim($_GET['to'] ?? '');
$auditWhere = [];
$auditParams = [];

if ($auditUser > 0) {
    $auditWhere[] = 'l.admin_user_id = ?';
    $auditParams[] = $auditUser;
}

if ($auditAction !== '') {
    $auditWhere[] = 'l.action = ?';
    $auditParams[] = $auditAction;
}

if ($auditEntity !== '') {
    $auditWhere[] = 'l.entity_type = ?';
    $auditParams[] = $auditEntity;
}

if ($auditFrom !== '' && strtotime($auditFrom)) {
    $auditWhere[] = 'l.created_at >= ?';
    $auditParams[] = date('Y-m-d 00:00:00', strtotime($auditFrom));
}

if ($auditTo !== '' && strtotime($auditTo)) {
    $auditWhere[] = 'l.created_at <= ?';
    $auditParams[] = date('Y-m-d 23:59:59', strtotime($auditTo));
}

$auditWhereSql = $auditWhere ? 'WHERE ' . implode(' AND ', $auditWhere) : '';

$activity = $db->query("
    SELECT *
    FROM analytics
    ORDER BY created_at DESC
    LIMIT 120
")->fetchAll(PDO::FETCH_ASSOC);

$auditStmt = $db->prepare("
    SELECT l.*, u.username, u.display_name
    FROM admin_audit_logs l
    LEFT JOIN admin_users u ON u.id = l.admin_user_id
    {$auditWhereSql}
    ORDER BY l.created_at DESC
    LIMIT 120
");
$auditStmt->execute($auditParams);
$audit = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

$adminUsers = $db->query("
    SELECT id, COALESCE(display_name, username) AS label
    FROM admin_users
    ORDER BY label
")->fetchAll(PDO::FETCH_ASSOC);
$auditActions = $db->query("SELECT DISTINCT action FROM admin_audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$auditEntities = $db->query("SELECT DISTINCT entity_type FROM admin_audit_logs WHERE entity_type IS NOT NULL AND entity_type <> '' ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Logs';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content">
        <div class="admin-page-heading">
            <div>
                <p class="admin-kicker">System activity</p>
                <h2 class="section-title">Logs</h2>
            </div>
        </div>

        <div class="dashboard-grid control-grid">
            <article class="admin-panel">
                <div class="panel-head">
                    <h3>Admin audit trail</h3>
                    <span class="text-muted"><?php echo count($audit); ?> latest</span>
                </div>
                <form method="GET" class="admin-log-filter" aria-label="Filter admin audit logs">
                    <div class="admin-search-field">
                        <label for="audit-user">User</label>
                        <select id="audit-user" name="admin_user_id" class="compact-select">
                            <option value="">All users</option>
                            <?php foreach ($adminUsers as $user): ?>
                                <option value="<?php echo (int)$user['id']; ?>" <?php echo $auditUser === (int)$user['id'] ? 'selected' : ''; ?>><?php echo h($user['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-search-field">
                        <label for="audit-action">Action</label>
                        <select id="audit-action" name="action" class="compact-select">
                            <option value="">All actions</option>
                            <?php foreach ($auditActions as $action): ?>
                                <option value="<?php echo h($action); ?>" <?php echo $auditAction === $action ? 'selected' : ''; ?>><?php echo h($action); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-search-field">
                        <label for="audit-entity">Entity</label>
                        <select id="audit-entity" name="entity_type" class="compact-select">
                            <option value="">All entities</option>
                            <?php foreach ($auditEntities as $entity): ?>
                                <option value="<?php echo h($entity); ?>" <?php echo $auditEntity === $entity ? 'selected' : ''; ?>><?php echo h($entity); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-search-field">
                        <label for="audit-from">From</label>
                        <input id="audit-from" name="from" type="date" value="<?php echo h($auditFrom); ?>">
                    </div>
                    <div class="admin-search-field">
                        <label for="audit-to">To</label>
                        <input id="audit-to" name="to" type="date" value="<?php echo h($auditTo); ?>">
                    </div>
                    <button type="submit" class="btn btn-sm">Filter</button>
                    <a href="logs.php" class="btn btn-sm btn-secondary">Reset</a>
                </form>
                <div class="admin-scroll-panel log-list">
                    <?php foreach ($audit as $row): ?>
                        <div class="admin-mini-row">
                            <span>
                                <strong><?php echo h($row['action']); ?></strong>
                                <small><?php echo h($row['summary'] ?: $row['entity_type']); ?></small>
                            </span>
                            <span class="mini-date"><?php echo h(($row['display_name'] ?: $row['username'] ?: 'System') . ' / ' . $row['created_at']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($audit)): ?><p class="text-muted">No admin audit entries yet.</p><?php endif; ?>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-head">
                    <h3>Public activity</h3>
                    <span class="text-muted"><?php echo count($activity); ?> latest</span>
                </div>
                <div class="admin-scroll-panel log-list">
                    <?php foreach ($activity as $row): ?>
                        <div class="admin-mini-row">
                            <span>
                                <strong><?php echo h($row['type']); ?></strong>
                                <small><?php echo h($row['page_url']); ?></small>
                            </span>
                            <span class="mini-date"><?php echo h($row['created_at']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($activity)): ?><p class="text-muted">No public activity entries yet.</p><?php endif; ?>
                </div>
            </article>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
