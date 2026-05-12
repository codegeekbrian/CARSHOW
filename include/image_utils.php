
<?php
/**
 * Image utilities for CARSHOW
 * Handles image path validation and fallback images
 */

class ImageUtils {

    /** @var string Path to fallback images */
    private const FALLBACK_DIR = 'assets/images/fallback/';

    /** @var array List of fallback images */
    private const FALLBACK_IMAGES = [
        'car_placeholder.jpg',
        'generic_vehicle.jpg',
        'image_not_found.jpg'
    ];

    /**
     * Validate image path and return valid path
     *
     * @param string $imagePath Original image path
     * @return string Valid image path (original or fallback)
     */
    public static function getValidImagePath(string $imagePath): string {
        // Check if image exists
        if (self::imageExists($imagePath)) {
            return $imagePath;
        }

        // Return fallback image
        return self::getRandomFallback();
    }

    /**
     * Check if image file exists
     *
     * @param string $path Image path
     * @return bool True if image exists
     */
    private static function imageExists(string $path): bool {
        // Handle relative paths
        $fullPath = self::getAbsolutePath($path);

        return file_exists($fullPath) && is_file($fullPath);
    }

    /**
     * Get absolute path for image
     *
     * @param string $relativePath Relative path
     * @return string Absolute path
     */
    private static function getAbsolutePath(string $relativePath): string {
        // Handle different path formats
        return realpath(__DIR__ . '/../' . $relativePath) ?: $relativePath;
    }

    /**
     * Get random fallback image
     *
     * @return string Fallback image path
     */
    private static function getRandomFallback(): string {
        $fallback = self::FALLBACK_IMAGES[array_rand(self::FALLBACK_IMAGES)];
        return self::FALLBACK_DIR . $fallback;
    }

    /**
     * Optimize image path for web
     *
     * @param string $path Original path
     * @return string Optimized path
     */
    public static function optimizeImagePath(string $path): string {
        // Add caching busting parameter
        $cacheBust = '?v=' . filemtime(self::getAbsolutePath($path));
        return $path . $cacheBust;
    }
}
?>