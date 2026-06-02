<?php
/**
 * Admin inventory presentation and bulk update helpers.
 */

function formatAdminVehiclePrice($price): string
{
    if ($price === null || $price === '' || (float)$price <= 0) {
        return 'Call for price';
    }
    return '$' . number_format((float)$price);
}

function selectedAdminVehicleIds(array $rawIds): array
{
    $ids = [];
    foreach ($rawIds as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_values($ids);
}

function vehiclePlaceholders(array $ids): string
{
    return implode(',', array_fill(0, count($ids), '?'));
}

function applyAdminVehicleBulkAction(PDO $db, array $rawIds, string $bulkAction, string $adminUsername): string
{
    $vehicleIds = selectedAdminVehicleIds($rawIds);
    if (empty($vehicleIds)) {
        return 'Select at least one vehicle first.';
    }

    $statusByAction = [
        'status_available' => 'available',
        'status_reserved' => 'reserved',
        'status_sold' => 'sold',
    ];

    try {
        $placeholders = vehiclePlaceholders($vehicleIds);
        $db->beginTransaction();

        if (isset($statusByAction[$bulkAction])) {
            $newStatus = $statusByAction[$bulkAction];
            $currentStmt = $db->prepare("SELECT id, status FROM vehicles WHERE id IN ({$placeholders})");
            $currentStmt->execute($vehicleIds);
            $currentRows = $currentStmt->fetchAll(PDO::FETCH_ASSOC);

            $update = $db->prepare("UPDATE vehicles SET status = ? WHERE id IN ({$placeholders})");
            $update->execute(array_merge([$newStatus], $vehicleIds));

            $history = $db->prepare('
                INSERT INTO vehicle_status_history (vehicle_id, old_status, new_status, changed_by, note)
                VALUES (?, ?, ?, ?, ?)
            ');
            foreach ($currentRows as $row) {
                $history->execute([(int)$row['id'], $row['status'] ?? null, $newStatus, $adminUsername, 'Bulk inventory update.']);
            }

            recordAdminAuditLog($db, 'vehicle.bulk_status', 'vehicle', null, 'Bulk set ' . count($vehicleIds) . " vehicles to {$newStatus}");
            $message = count($vehicleIds) . ' vehicle status' . (count($vehicleIds) === 1 ? '' : 'es') . ' updated.';
        } elseif ($bulkAction === 'feature_on' || $bulkAction === 'feature_off') {
            $featured = $bulkAction === 'feature_on' ? 1 : 0;
            $update = $db->prepare("UPDATE vehicles SET is_featured = ? WHERE id IN ({$placeholders})");
            $update->execute(array_merge([$featured], $vehicleIds));

            recordAdminAuditLog($db, $featured ? 'vehicle.bulk_featured' : 'vehicle.bulk_unfeatured', 'vehicle', null, 'Bulk updated featured vehicles.');
            $message = count($vehicleIds) . ' vehicle' . (count($vehicleIds) === 1 ? '' : 's') . ' updated.';
        } elseif ($bulkAction === 'delete') {
            $delete = $db->prepare("DELETE FROM vehicles WHERE id IN ({$placeholders})");
            $delete->execute($vehicleIds);

            recordAdminAuditLog($db, 'vehicle.bulk_deleted', 'vehicle', null, 'Bulk deleted ' . count($vehicleIds) . ' vehicles.');
            $message = count($vehicleIds) . ' vehicle' . (count($vehicleIds) === 1 ? '' : 's') . ' deleted.';
        } else {
            throw new InvalidArgumentException('Choose a valid bulk action.');
        }

        $db->commit();
        clearCacheByTag('inventory');
        clearCacheByTag('featured');
        return $message;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return 'Bulk action failed: ' . $e->getMessage();
    }
}
