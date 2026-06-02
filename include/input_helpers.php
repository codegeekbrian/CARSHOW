<?php
/**
 * Global Input & Content Helpers
 * Consolidates repeated logic for image handling, strings, and slugs.
 */

/**
 * Encodes an image URL for safe HTML usage.
 * @param string $path
 * @return string
 */
function encodeImagePath($path) {
    if (empty($path)) return '';
    return implode('/', array_map('rawurlencode', explode('/', (string)$path)));
}

/**
 * Returns a fallback image path if the primary doesn't exist (conceptual).
 * For now, returns the encoded string.
 */
function getVehicleImage($path) {
    return url(encodeImagePath($path));
}

/**
 * Generates a URL-friendly slug from a brand name.
 * @param string $name
 * @return string
 */
function slugify($name) {
    return strtolower(str_replace(' ', '-', (string)$name));
}

/**
 * Escapes a string for safe HTML output.
 * @param string $val
 * @return string
 */
function h($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

/**
 * Strips tags and trims whitespace from a string.
 * @param string $str
 * @return string
 */
function sanitize_text($str) {
    return trim(strip_tags((string)$str));
}

/**
 * Sanitizes an email address string.
 * @param string $str
 * @return string
 */
function sanitize_email($str) {
    return trim(filter_var((string)$str, FILTER_SANITIZE_EMAIL));
}
