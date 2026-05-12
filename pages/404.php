<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Not Found — Own It &amp; Drive It</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<?php include "../include/nav.php"; ?>

<div class="site-hero">
  <h1>404</h1>
  <p>The road ends here. The page you're looking for doesn't exist or has been moved.</p>
  <div class="hero-rule"></div>
</div>

<div class="page-content" style="text-align:center">
  <p style="color:var(--muted);margin-bottom:2rem;">
    Perhaps you'd like to explore what we do have?
  </p>
  <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
    <a href="../index.php" style="
      display:inline-flex;align-items:center;gap:.5rem;
      padding:1rem 2.5rem;background:var(--gold);color:var(--carbon);
      font-family:var(--font-disp);font-size:1.3rem;letter-spacing:4px;
      border-radius:var(--r);transition:background .2s;
    ">Home</a>
    <a href="../pages/inventory.php" style="
      display:inline-flex;align-items:center;gap:.5rem;
      padding:1rem 2.5rem;background:transparent;color:var(--gold-light);
      font-family:var(--font-disp);font-size:1.3rem;letter-spacing:4px;
      border:1px solid var(--gold);border-radius:var(--r);transition:background .2s;
    ">Inventory</a>
  </div>
</div>

<?php include "../include/footer.php"; ?>
</body>
</html>
