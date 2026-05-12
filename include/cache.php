<?php
/**
 * Simple file-based caching system
 */

define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_DURATION', 3600); // 1 hour

/**
 * Get cached content
 * @param string $key Cache key
 * @return mixed Cached content or false if not found/expired
 */
function getCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.cache';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_DURATION) {
        return unserialize(file_get_contents($cacheFile));
    }
    
    return false;
}

/**
 * Set cached content
 * @param string $key Cache key
 * @param mixed $data Data to cache
 * @return bool Success
 */
function setCache($key, $data) {
    $cacheFile = CACHE_DIR . md5($key) . '.cache';
    return file_put_contents($cacheFile, serialize($data)) !== false;
}

/**
 * Clear cache for a specific key
 * @param string $key Cache key
 * @return bool Success
 */
function clearCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    return true;
}

/**
 * Clear all cache
 * @return bool Success
 */
function clearAllCache() {
    $files = glob(CACHE_DIR . '*.cache');
    foreach ($files as $file) {
        unlink($file);
    }
    return true;
}
?>