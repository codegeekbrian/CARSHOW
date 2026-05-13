<?php
/**
 * Navigation Component
 * Renders the sticky navbar with dropdown menus.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
?>
<nav class="navbar">
  <a class="navbar-brand" href="/index.php">OWN IT &amp; DRIVE IT</a>
  <div class="nav-toggle" id="navToggle" onclick="toggleNav()">
    <span></span><span></span><span></span>
  </div>
  <div class="navbar-links" id="navLinks">
    <a href="/index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">Home</a>
    <a href="/inventory" class="<?php echo $current_page === 'inventory' ? 'active' : ''; ?>">Inventory</a>

    <div class="nav-dropdown">
      <a href="#" class="dropdown-trigger" id="legacyTrigger">Legacy <span class="caret">▾</span></a>
      <div class="dropdown-menu" id="legacyMenu">
        <a href="/legacy/GT-R">Nissan GT-R</a>
        <a href="/legacy/Lancer%20Evo">Lancer Evo</a>
        <a href="/legacy/Jesko">Koenigsegg Jesko</a>
        <a href="/legacy/Supra">Toyota Supra</a>
        <a href="/legacy/Charger">Dodge Charger</a>
        <a href="/legacy/RX-7">Mazda RX-7</a>
        <a href="/legacy/NSX">Honda NSX</a>
      </div>
    </div>

    <a href="/about" class="<?php echo $current_page === 'about' ? 'active' : ''; ?>">About</a>
    <a href="/contact" class="<?php echo $current_page === 'contact' ? 'active' : ''; ?>">Contact</a>

    <?php if ($is_logged_in): ?>
      <div class="nav-dropdown">
        <a href="#" class="dropdown-trigger"><?php echo htmlspecialchars($user_name); ?> <span class="caret">▾</span></a>
        <div class="dropdown-menu">
          <a href="/logout">Logout</a>
        </div>
      </div>
    <?php else: ?>
      <a href="/login">Login</a>
      <a href="/register">Register</a>
    <?php endif; ?>

    <button class="theme-toggle-btn" id="theme-toggle" aria-label="Toggle theme">
      <span class="theme-icon">&#9789;</span><span class="theme-label"></span>
    </button>
  </div>
</nav>
