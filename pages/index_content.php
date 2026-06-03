<?php
require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/../include/cache.php';
require_once __DIR__ . '/../include/analytics.php';
require_once __DIR__ . '/../include/settings.php';

logActivity('view');

$settings = loadSettings($db);

$heroHeadline = $settings['hero_headline'] ?? "OWN IT.\nDRIVE IT.";
$heroSubtitle = $settings['hero_subtitle'] ?? "Clean stock, clear pricing, and branch viewings when you are ready.";
?>

<main>
    <header class="site-hero home-hero">
        <div class="hero-copy">
            <h1><?php echo str_replace("\n", '<br>', h($heroHeadline)); ?></h1>
            <p><?php echo h($heroSubtitle); ?></p>
            <div class="hero-actions">
                <a class="btn" href="<?php echo url('inventory'); ?>">Browse Stock</a>
                <a class="btn-outline" href="<?php echo url('contact'); ?>">Inquire</a>
            </div>
        </div>
        <div class="hero-bento" aria-hidden="true">
            <div class="hero-bento-tile hero-bento-main"></div>
            <div class="hero-bento-tile hero-bento-small"></div>
            <div class="hero-bento-tile hero-bento-wide"></div>
        </div>
    </header>

    <div class="page-content">
        <section class="featured-section">
            <h2 class="section-title">
                <span>Featured</span> Vehicles
                <div class="title-line"></div>
            </h2>

            <div class="featured-carousel liquid-panel" data-carousel data-carousel-interval="5200">
                <div class="carousel-shell">
                    <div class="hero-cards" data-carousel-track>
                        <?php
                        $featured = getCache('featured_vehicles_inventory_only', 'featured');

                        if ($featured === false) {
                            $stmt = $db->prepare("
                                SELECT v.*, b.name AS brand_name
                                FROM vehicles v
                                JOIN brands b ON v.brand_id = b.id
                                WHERE v.is_featured = 1
                                  AND v.is_inventory = 1
                                  AND v.status = 'available'
                                ORDER BY v.year DESC, v.id DESC
                                LIMIT 6
                            ");
                            $stmt->execute();
                            $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            setCache('featured_vehicles_inventory_only', $featured, 'featured');
                        }

                        $fallbackImage = url('assets/images/products/toyota-land-cruiser-300.jpg');
                        foreach ($featured as $car):
                        ?>
                        <article class="hero-card">
                            <img src="<?php echo getVehicleImage($car['image_url'] ?? ''); ?>"
                                 alt="<?php echo h($car['brand_name'] . ' ' . $car['model']); ?>"
                                 loading="lazy"
                                 onerror="this.src='<?php echo $fallbackImage; ?>'; this.onerror=null;">
                            <div class="hero-card-overlay">
                                <div class="hero-card-tag"><?php echo h($car['brand_name']); ?> / <?php echo h($car['year']); ?></div>
                                <h3><?php echo h($car['model']); ?></h3>
                                <p><?php echo h(trim(($car['engine'] ?? '') . ' ' . ($car['drive'] ?? ''))); ?></p>
                                <a class="hero-card-link" href="<?php echo url('vehicle/' . (int) $car['id']); ?>">View Details</a>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="carousel-footer">
                    <div class="carousel-meter" aria-hidden="true"><span data-carousel-progress></span></div>
                    <div class="carousel-dots" data-carousel-dots aria-label="Featured vehicle slides"></div>
                    <div class="carousel-controls">
                        <button type="button" data-carousel-prev aria-label="Previous featured vehicle">&lsaquo;</button>
                        <button type="button" data-carousel-next aria-label="Next featured vehicle">&rsaquo;</button>
                    </div>
                </div>
            </div>
        </section>

    </div>
</main>
