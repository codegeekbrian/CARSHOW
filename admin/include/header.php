<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$basePath = defined('BASE_PATH') ? BASE_PATH : '/';

$navGroups = [
    'Sales Desk' => [
        ['leads.php', 'Leads', 'sales.manage'],
        ['customers.php', 'Customers', 'customers.manage'],
        ['sales.php', 'Sales', 'sales.manage'],
        ['appointments.php', 'Appointments', 'appointments.manage'],
        ['tradeins.php', 'Trade-ins', 'trade_ins.manage'],
        ['finance_requests.php', 'Finance', 'finance_requests.manage'],
        ['messages.php', 'Messages', 'messages.view'],
    ],
    'Inventory' => [
        ['cars.php', 'Vehicles', 'inventory.manage'],
        ['brands.php', 'Brands', 'brands.manage'],
        ['categories.php', 'Categories', 'categories.manage'],
    ],
    'Content' => [
        ['testimonials.php', 'Testimonials', 'testimonials.manage'],
    ],
    'System' => [
        ['settings.php', 'Site settings', 'settings.manage'],
        ['users.php', 'App users', 'app_users.view'],
        ['logs.php', 'Logs', 'logs.view'],
        ['access.php', 'Team & Roles', 'users.manage'],
    ],
];

function adminNavGroupVisible(array $items): bool
{
    foreach ($items as $item) {
        if (admin_can($item[2])) {
            return true;
        }
    }
    return false;
}

function adminNavGroupActive(array $items, string $currentPage): bool
{
    foreach ($items as $item) {
        if ($item[0] === $currentPage) {
            return true;
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle ?? 'Admin Panel'); ?> - Own It & Drive It</title>
    <script>
        (function () {
            var theme = localStorage.getItem('admin-theme') || 'night';
            if (theme !== 'day') theme = 'night';
            document.documentElement.dataset.adminTheme = theme;
        })();
    </script>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body" data-admin-theme="night">
    <header class="admin-header">
        <button type="button" class="admin-menu-toggle" data-admin-menu-toggle aria-controls="adminNav" aria-expanded="false">
            <span></span><span></span><span></span>
            <span class="sr-only">Open admin menu</span>
        </button>
        <h1><?php echo h($pageTitle ?? 'Admin Panel'); ?></h1>

        <nav class="admin-nav" id="adminNav" aria-label="Admin navigation" data-admin-nav-panel>
            <?php if (admin_can('dashboard.view')): ?>
                <a href="index.php" class="admin-nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <?php endif; ?>
            <?php foreach ($navGroups as $groupLabel => $items): ?>
                <?php if (!adminNavGroupVisible($items)) continue; ?>
                <details class="admin-nav-group <?php echo adminNavGroupActive($items, $currentPage) ? 'active' : ''; ?>">
                    <summary><?php echo h($groupLabel); ?></summary>
                    <div class="admin-subnav">
                        <?php foreach ($items as [$href, $label, $permission]): ?>
                            <?php if (!admin_can($permission)) continue; ?>
                            <a href="<?php echo h($href); ?>" class="<?php echo $currentPage === $href ? 'active' : ''; ?>">
                                <?php echo h($label); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
            <div class="admin-nav-utilities">
                <button type="button" class="admin-theme-toggle" data-admin-theme-toggle aria-label="Switch to day mode" aria-pressed="false">
                    <span class="admin-theme-toggle-track" aria-hidden="true">
                        <span class="admin-theme-toggle-knob"></span>
                    </span>
                    <span data-admin-theme-label>Night</span>
                </button>
                <a href="logout.php" class="admin-mobile-logout">Logout</a>
            </div>
        </nav>

        <details class="admin-user admin-account-menu">
            <summary aria-label="Open admin account menu">
                <span><?php echo h($_SESSION['admin_display_name'] ?? $_SESSION['admin_username']); ?></span>
                <small><?php echo h($_SESSION['admin_position'] ?? 'Dashboard user'); ?></small>
            </summary>
            <div class="admin-account-panel">
                <div>
                    <strong><?php echo h($_SESSION['admin_display_name'] ?? $_SESSION['admin_username']); ?></strong>
                    <small><?php echo h($_SESSION['admin_position'] ?? 'Dashboard user'); ?></small>
                </div>
                <a href="logout.php">Logout</a>
            </div>
        </details>
    </header>

    <div class="admin-nav-backdrop" data-admin-menu-close hidden></div>
