<?php
// Parikshya Sathi - Academic Years API Endpoint
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

handle_cors();

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM academic_years ORDER BY id DESC");
        $years = $stmt->fetchAll();
        $activeYear = get_active_academic_year();

        json_response([
            'success' => true,
            'data' => [
                'years' => $years,
                'active_year' => $activeYear
            ]
        ]);
    }

    if ($method === 'POST') {
        $input = get_json_input();
        $action = $input['action'] ?? 'create';

        if ($action === 'set_active') {
            $yearId = (int)($input['id'] ?? 0);
            if ($yearId <= 0) {
                json_response(['success' => false, 'error' => 'Invalid academic year ID.'], 400);
            }

            $db->beginTransaction();
            $db->exec("UPDATE academic_years SET is_active = 0");
            $stmt = $db->prepare("UPDATE academic_years SET is_active = 1, status = 'active' WHERE id = ?");
            $stmt->execute([$yearId]);
            $db->commit();

            $_SESSION['active_academic_year_id'] = $yearId;

            json_response([
                'success' => true,
                'message' => 'Active academic year switched successfully.',
                'active_year' => get_active_academic_year()
            ]);
        }

        if ($action === 'create') {
            $name = trim((string)($input['name'] ?? ''));
            $startDate = !empty($input['start_date']) ? $input['start_date'] : null;
            $endDate = !empty($input['end_date']) ? $input['end_date'] : null;
            $setAsActive = !empty($input['set_active']);

            if (empty($name)) {
                json_response(['success' => false, 'error' => 'Academic year name is required.'], 400);
            }

            $db->beginTransaction();
            if ($setAsActive) {
                $db->exec("UPDATE academic_years SET is_active = 0");
            }

            $stmt = $db->prepare("INSERT INTO academic_years (name, start_date, end_date, status, is_active) VALUES (?, ?, ?, 'active', ?)");
            $stmt->execute([$name, $startDate, $endDate, $setAsActive ? 1 : 0]);
            $newId = (int)$db->lastInsertId();

            if ($setAsActive) {
                $_SESSION['active_academic_year_id'] = $newId;
            }

            $db->commit();

            json_response([
                'success' => true,
                'message' => "Academic year '{$name}' created successfully.",
                'id' => $newId
            ]);
        }

        if ($action === 'archive') {
            $yearId = (int)($input['id'] ?? 0);
            if ($yearId <= 0) {
                json_response(['success' => false, 'error' => 'Invalid academic year ID.'], 400);
            }

            $stmt = $db->prepare("UPDATE academic_years SET status = 'archived', is_active = 0 WHERE id = ?");
            $stmt->execute([$yearId]);

            json_response([
                'success' => true,
                'message' => 'Academic year archived.'
            ]);
        }

        json_response(['success' => false, 'error' => 'Unknown action.'], 400);
    }

    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
