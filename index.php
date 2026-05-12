<?php 
$current_page = 'index'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Own It &amp; Drive It — Kenya's Premier Car Destination</title>
  <meta name="description" content="Kenya's premier car dealership and automotive enthusiast hub.">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<?php include "include/nav.php"; ?>

<div class="site-hero">
  <h1>OWN IT.<br><span>DRIVE IT.</span></h1>
  <p>Kenya's premier destination for quality imported vehicles and legendary automotive heritage — sourced from Japan, UK, Australia &amp; beyond.</p>
  <div class="hero-rule"></div>
</div>
</div>

<div class="page-content">

  <h2 class="section-title">
    <span>Featured</span> Vehicles
    <div class="title-line"></div>
  </h2>

  <div class="hero-cards">
    <div class="hero-card">
      <img src="assets/images/products/landcruiser.png" alt="Land Cruiser" loading="lazy">
      <div class="hero-card-overlay">
        <div class="hero-card-tag">Toyota Motors · 2025</div>
        <h3>Land Cruiser</h3>
        <a class="hero-card-link" href="pages/inventory.php#toyota">View Details</a>
      </div>
    </div>
    <div class="hero-card">
      <img src="assets/images/products/carrera gts.webp" alt="Porsche Carrera GTS" loading="lazy">
      <div class="hero-card-overlay">
        <div class="hero-card-tag">Porsche Motors · 2025</div>
        <h3>911 Carrera GTS</h3>
        <a class="hero-card-link" href="pages/inventory.php#porsche">View Details</a>
      </div>
    </div>
    <div class="hero-card">
      <img src="assets/images/products/ranger.webp" alt="Ford Ranger" loading="lazy">
      <div class="hero-card-overlay">
        <div class="hero-card-tag">Ford Motors · 2025</div>
        <h3>Ranger</h3>
        <a class="hero-card-link" href="pages/inventory.php#ford">View Details</a>
      </div>
    </div>
  </div>

  <h2 class="section-title">
    <span>Heritage</span> Showcase
    <div class="title-line"></div>
  </h2>

  <div class="heritage-bento">
    <a class="bento-tile wide" href="legacy/GT-R">
      <img src="assets/images/gtr/r35-nissan-gt-r-1709580348.jpg" alt="Nissan GT-R" loading="lazy">
      <div class="bento-body">
        <span class="bento-title">Nissan GT-R</span>
        <small>1969 — Present</small>
      </div>
    </a>
    <a class="bento-tile" href="legacy/Lancer%20Evo">
      <img src="assets/images/lancer/evo 10.jpg" alt="Lancer Evo" loading="lazy">
      <div class="bento-body">
        <span class="bento-title">Lancer Evo</span>
        <small>1992 — 2016</small>
      </div>
    </a>
    <a class="bento-tile" href="legacy/Supra">
      <img src="assets/images/supra/2020-supra-7.jpg" alt="Toyota Supra" loading="lazy">
      <div class="bento-body">
        <span class="bento-title">Toyota Supra</span>
        <small>1978 — Present</small>
      </div>
    </a>
    <a class="bento-tile tall" href="legacy/Koenigsegg">
      <img src="assets/images/koenigsegg/Jesko.jpg" alt="Koenigsegg" loading="lazy">
      <div class="bento-body">
        <span class="bento-title">Koenigsegg</span>
        <small>1994 — Present</small>
      </div>
    </a>
    <a class="bento-tile" href="legacy/Charger">
      <img src="assets/images/charger/1969_Dodge_Charger_green_I.jpg" alt="Dodge Charger" loading="lazy">
      <div class="bento-body">
        <span class="bento-title">Dodge Charger</span>
        <small>1966 — Present</small>
      </div>
    </a>
    <a class="bento-tile" href="legacy/RX-7">
      <img src="assets/images/mazda/mazda-rx-7-fd.jpg" alt="Mazda RX-7" loading="lazy">
      <div class="bento-body">
        <span class="bento-title">Mazda RX-7</span>
        <small>1978 — 2002</small>
      </div>
    </a>
    <a class="bento-tile" href="legacy/NSX">
      <img src="assets/images/nsx/HONDA_NSX-2016_main.jpg" alt="Honda NSX" loading="lazy">
      <div class="bento-body">
        <span class="bento-title">Honda NSX</span>
        <small>1990 — 2022</small>
      </div>
    </a>
  </div>

</div><!-- /page-content -->

<footer class="site-footer">
  <div class="footer-inner">
    <p class="footer-title">What Our <span>Clients</span> Say</p>
    <div class="testimonials">
      <div class="testimonial">
        <p>"This website has been so useful in searching for a car. As a first-time buyer the process was overwhelming, but having everything in one place made it so much easier."</p>
        <cite>— Bianca, Dallas TX</cite>
      </div>
      <div class="testimonial">
        <p>"It's rare that I don't find what I'm looking for. Own It &amp; Drive It will be my first and last stop the next time I'm in the market for a vehicle."</p>
        <cite>— Greg, Bay Village OH</cite>
      </div>
    </div>
    <p class="footer-copy">&copy; <?= date('Y') ?> &nbsp;·&nbsp; OWN IT &amp; DRIVE IT STORE &nbsp;·&nbsp; NAIROBI, KENYA</p>
  </div>
</footer>

<button id="back-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top">↑</button>
<script src="assets/js/main.js"></script>
</body>
</html>
