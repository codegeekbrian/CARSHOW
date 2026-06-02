<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/session_helpers.php';
require_once __DIR__ . '/customer_journey.php';
enforce_user_session_timeout($db);

function customerJourneyJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function currentUserIdForJourney(): int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function requireJourneyUser(): int
{
    $userId = currentUserIdForJourney();
    if ($userId < 1) {
        customerJourneyJson([
            'success' => false,
            'requires_login' => true,
            'message' => 'Please log in to use saved cars, compare, finance, or trade-in tools.',
        ], 401);
    }
    return $userId;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = currentUserIdForJourney();
    if ($userId < 1) {
        customerJourneyJson([
            'success' => true,
            'logged_in' => false,
            'saved_ids' => [],
            'compare_ids' => [],
            'saved' => [],
            'compare' => [],
        ]);
    }

    $saved = getUserSavedVehicles($db, $userId);
    $compare = getUserCompareVehicles($db, $userId);
    customerJourneyJson([
        'success' => true,
        'logged_in' => true,
        'saved_ids' => array_map(fn($row) => (int)$row['id'], $saved),
        'compare_ids' => array_map(fn($row) => (int)$row['id'], $compare),
        'saved' => $saved,
        'compare' => $compare,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    customerJourneyJson(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!validateCSRFToken($csrfToken)) {
    customerJourneyJson(['success' => false, 'message' => 'Security token mismatch. Please refresh and try again.'], 403);
}

$action = $_POST['action'] ?? '';
$userId = requireJourneyUser();

try {
    if ($action === 'save') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        saveVehicleForUser($db, $userId, $vehicleId);
        customerJourneyJson(['success' => true, 'message' => 'Vehicle saved.', 'saved_ids' => array_map(fn($row) => (int)$row['id'], getUserSavedVehicles($db, $userId))]);
    }

    if ($action === 'unsave') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        removeSavedVehicleForUser($db, $userId, $vehicleId);
        customerJourneyJson(['success' => true, 'message' => 'Vehicle removed from saved cars.', 'saved_ids' => array_map(fn($row) => (int)$row['id'], getUserSavedVehicles($db, $userId))]);
    }

    if ($action === 'compare') {
        $ids = $_POST['vehicle_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = explode(',', (string)$ids);
        }
        setUserCompareVehicles($db, $userId, $ids);
        customerJourneyJson(['success' => true, 'message' => 'Compare list updated.', 'compare_ids' => array_map(fn($row) => (int)$row['id'], getUserCompareVehicles($db, $userId))]);
    }

    if ($action === 'clear_compare') {
        setUserCompareVehicles($db, $userId, []);
        customerJourneyJson(['success' => true, 'message' => 'Compare cleared.', 'compare_ids' => []]);
    }

    if ($action === 'clear_saved') {
        $db->prepare('DELETE FROM saved_vehicles WHERE user_id = ?')->execute([$userId]);
        customerJourneyJson(['success' => true, 'message' => 'Saved vehicles cleared.', 'saved_ids' => []]);
    }

    if ($action === 'trade_in') {
        $payload = $_POST;
        $payload['user_id'] = $userId;
        $tradeId = createTradeInRequest($db, $payload);
        customerJourneyJson(['success' => true, 'message' => 'Trade-in request received. Our team will review it.', 'id' => $tradeId]);
    }

    if ($action === 'finance') {
        $payload = $_POST;
        $payload['user_id'] = $userId;
        $financeId = createFinanceRequest($db, $payload);
        customerJourneyJson(['success' => true, 'message' => 'Finance request received. Our team will review it.', 'id' => $financeId]);
    }

    customerJourneyJson(['success' => false, 'message' => 'Unknown customer action.'], 400);
} catch (Throwable $e) {
    error_log('Customer journey action failed: ' . $e->getMessage());
    customerJourneyJson(['success' => false, 'message' => $e->getMessage()], 400);
}
