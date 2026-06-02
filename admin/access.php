<?php
require_once __DIR__ . '/admin_db.php';
require_permission('users.manage');
require_csrf();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_user') {
        $id = (int)($_POST['id'] ?? 0);
        $roleId = (int)($_POST['role_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $positionTitle = trim($_POST['position_title'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $password = trim($_POST['password'] ?? '');

        if ($roleId < 1 || $username === '') {
            $error = 'Choose a role and username.';
        } elseif ($id > 0) {
            if ($password !== '') {
                $stmt = $db->prepare('
                    UPDATE admin_users
                    SET role_id = ?, username = ?, email = ?, display_name = ?, position_title = ?, is_active = ?, password_hash = ?
                    WHERE id = ?
                ');
                $stmt->execute([$roleId, $username, $email ?: null, $displayName ?: null, $positionTitle ?: null, $isActive, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $db->prepare('
                    UPDATE admin_users
                    SET role_id = ?, username = ?, email = ?, display_name = ?, position_title = ?, is_active = ?
                    WHERE id = ?
                ');
                $stmt->execute([$roleId, $username, $email ?: null, $displayName ?: null, $positionTitle ?: null, $isActive, $id]);
            }
            recordAdminAuditLog($db, 'staff.updated', 'admin_user', $id, "Updated staff access for {$username}");
            $message = 'Staff access updated.';
        } elseif ($password === '') {
            $error = 'Set an initial password.';
        } else {
            $stmt = $db->prepare('
                INSERT INTO admin_users (role_id, username, email, password_hash, display_name, position_title, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$roleId, $username, $email ?: null, password_hash($password, PASSWORD_DEFAULT), $displayName ?: null, $positionTitle ?: null, $isActive]);
            recordAdminAuditLog($db, 'staff.created', 'admin_user', (int)$db->lastInsertId(), "Created staff access for {$username}");
            $message = 'Staff access created.';
        }
    }
}

$roles = $db->query('SELECT * FROM admin_roles WHERE is_active = 1 ORDER BY level DESC, name')->fetchAll(PDO::FETCH_ASSOC);
$users = $db->query('
    SELECT u.*, r.name AS role_name
    FROM admin_users u
    JOIN admin_roles r ON r.id = u.role_id
    ORDER BY r.level DESC, u.username
')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Team & Roles';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content">
        <div class="admin-page-heading">
            <div>
                <p class="admin-kicker">Position-based access</p>
                <h2 class="section-title">Team & Roles</h2>
                <p class="text-muted">Create staff users, assign a role, and control what each person can reach in the dashboard.</p>
            </div>
        </div>
        <?php if ($message): ?><div class="message success"><?php echo h($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message error"><?php echo h($error); ?></div><?php endif; ?>

        <div class="access-grid">
            <?php foreach ($users as $user): ?>
                <?php $accessId = (int)$user['id']; ?>
                <form method="POST" class="settings-card access-card">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_user">
                    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                    <div class="access-card-head">
                        <strong><?php echo h($user['display_name'] ?: $user['username']); ?></strong>
                        <span><?php echo h($user['role_name']); ?></span>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="admin-username-<?php echo $accessId; ?>">Username</label><input id="admin-username-<?php echo $accessId; ?>" name="username" value="<?php echo h($user['username']); ?>" required autocomplete="username"></div>
                        <div class="form-group"><label for="admin-email-<?php echo $accessId; ?>">Email</label><input id="admin-email-<?php echo $accessId; ?>" name="email" type="email" value="<?php echo h($user['email']); ?>" autocomplete="email"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="admin-display-<?php echo $accessId; ?>">Display name</label><input id="admin-display-<?php echo $accessId; ?>" name="display_name" value="<?php echo h($user['display_name']); ?>"></div>
                        <div class="form-group"><label for="admin-position-<?php echo $accessId; ?>">Position</label><input id="admin-position-<?php echo $accessId; ?>" name="position_title" value="<?php echo h($user['position_title']); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin-role-<?php echo $accessId; ?>">Role</label>
                            <select id="admin-role-<?php echo $accessId; ?>" name="role_id">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo (int)$role['id']; ?>" <?php echo (int)$user['role_id'] === (int)$role['id'] ? 'selected' : ''; ?>><?php echo h($role['name']); ?> - level <?php echo (int)$role['level']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label for="admin-password-<?php echo $accessId; ?>">New password</label><input id="admin-password-<?php echo $accessId; ?>" name="password" type="password" autocomplete="new-password"></div>
                    </div>
                    <label class="admin-checkline"><input type="checkbox" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>> Active</label>
                    <button type="submit" class="btn btn-sm">Save access</button>
                </form>
            <?php endforeach; ?>

            <form method="POST" class="settings-card access-card access-card-new">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="save_user">
                <h3>Add staff member</h3>
                <div class="form-group"><label for="new-admin-username">Username</label><input id="new-admin-username" name="username" required autocomplete="username"></div>
                <div class="form-group"><label for="new-admin-email">Email</label><input id="new-admin-email" name="email" type="email" autocomplete="email"></div>
                <div class="form-group"><label for="new-admin-display">Display name</label><input id="new-admin-display" name="display_name"></div>
                <div class="form-group"><label for="new-admin-position">Position</label><input id="new-admin-position" name="position_title"></div>
                <div class="form-group">
                    <label for="new-admin-role">Role</label>
                    <select id="new-admin-role" name="role_id">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo (int)$role['id']; ?>"><?php echo h($role['name']); ?> - level <?php echo (int)$role['level']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="new-admin-password">Password</label><input id="new-admin-password" name="password" type="password" required autocomplete="new-password"></div>
                <label class="admin-checkline"><input type="checkbox" name="is_active" checked> Active</label>
                <button type="submit" class="btn btn-success">Create access</button>
            </form>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
