<?php
/**
 * Home Page
 * Displays hero banner, featured vehicles, and legacy bento grid.
 */
$current_page = 'index';
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/db.php';
require_once __DIR__ . '/include/analytics.php';

logActivity('view');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?> — Kenya's Premier Car Destination</title>
    <meta name="description" content="Kenya's premier car dealership and automotive enthusiast hub.">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<?php include __DIR__ . '/include/nav.php'; ?>

<!-- ── Hero Banner ─────────────────────────────────────── -->
<div class="site-hero">
    <h1>OWN IT.<br><span>DRIVE IT.</span></h1>
    <p>Kenya's premier destination for quality locally available vehicles and legendary automotive heritage.</p>
    <div class="hero-rule"></div>
</div>

<div class="page-content">

    <!-- ── Featured Vehicles ──────────────────────────────── -->
    <h2 class="section-title">
        <span>Featured</span> Vehicles
        <div class="title-line"></div>
    </h2>

    <div class="hero-cards">
        <?php
        $stmt = $db->prepare("SELECT v.*, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id WHERE v.is_featured = 1 LIMIT 3");
        $stmt->execute();
        $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($featured as $car):
            $img = $car['image_url'];
            $encoded_img = implode('/', array_map('rawurlencode', explode('/', $img)));
            $alt_img = '';
            if (str_ends_with($img, '.webp')) {
                $alt_img = str_replace('.webp', '.png', $img);
                if (!file_exists(__DIR__ . '/' . $alt_img)) {
                    $alt_img = str_replace('.webp', '.jpg', $img);
                }
            }
            $brand_slug = strtolower(str_replace(' ', '-', $car['brand_name']));
        ?>
        <div class="hero-card">
            <img src="/<?php echo htmlspecialchars($encoded_img); ?>" alt="<?php echo htmlspecialchars($car['model']); ?>" loading="lazy"
                 <?php if ($alt_img): ?>onerror="this.src='/<?php echo rawurlencode($alt_img); ?>'; this.onerror=null;"<?php endif; ?>>
            <div class="hero-card-overlay">
                <div class="hero-card-tag"><?php echo htmlspecialchars($car['brand_name']); ?> · <?php echo htmlspecialchars($car['year']); ?></div>
                <h3><?php echo htmlspecialchars($car['model']); ?></h3>
                <a class="hero-card-link" href="/inventory#<?php echo $brand_slug; ?>">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Legacy Showcase ──────────────────────────────── -->
    <h2 class="section-title">
        <span>Legacy</span> Showcase
        <div class="title-line"></div>
    </h2>

    <div class="legacy-bento">
        <a class="bento-tile wide" href="/legacy/GT-R">
            <img src="/assets/images/gtr/r35-nissan-gt-r-1709580348.jpg" alt="Nissan GT-R" loading="lazy"
                 onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
            <div class="bento-body">
                <span class="bento-title">Nissan GT-R</span>
                <small>1969 — Present</small>
            </div>
        </a>
        <a class="bento-tile" href="/legacy/Lancer%20Evo">
            <img src="/assets/images/lancer/evo%2010.jpg" alt="Lancer Evo" loading="lazy"
                 onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
            <div class="bento-body">
                <span class="bento-title">Lancer Evo</span>
                <small>1992 — 2016</small>
            </div>
        </a>
        <a class="bento-tile" href="/legacy/Supra">
            <img src="/assets/images/supra/2020-supra-7.jpg" alt="Toyota Supra" loading="lazy"
                 onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
            <div class="bento-body">
                <span class="bento-title">Toyota Supra</span>
                <small>1978 — Present</small>
            </div>
        </a>
        <a class="bento-tile tall" href="/legacy/Jesko">
            <img src="/assets/images/koenigsegg/Jesko.jpg" alt="Koenigsegg Jesko" loading="lazy"
                 onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
            <div class="bento-body">
                <span class="bento-title">Koenigsegg Jesko</span>
                <small>1994 — Present</small>
            </div>
        </a>
        <a class="bento-tile" href="/legacy/Charger">
            <img src="/assets/images/charger/1969_Dodge_Charger_green_I.jpg" alt="Dodge Charger" loading="lazy"
                 onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
            <div class="bento-body">
                <span class="bento-title">Dodge Charger</span>
                <small>1966 — Present</small>
            </div>
        </a>
        <a class="bento-tile" href="/legacy/RX-7">
            <img src="/assets/images/mazda/mazda-rx-7-fd.jpg" alt="Mazda RX-7" loading="lazy"
                 onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
            <div class="bento-body">
                <span class="bento-title">Mazda RX-7</span>
                <small>1978 — 2002</small>
            </div>
        </a>
        <a class="bento-tile" href="/legacy/NSX">
            <img src="/assets/images/nsx/HONDA_NSX-2016_main.jpg" alt="Honda NSX" loading="lazy"
                 onerror="this.src='/assets/images/products/landcruiser.png'; this.onerror=null;">
            <div class="bento-body">
                <span class="bento-title">Honda NSX</span>
                <small>1990 — 2022</small>
            </div>
        </a>
    </div>

</div>

<?php include __DIR__ . '/include/footer.php'; ?>
</body>
</html>
