<?php

function encodeImagePath($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    return implode('/', array_map('rawurlencode', explode('/', $path)));
}

function getVehicleImage($path) {
    $path = trim((string)$path);
    if ($path === '') return '';

    if (preg_match('#^https?://#i', $path)) {
        return h($path);
    }

    return h(url(encodeImagePath($path)));
}

function slugify($name) {
    return strtolower(str_replace(' ', '-', (string)$name));
}

function h($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function sanitize_text($str) {
    return trim(strip_tags((string)$str));
}

function sanitize_email($str) {
    return trim(filter_var((string)$str, FILTER_SANITIZE_EMAIL));
}
