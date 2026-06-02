<?php
/**
 * Simple file-based caching system with tag support
 */

if (!defined('CACHE_DIR')) {
    define('CACHE_DIR', __DIR__ . '/../cache/');
}
if (!defined('CACHE_DURATION')) {
    define('CACHE_DURATION', 3600); // 1 hour
}

/**
 * Internal helper to get cache file path
 * @param string $key Cache key
 * @param string|null $tag Optional tag
 * @return string File path
 */
function _getCachePath($key, $tag = null) {
    $hash = md5($key);
    if ($tag) {
        $cleanTag = preg_replace('/[^a-zA-Z0-9_\-]/', '', $tag);
        return CACHE_DIR . "tag_{$cleanTag}__{$hash}.cache";
    }
    return CACHE_DIR . "{$hash}.cache";
}

/**
 * Get cached content
 * @param string $key Cache key
 * @param string|null $tag Optional tag (recommended if set with one)
 * @return mixed Cached content or false if not found/expired
 */
function getCache($key, $tag = null) {
    $cacheFile = _getCachePath($key, $tag);
    
    // If tag was not provided but file not found, try finding any tagged version
    if (!file_exists($cacheFile) && !$tag) {
        $hash = md5($key);
        $files = glob(CACHE_DIR . "tag_*__{$hash}.cache");
        if (!empty($files)) {
            $cacheFile = $files[0];
        }
    }

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_DURATION) {
        $content = file_get_contents($cacheFile);
        return $content ? unserialize($content) : false;
    }

    return false;
}

/**
 * Set cached content
 * @param string $key Cache key
 * @param mixed $data Data to cache
 * @param string|null $tag Optional tag for group clearing
 * @return bool Success
 */
function setCache($key, $data, $tag = null) {
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }
    $cacheFile = _getCachePath($key, $tag);
    return file_put_contents($cacheFile, serialize($data)) !== false;
}

/**
 * Clear cache for a specific key
 * @param string $key Cache key
 * @param string|null $tag Optional tag
 * @return bool Success
 */
function clearCache($key, $tag = null) {
    $cacheFile = _getCachePath($key, $tag);
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }

    // If untagged lookup failed, try finding any tagged version
    if (!$tag) {
        $hash = md5($key);
        $files = glob(CACHE_DIR . "tag_*__{$hash}.cache");
        foreach ($files as $file) {
            unlink($file);
        }
    }
    return true;
}

/**
 * Clear all cache files associated with a specific tag
 * @param string $tag Tag to clear
 * @return bool Success
 */
function clearCacheByTag($tag) {
    $cleanTag = preg_replace('/[^a-zA-Z0-9_\-]/', '', $tag);
    $files = glob(CACHE_DIR . "tag_{$cleanTag}__*.cache");
    foreach ($files as $file) {
        unlink($file);
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
