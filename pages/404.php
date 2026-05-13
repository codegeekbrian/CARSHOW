<?php http_response_code(404);
require_once __DIR__ . '/../include/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/../include/nav.php'; ?>

    <div class="site-hero">
        <h1>404</h1>
        <p>The road ends here. The page you're looking for doesn't exist or has been moved.</p>
        <div class="hero-rule"></div>
    </div>

    <div class="page-content" style="text-align:center">
        <p style="color:var(--muted);margin-bottom:2rem;">
            Perhaps you'd like to explore what we do have?
        </p>
        <div class="error-page-links">
            <a href="/index.php" class="btn btn-success">Home</a>
            <a href="/inventory" class="btn-outline">Inventory</a>
        </div>
    </div>

    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>
