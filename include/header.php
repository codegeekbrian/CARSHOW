<?php
/**
 * Shared Site Header
 * Handles HTML5 boilerplate, meta tags, and global navigation.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/csrf.php';

if (!isset($settings)) {
    $settings = loadSettings($db ?? null);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($pageTitle) ? $pageTitle . ' — ' : '') . htmlspecialchars(SITE_NAME); ?></title>
    <meta name="description" content="<?php echo h($pageDescription ?? ($settings['meta_description_default'] ?? "Kenya's premier car dealership and automotive enthusiast hub.")); ?>">
    <meta name="csrf-token" content="<?php echo h(csrf_token()); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo url('assets/images/favicon.svg'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/main.css'); ?>">
</head>
<body class="page-<?php echo $current_page ?? 'default'; ?>">

<?php include __DIR__ . '/nav.php'; ?>
