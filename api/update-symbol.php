<?php
// Parikshya Sathi - API: Inline Symbol Number Updater
declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['student_id']) || empty($data['symbol_no'])) {
    echo json_encode(['success' => false, 'message' => 'Missing student_id or symbol_no']);
    exit;
}

$studentId = (int)$data['student_id'];
$symbolNo = trim((string)$data['symbol_no']);

$db = get_db();
try {
    // Check duplicate
    $stmt = $db->prepare("SELECT id FROM students WHERE symbol_no = ? AND id != ? AND is_deleted = 0");
    $stmt->execute([$symbolNo, $studentId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Symbol number is already assigned to another student.']);
        exit;
    }

    $db->prepare("UPDATE students SET symbol_no = ? WHERE id = ?")->execute([$symbolNo, $studentId]);
    echo json_encode(['success' => true, 'message' => 'Symbol number updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
