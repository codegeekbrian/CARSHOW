<?php
require_once __DIR__ . '/admin_db.php';
require_permission('categories.manage');
require_csrf();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $group = trim($_POST['group_name'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if (($action === 'add' || $action === 'edit') && $name !== '' && $group !== '') {
        $slug = slugify($name);
        if ($action === 'add') {
            $stmt = $db->prepare('
                INSERT INTO vehicle_categories (name, slug, group_name, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order), is_active = VALUES(is_active)
            ');
            $stmt->execute([$name, $slug, $group, $sort, $active]);
            $message = 'Category saved.';
        } else {
            $stmt = $db->prepare('UPDATE vehicle_categories SET name = ?, slug = ?, group_name = ?, sort_order = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$name, $slug, $group, $sort, $active, (int)$_POST['id']]);
            $message = 'Category updated.';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM vehicle_category_assignments WHERE category_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM vehicle_categories WHERE id = ?')->execute([$id]);
        $message = 'Category removed.';
    }
}

$categories = $db->query('SELECT * FROM vehicle_categories ORDER BY group_name, sort_order, name')->fetchAll(PDO::FETCH_ASSOC);
$groups = ['Body type', 'Fuel', 'Transmission', 'Condition', 'Source market', 'Feature'];

$pageTitle = 'Vehicle categories';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content admin-bento-layout">
        <article class="settings-card admin-panel-compact">
            <h2 class="section-title">Add category</h2>
            <?php if ($message): ?><div class="message success"><?php echo h($message); ?></div><?php endif; ?>
            <form method="POST" class="admin-form-grid">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" required>
                </div>
                <div class="form-group">
                    <label for="group_name">Group</label>
                    <select id="group_name" name="group_name" required>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo h($group); ?>"><?php echo h($group); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sort_order">Sort</label>
                    <input id="sort_order" name="sort_order" type="number" value="0">
                </div>
                <label class="admin-checkline"><input type="checkbox" name="is_active" checked> Active</label>
                <button type="submit" class="btn btn-success">Save category</button>
            </form>
        </article>

        <article class="settings-card admin-panel-wide">
            <h2 class="section-title">Classification library</h2>
            <div class="category-chip-grid">
                <?php foreach ($categories as $category): ?>
                    <form method="POST" class="category-edit-card">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo (int)$category['id']; ?>">
                        <input name="name" value="<?php echo h($category['name']); ?>" aria-label="Category name">
                        <select name="group_name" aria-label="Category group">
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo h($group); ?>" <?php echo $category['group_name'] === $group ? 'selected' : ''; ?>><?php echo h($group); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="sort_order" type="number" value="<?php echo (int)$category['sort_order']; ?>" aria-label="Sort order">
                        <label><input type="checkbox" name="is_active" <?php echo $category['is_active'] ? 'checked' : ''; ?>> Active</label>
                        <button class="btn btn-sm" type="submit">Save</button>
                        <button class="btn btn-danger btn-sm" name="action" value="delete" onclick="return confirm('Delete this category?')">Delete</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
