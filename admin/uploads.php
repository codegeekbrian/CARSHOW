<?php
require_once __DIR__ . '/admin_db.php';
require_permission('inventory.upload');
require_csrf();

$media = $db->query('SELECT * FROM uploaded_media ORDER BY created_at DESC LIMIT 60')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Uploads moved';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content admin-bento-layout admin-upload-legacy">
        <article class="settings-card admin-panel-compact">
            <p class="admin-kicker">Inventory workflow</p>
            <h2 class="section-title">Uploads moved</h2>
            <p class="text-muted">Vehicle images are now uploaded from the Add/Edit Vehicle form, keeping each file tied to the vehicle record it belongs to.</p>
            <a href="cars.php" class="btn btn-success">Open vehicle inventory</a>
        </article>

        <article class="settings-card admin-panel-wide">
            <h2 class="section-title">Recent media</h2>
            <div class="media-library-grid">
                <?php foreach ($media as $item): ?>
                    <figure class="media-tile">
                        <img src="../<?php echo h($item['file_path']); ?>" alt="<?php echo h($item['original_name']); ?>" loading="lazy">
                        <figcaption>
                            <span><?php echo h($item['original_name']); ?></span>
                            <code><?php echo h($item['file_path']); ?></code>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
