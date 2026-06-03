<?php
// Main router and entry point

// Routing - get the path after /index.php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /index.php prefix if present
if (strpos($uri, '/index.php') === 0) {
    $uri = substr($uri, 11); // remove '/index.php'
}
$uri = rtrim($uri, '/');
if ($uri === '') $uri = '/';

// Load config first
require_once __DIR__ . '/include/config.php';

// Flag to tell included pages to skip their own header/footer
define('IS_INDEX_INCLUDE', true);

// Route mapping
$routes = [
    '/' => 'home',
    '/inventory' => 'inventory',
    '/about' => 'about',
    '/contact' => 'contact',
    '/inquire' => 'contact',
    '/testimonials' => 'testimonials',
    '/api/contact' => 'api_contact',
    '/api/testimonial-comment' => 'api_testimonial_comment',
    '/api/vehicles' => 'api_vehicles',
    '/api/customer-journey' => 'api_customer_journey',
    '/login' => 'login',
    '/register' => 'register',
    '/profile' => 'profile',
    '/compare' => 'compare',
    '/logout' => 'logout',
];

// Dynamic routes
if (preg_match('#^/vehicle/(\d+)$#', $uri, $m)) {
    $_GET['id'] = (int) $m[1];
    $page = 'vehicle';
} elseif (isset($routes[$uri])) {
    $page = $routes[$uri];
} else {
    http_response_code(404);
    $page = '404';
}

// Load database for all pages except static
if (in_array($page, ['home', 'inventory', 'vehicle', 'about', 'contact', 'testimonials', 'profile', 'compare', 'login', 'register'])) {
    require_once __DIR__ . '/include/db.php';
}

// Route to the appropriate page
switch ($page) {
    case 'home':
        $current_page = 'index';
        $pageTitle = "Kenya's Premier Car Destination";
        require_once __DIR__ . '/include/header.php';
        include __DIR__ . '/pages/index_content.php';
        break;

    case 'inventory':
        $current_page = 'inventory';
        $pageTitle = "Inventory";
        require_once __DIR__ . '/include/header.php';
        include __DIR__ . '/pages/inventory.php';
        break;

    case 'about':
        $current_page = 'about';
        require_once __DIR__ . '/include/analytics.php';
        logActivity('view');
        $pageTitle = "About Us";
        require_once __DIR__ . '/include/header.php';
        include __DIR__ . '/pages/about.php';
        break;

    case 'contact':
        $current_page = 'contact';
        require_once __DIR__ . '/include/analytics.php';
        logActivity('view');
        $pageTitle = "Inquire";
        require_once __DIR__ . '/include/header.php';
        include __DIR__ . '/pages/contact.php';
        break;

    case 'testimonials':
        $current_page = 'testimonials';
        require_once __DIR__ . '/include/analytics.php';
        logActivity('view');
        $pageTitle = "Testimonials";
        require_once __DIR__ . '/include/header.php';
        include __DIR__ . '/pages/testimonials.php';
        break;

    case 'api_contact':
        include __DIR__ . '/include/contact_handler.php';
        break;

    case 'api_testimonial_comment':
        include __DIR__ . '/include/testimonial_comment_handler.php';
        break;

    case 'api_vehicles':
        include __DIR__ . '/include/api_vehicles.php';
        break;

    case 'api_customer_journey':
        include __DIR__ . '/include/customer_journey_handler.php';
        break;

    case 'login':
        $current_page = 'login';
        include __DIR__ . '/pages/auth/login.php';
        break;

    case 'register':
        $current_page = 'register';
        include __DIR__ . '/pages/auth/register.php';
        break;

    case 'profile':
        $current_page = 'profile';
        include __DIR__ . '/pages/auth/profile.php';
        break;

    case 'compare':
        $current_page = 'compare';
        include __DIR__ . '/pages/compare.php';
        break;

    case 'logout':
        require_once __DIR__ . '/pages/auth/logout.php';
        break;

    case 'vehicle':
        $current_page = 'inventory';
        require_once __DIR__ . '/include/analytics.php';
        $pageTitle = "Vehicle Details";
        require_once __DIR__ . '/include/header.php';
        include __DIR__ . '/pages/vehicle.php';
        break;

    default:
        http_response_code(404);
        require_once __DIR__ . '/include/header.php';
        include __DIR__ . '/pages/404.php';
        break;
}

// Footer for all pages except logout
if (!in_array($page, ['login', 'register', 'profile', 'logout', 'api_contact', 'api_testimonial_comment', 'api_vehicles', 'api_customer_journey'], true)) {
    include __DIR__ . '/include/footer.php';
}
