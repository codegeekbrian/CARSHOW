<?php
/**
 * Shared settings loader.
 */

function loadSettings(?PDO $connection = null): array
{
    static $settingsCache = null;

    if ($settingsCache !== null) {
        return $settingsCache;
    }

    $settingsCache = [];
    $db = $connection;

    if (!$db && isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
        $db = $GLOBALS['db'];
    }

    if (!$db) {
        return $settingsCache;
    }

    try {
        $stmt = $db->query('SELECT setting_key, setting_value FROM settings');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settingsCache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        $settingsCache = [];
    }

    return $settingsCache;
}
