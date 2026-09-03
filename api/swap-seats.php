<?php
// Parikshya Sathi - API: Seat Swapping & Manual Reassignment
declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$versionId = (int)($data['version_id'] ?? 0);
$alloc1Id = !empty($data['alloc1_id']) ? (int)$data['alloc1_id'] : null;
$seat1Id = (int)($data['seat1_id'] ?? 0);
$alloc2Id = !empty($data['alloc2_id']) ? (int)$data['alloc2_id'] : null;
$seat2Id = (int)($data['seat2_id'] ?? 0);

if (!$versionId || (!$seat1Id && !$seat2Id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required seat or version parameters']);
    exit;
}

$db = get_db();
$db->beginTransaction();
try {
    // Case 1: Both seats had allocations -> Swap seats
    if ($alloc1Id && $alloc2Id) {
        $db->prepare("UPDATE allocations SET seat_id = ? WHERE id = ? AND allocation_version_id = ?")->execute([$seat2Id, $alloc1Id, $versionId]);
        $db->prepare("UPDATE allocations SET seat_id = ? WHERE id = ? AND allocation_version_id = ?")->execute([$seat1Id, $alloc2Id, $versionId]);
    }
    // Case 2: Only seat 1 was occupied, moved to empty seat 2
    elseif ($alloc1Id && !$alloc2Id && $seat2Id) {
        $db->prepare("UPDATE allocations SET seat_id = ? WHERE id = ? AND allocation_version_id = ?")->execute([$seat2Id, $alloc1Id, $versionId]);
    }
    // Case 3: Only seat 2 was occupied, moved to empty seat 1
    elseif (!$alloc1Id && $alloc2Id && $seat1Id) {
        $db->prepare("UPDATE allocations SET seat_id = ? WHERE id = ? AND allocation_version_id = ?")->execute([$seat1Id, $alloc2Id, $versionId]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Seats swapped successfully']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
