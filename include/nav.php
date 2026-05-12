<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$base_path = (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? '../' : '';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
?>
<nav class="navbar">
  <a class="navbar-brand" href="/index.php">OWN IT &amp; DRIVE IT</a>
  <div class="nav-toggle" id="navToggle" onclick="toggleNav()">
    <span></span><span></span><span></span>
  </div>
  <div class="navbar-links" id="navLinks">
    <a href="/index.php" class="<?= $current_page==='index' ?'active' : '' ?>">Home</a>
    <a href="/pages/inventory.php" class="<?= $current_page==='inventory' ?'active' : '' ?>">Inventory</a>

    <div class="nav-dropdown">
      <a href="#" class="dropdown-trigger" id="heritageTrigger">Legacy <span class="caret">▾</span></a>
      <div class="dropdown-menu" id="heritageMenu">
        <a href="/pages/heritage_template.php?model=GT-R">Nissan GT-R</a>
        <a href="/pages/heritage_template.php?model=Lancer%20Evo">Lancer Evo</a>
        <a href="/pages/heritage_template.php?model=Jesko">Koenigsegg</a>
        <a href="/pages/heritage_template.php?model=Supra">Toyota Supra</a>
        <a href="/pages/heritage_template.php?model=Charger">Dodge Charger</a>
        <a href="/pages/heritage_template.php?model=RX-7">Mazda RX-7</a>
        <a href="/pages/heritage_template.php?model=NSX">Honda NSX</a>
      </div>
    </div>

    <a href="/pages/about.php" class="<?= $current_page==='about' ?'active' : '' ?>">About</a>
    <a href="/pages/contact.php" class="<?= $current_page==='contact' ?'active' : '' ?>">Contact</a>
    
    <?php if ($is_logged_in): ?>
      <div class="nav-dropdown">
        <a href="#" class="dropdown-trigger"><?= htmlspecialchars($user_name) ?> <span class="caret">▾</span></a>
        <div class="dropdown-menu">
          <a href="/logout.php">Logout</a>
        </div>
      </div>
    <?php else: ?>
      <a href="/login.php">Login</a>
      <a href="/register.php">Register</a>
    <?php endif; ?>
    
    <button class="theme-toggle-btn" id="theme-toggle" aria-label="Toggle theme">
      <span class="theme-icon">&#9789;</span><span class="theme-label"></span>
    </button>
  </div>
</nav>
