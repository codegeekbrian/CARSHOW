<?php
/**
 * About Us Page
 * Describes the company and displays key statistics.
 */
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/csrf.php';
$current_page = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/../include/nav.php'; ?>

    <div class="site-hero">
        <h1>ABOUT <span>US</span></h1>
        <p>Kenya's trusted home for quality locally available vehicles and automotive passion.</p>
        <div class="hero-rule"></div>
    </div>

    <div class="page-content">
        <div class="about-box">
            <h2><?php echo htmlspecialchars(SITE_NAME); ?></h2>
            <p><?php echo htmlspecialchars(SITE_NAME); ?> is a car dealership incorporated in Kenya, specialising in the sale of new and second-hand motor vehicles. We pride ourselves on offering quality, carefully sourced vehicles guided by our core values and a burning desire to satisfy customer needs.</p>
            <p>Vehicles in our dealership are sourced locally and from reputable international markets — ensuring variety to meet every taste and budget in the Kenyan market.</p>
            <p>Beyond our inventory, we are passionate about car culture. Our <strong>Legacy Showcase</strong> celebrates legendary nameplates — from the Nissan GT-R and Mitsubishi Lancer Evolution to the Koenigsegg, Toyota Supra, Dodge Charger, Mazda RX-7, and Honda NSX — sharing their rich histories with fellow enthusiasts.</p>
            <p>Whether you are a first-time buyer or a seasoned collector, our team is here to make the process easy, transparent, and safe. <strong>Own it. Drive it. Love it.</strong></p>
        </div>

        <div class="stat-strip">
            <div class="stat-item">
                <div class="stat-num">7+</div>
                <div class="stat-label">Heritage Models Showcased</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">10+</div>
                <div class="stat-label">Source Countries</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">5</div>
                <div class="stat-label">Brands in Inventory</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">24/7</div>
                <div class="stat-label">Online Showcase</div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../include/footer.php'; ?>
</body>
</html>