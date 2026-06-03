<?php

$root = dirname(__DIR__);

function file_contents_from_root(string $relativePath): string
{
    global $root;
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        throw new RuntimeException("Missing file: {$relativePath}");
    }
    return file_get_contents($path) ?: '';
}

function assert_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function assert_not_contains(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) !== false) {
        throw new RuntimeException($message);
    }
}

$home = file_contents_from_root('pages/index_content.php');
$inventory = file_contents_from_root('pages/inventory.php');
$router = file_contents_from_root('index.php');
$inventoryJs = file_contents_from_root('assets/js/inventory-filter.js');
$componentsCss = file_contents_from_root('assets/css/components.css');
$componentCss = implode("\n", [
    file_contents_from_root('assets/css/components/vehicle-cards-inventory.css'),
    file_contents_from_root('assets/css/components/responsive.css'),
    file_contents_from_root('assets/css/components/page-panels.css'),
]);

assert_contains($home, "v.is_inventory = 1", 'Home featured query must only use inventory vehicles.');
assert_contains($home, "onerror=", 'Home featured images need the same fallback protection as inventory cards.');
assert_not_contains($inventory, '<section class="trust-row"', 'Inventory trust row should be removed.');
assert_not_contains($router, "'compare', 'logout'", 'Compare must use the shared footer so navigation scripts load.');
assert_not_contains($inventoryJs, 'style="height:', 'Inventory skeletons should use reusable CSS classes, not inline dimensions.');
assert_contains($componentsCss, '@import url("components/vehicle-cards-inventory.css");', 'Components manifest should load split inventory styles.');
assert_contains($componentCss, 'scroll-snap-type: x mandatory', 'Mobile inventory carousel needs horizontal snap scrolling.');
assert_contains($componentCss, 'scroll-snap-align: start', 'Mobile inventory cards need snap alignment.');
assert_contains($componentCss, '.profile-shell', 'Profile dashboard needs dedicated layout styles.');
assert_contains($componentCss, '.about-box', 'About page needs dedicated layout styles.');
assert_contains($componentCss, '.compare-page-grid', 'Compare page needs dedicated layout styles.');

echo "Public UI contracts passed.\n";
