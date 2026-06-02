<?php
/**
 * Navigation Component
 * Renders the sticky navbar, account menu, and mobile sidebar.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/session_helpers.php';
enforce_user_session_timeout();

if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    if ($current_page === 'router') {
        $current_page = '';
    }
}

enforce_user_session_timeout($db ?? null);

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
?>
<nav class="navbar" aria-label="Main Navigation">
  <div class="navbar-brand">
    <a href="<?php echo url('/'); ?>">OWN IT &amp; DRIVE IT</a>
  </div>

  <button class="nav-toggle" id="navToggle" type="button" aria-label="Open main menu" aria-controls="navLinks" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>

  <div class="navbar-links sidebar-menu" id="navLinks">
    <ul class="nav-menu">
      <li><a href="<?php echo url('/'); ?>" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">Home</a></li>
      <li><a href="<?php echo url('inventory'); ?>" class="<?php echo $current_page === 'inventory' ? 'active' : ''; ?>">Inventory</a></li>
      <li><a href="<?php echo url('about'); ?>" class="<?php echo $current_page === 'about' ? 'active' : ''; ?>">About</a></li>
      <li><a href="<?php echo url('contact'); ?>" class="<?php echo $current_page === 'contact' ? 'active' : ''; ?>">Inquire</a></li>

      <?php if ($is_logged_in): ?>
        <li class="nav-dropdown account-menu">
          <button type="button" class="dropdown-trigger nav-account-trigger" aria-expanded="false">
            <span class="nav-account-name"><?php echo h($user_name); ?></span>
            <span class="nav-chevron" aria-hidden="true"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a href="<?php echo url('profile'); ?>" class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>">My Profile</a></li>
            <li><a href="<?php echo url('compare'); ?>" class="<?php echo $current_page === 'compare' ? 'active' : ''; ?>">Compare</a></li>
            <li><a href="<?php echo url('logout'); ?>">Logout</a></li>
          </ul>
        </li>
      <?php else: ?>
        <li><a href="<?php echo url('login'); ?>">Login</a></li>
        <li><a href="<?php echo url('register'); ?>">Register</a></li>
      <?php endif; ?>
    </ul>

    <button class="theme-toggle-btn" id="theme-toggle" type="button" aria-label="Toggle theme">
      <span class="theme-switch-track" aria-hidden="true">
        <span class="theme-switch-thumb"></span>
      </span>
      <span class="theme-label"></span>
    </button>
  </div>
</nav>
<div class="nav-backdrop" id="navBackdrop" hidden></div>
