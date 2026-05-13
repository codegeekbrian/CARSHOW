<?php
/**
 * Image Optimization Script
 * Scans assets/images and attempts to optimize/convert to WebP using GD library.
 */
require_once __DIR__ . '/../include/config.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

$image_dirs = [
    dirname(__DIR__) . '/assets/images/products',
    dirname(__DIR__) . '/assets/images/gtr',
    dirname(__DIR__) . '/assets/images/lancer',
    dirname(__DIR__) . '/assets/images/supra',
    dirname(__DIR__) . '/assets/images/charger',
    dirname(__DIR__) . '/assets/images/mazda',
    dirname(__DIR__) . '/assets/images/nsx',
    dirname(__DIR__) . '/assets/images/koenigsegg',
];

echo "Starting image optimization...\n";

foreach ($image_dirs as $dir) {
    if (!is_dir($dir)) continue;
    
    echo "Processing directory: $dir\n";
    $files = glob($dir . '/*.{jpg,jpeg,png}', GLOB_BRACE);
    
    foreach ($files as $file) {
        $info = getimagesize($file);
        if (!$info) continue;
        
        $mime = $info['mime'];
        $webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
        
        if (file_exists($webp_file)) {
            echo "  Skipping (WebP exists): " . basename($file) . "\n";
            continue;
        }

        echo "  Optimizing: " . basename($file) . " -> WebP\n";
        
        $image = null;
        if ($mime === 'image/jpeg') {
            $image = imagecreatefromjpeg($file);
        } elseif ($mime === 'image/png') {
            $image = imagecreatefrompng($file);
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
        
        if ($image) {
            // Convert to WebP with 80% quality
            if (imagewebp($image, $webp_file, 80)) {
                echo "    Success!\n";
            } else {
                echo "    Failed to save WebP.\n";
            }
            imagedestroy($image);
        }
    }
}

echo "Optimization complete.\n";
