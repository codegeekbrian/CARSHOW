<?php
/**
 * Simple .env file loader
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') === false) {
            // skip malformed line
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));
        // Basic validation of name
        if ($name === '' || preg_match('/[^A-Z0-9_]/i', $name)) {
            continue;
        }

        // Strip optional surrounding quotes from value
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Auto-load .env from root
loadEnv(__DIR__ . '/../.env');
