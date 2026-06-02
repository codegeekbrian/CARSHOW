<?php
require_once __DIR__ . '/db.php';

function customerJourneyVehicleSelect(): string
{
    return "
        SELECT v.*, b.name AS brand_name, br.name AS branch_name
        FROM vehicles v
        JOIN brands b ON b.id = v.brand_id
        LEFT JOIN branches br ON br.id = v.branch_id
    ";
}

function customerJourneyVehicleWhere(): string
{
    return "v.is_inventory = 1 AND v.status = 'available'";
}

function saveVehicleForUser(PDO $db, int $userId, int $vehicleId): void
{
    if ($userId < 1 || $vehicleId < 1) {
        throw new InvalidArgumentException('A valid user and vehicle are required.');
    }

    $stmt = $db->prepare("
        INSERT IGNORE INTO saved_vehicles (user_id, vehicle_id)
        SELECT ?, v.id
        FROM vehicles v
        WHERE v.id = ? AND " . customerJourneyVehicleWhere() . "
    ");
    $stmt->execute([$userId, $vehicleId]);
}

function removeSavedVehicleForUser(PDO $db, int $userId, int $vehicleId): void
{
    $stmt = $db->prepare('DELETE FROM saved_vehicles WHERE user_id = ? AND vehicle_id = ?');
    $stmt->execute([$userId, $vehicleId]);
}

function getUserSavedVehicles(PDO $db, int $userId): array
{
    $stmt = $db->prepare(customerJourneyVehicleSelect() . "
        JOIN saved_vehicles sv ON sv.vehicle_id = v.id
        WHERE sv.user_id = ? AND " . customerJourneyVehicleWhere() . "
        ORDER BY sv.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function setUserCompareVehicles(PDO $db, int $userId, array $vehicleIds): void
{
    if ($userId < 1) {
        throw new InvalidArgumentException('A valid user is required.');
    }

    $vehicleIds = array_values(array_unique(array_filter(array_map('intval', $vehicleIds), fn($id) => $id > 0)));
    $vehicleIds = array_slice($vehicleIds, 0, 3);

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM vehicle_comparisons WHERE user_id = ?')->execute([$userId]);
        if (!empty($vehicleIds)) {
            $insert = $db->prepare("
                INSERT IGNORE INTO vehicle_comparisons (user_id, vehicle_id)
                SELECT ?, v.id
                FROM vehicles v
                WHERE v.id = ? AND " . customerJourneyVehicleWhere() . "
            ");
            foreach ($vehicleIds as $vehicleId) {
                $insert->execute([$userId, $vehicleId]);
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function getUserCompareVehicles(PDO $db, int $userId): array
{
    $stmt = $db->prepare(customerJourneyVehicleSelect() . "
        JOIN vehicle_comparisons vc ON vc.vehicle_id = v.id
        WHERE vc.user_id = ? AND " . customerJourneyVehicleWhere() . "
        ORDER BY vc.created_at ASC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createTradeInRequest(PDO $db, array $data): int
{
    $name = trim((string)($data['customer_name'] ?? ''));
    $email = trim((string)($data['customer_email'] ?? ''));
    $make = trim((string)($data['vehicle_make'] ?? ''));
    $model = trim((string)($data['vehicle_model'] ?? ''));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $make === '' || $model === '') {
        throw new InvalidArgumentException('Name, email, make, and model are required.');
    }

    $stmt = $db->prepare('
        INSERT INTO trade_in_requests
            (user_id, vehicle_id, customer_name, customer_email, customer_phone, vehicle_make, vehicle_model, vehicle_year, mileage, expected_value, message)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        !empty($data['user_id']) ? (int)$data['user_id'] : null,
        !empty($data['vehicle_id']) ? (int)$data['vehicle_id'] : null,
        $name,
        $email,
        trim((string)($data['customer_phone'] ?? '')) ?: null,
        $make,
        $model,
        trim((string)($data['vehicle_year'] ?? '')) ?: null,
        trim((string)($data['mileage'] ?? '')) ?: null,
        isset($data['expected_value']) && $data['expected_value'] !== '' ? (float)$data['expected_value'] : null,
        trim((string)($data['message'] ?? '')) ?: null,
    ]);

    return (int)$db->lastInsertId();
}

function createFinanceRequest(PDO $db, array $data): int
{
    $name = trim((string)($data['customer_name'] ?? ''));
    $email = trim((string)($data['customer_email'] ?? ''));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Name and email are required.');
    }

    $stmt = $db->prepare('
        INSERT INTO finance_requests
            (user_id, vehicle_id, customer_name, customer_email, customer_phone, deposit_amount, term_months, monthly_budget, employment_status, message)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        !empty($data['user_id']) ? (int)$data['user_id'] : null,
        !empty($data['vehicle_id']) ? (int)$data['vehicle_id'] : null,
        $name,
        $email,
        trim((string)($data['customer_phone'] ?? '')) ?: null,
        isset($data['deposit_amount']) && $data['deposit_amount'] !== '' ? (float)$data['deposit_amount'] : null,
        isset($data['term_months']) && $data['term_months'] !== '' ? (int)$data['term_months'] : null,
        isset($data['monthly_budget']) && $data['monthly_budget'] !== '' ? (float)$data['monthly_budget'] : null,
        trim((string)($data['employment_status'] ?? '')) ?: null,
        trim((string)($data['message'] ?? '')) ?: null,
    ]);

    return (int)$db->lastInsertId();
}

function getUserJourneyActivity(PDO $db, int $userId, string $email): array
{
    $email = trim($email);

    $appointments = $db->prepare('
        SELECT a.*, v.model, b.name AS brand_name, br.name AS branch_name
        FROM appointments a
        JOIN vehicles v ON v.id = a.vehicle_id
        JOIN brands b ON b.id = v.brand_id
        LEFT JOIN branches br ON br.id = a.branch_id
        WHERE a.customer_email = ?
        ORDER BY a.created_at DESC
        LIMIT 12
    ');
    $appointments->execute([$email]);

    $messages = $db->prepare('
        SELECT *
        FROM messages
        WHERE email = ?
        ORDER BY created_at DESC
        LIMIT 12
    ');
    $messages->execute([$email]);

    $tradeIns = $db->prepare('
        SELECT t.*, v.model AS target_model, b.name AS target_brand
        FROM trade_in_requests t
        LEFT JOIN vehicles v ON v.id = t.vehicle_id
        LEFT JOIN brands b ON b.id = v.brand_id
        WHERE t.user_id = ? OR t.customer_email = ?
        ORDER BY t.created_at DESC
        LIMIT 12
    ');
    $tradeIns->execute([$userId, $email]);

    $finance = $db->prepare('
        SELECT f.*, v.model, b.name AS brand_name
        FROM finance_requests f
        LEFT JOIN vehicles v ON v.id = f.vehicle_id
        LEFT JOIN brands b ON b.id = v.brand_id
        WHERE f.user_id = ? OR f.customer_email = ?
        ORDER BY f.created_at DESC
        LIMIT 12
    ');
    $finance->execute([$userId, $email]);

    return [
        'appointments' => $appointments->fetchAll(PDO::FETCH_ASSOC),
        'messages' => $messages->fetchAll(PDO::FETCH_ASSOC),
        'trade_ins' => $tradeIns->fetchAll(PDO::FETCH_ASSOC),
        'finance' => $finance->fetchAll(PDO::FETCH_ASSOC),
    ];
}
