<?php
// SEO Meta Tags Component
// Usage: $seo_title = '...'; $seo_desc = '...'; include 'include/seo.php';

$og_title  = $seo_title ?? 'Own It & Drive It STORE — Kenya\'s Premier Car Destination';
$og_desc   = $seo_desc ?? "Kenya's premier car dealership and automotive enthusiast hub. Browse quality imported vehicles and legendary automotive heritage.";
$og_image  = $og_image ?? 'https://codegeekbrian.github.io/CARSHOW/assets/images/sky.jpg';
$og_url    = $og_url ?? 'https://codegeekbrian.github.io/CARSHOW/' . ($seo_slug ?? 'index.php');
$og_type   = $og_type ?? 'website';
?>
  <meta name="description" content="<?= htmlspecialchars($og_desc) ?>">
  <meta name="keywords" content="car dealership kenya, buy car nairobi, imported cars, GT-R, Lancer Evo, Supra, Koenigsegg, automotive heritage">
  <meta name="author" content="Own It & Drive It STORE">

  <!-- Open Graph -->
  <meta property="og:title" content="<?= htmlspecialchars($og_title) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($og_desc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($og_url) ?>">
  <meta property="og:type" content="<?= htmlspecialchars($og_type) ?>">
  <meta property="og:site_name" content="Own It & Drive It STORE">
  <meta property="og:locale" content="en_KE">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($og_title) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($og_desc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

  <!-- Canonical URL -->
  <link rel="canonical" href="<?= htmlspecialchars($og_url) ?>">
