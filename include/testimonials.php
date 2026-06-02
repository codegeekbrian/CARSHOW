<?php
/**
 * Shared testimonial data helpers.
 */

function getActiveTestimonials(int $limit = 8, ?PDO $connection = null): array
{
    $limit = max(1, min(24, $limit));
    $db = $connection;

    if (!$db && isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
        $db = $GLOBALS['db'];
    }

    if (!$db) {
        require __DIR__ . '/db.php';
        $db = $GLOBALS['db'] ?? null;
    }

    if (!$db instanceof PDO) {
        return [];
    }

    try {
        $stmt = $db->prepare("
            SELECT
                t.client_name,
                t.location,
                t.quote,
                t.verified_buyer,
                t.vehicle_id,
                CONCAT(COALESCE(b.name, ''), ' ', COALESCE(v.model, '')) AS vehicle_label
            FROM testimonials t
            LEFT JOIN vehicles v ON v.id = t.vehicle_id
            LEFT JOIN brands b ON b.id = v.brand_id
            WHERE t.is_active = 1
            ORDER BY t.id DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function testimonialFallbackRows(array $settings = []): array
{
    return [[
        'quote' => $settings['testimonial_fallback_quote'] ?? 'Great selection of vehicles and excellent customer service.',
        'client_name' => $settings['testimonial_fallback_author'] ?? 'Client',
        'location' => SITE_LOCATION,
        'verified_buyer' => 0,
        'vehicle_label' => null,
    ]];
}
