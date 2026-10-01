<?php
// Parikshya Sathi - Programs API Endpoint
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

handle_cors();

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM programs WHERE status = 'active' ORDER BY code ASC");
        $programs = $stmt->fetchAll();

        json_response([
            'success' => true,
            'data' => [
                'programs' => $programs,
                'total' => count($programs)
            ]
        ]);
    }

    if ($method === 'POST') {
        $input = get_json_input();
        $action = $input['action'] ?? 'create';

        if ($action === 'create') {
            $name = trim((string)($input['name'] ?? ''));
            $code = strtoupper(trim((string)($input['code'] ?? '')));
            $dept = trim((string)($input['department'] ?? 'Faculty / Department'));
            $semesters = max(1, (int)($input['total_semesters'] ?? 8));

            if (empty($name) || empty($code)) {
                json_response(['success' => false, 'error' => 'Program name and code are required.'], 400);
            }

            // Check duplicate code
            $chk = $db->prepare("SELECT id FROM programs WHERE UPPER(code) = ?");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                json_response(['success' => false, 'error' => "Program with code '{$code}' already exists."], 400);
            }

            $stmt = $db->prepare("INSERT INTO programs (name, code, department, total_semesters, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $code, $dept, $semesters]);
            $newId = (int)$db->lastInsertId();

            json_response([
                'success' => true,
                'message' => "Program '{$code}' created successfully.",
                'id' => $newId
            ]);
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                json_response(['success' => false, 'error' => 'Invalid program ID.'], 400);
            }

            // Check if students belong to this program
            $chk = $db->prepare("SELECT COUNT(*) FROM students WHERE program_id = ? AND is_deleted = 0");
            $chk->execute([$id]);
            if ((int)$chk->fetchColumn() > 0) {
                json_response(['success' => false, 'error' => 'Cannot delete program with actively enrolled students.'], 400);
            }

            $stmt = $db->prepare("DELETE FROM programs WHERE id = ?");
            $stmt->execute([$id]);

            json_response([
                'success' => true,
                'message' => 'Program removed successfully.'
            ]);
        }

        json_response(['success' => false, 'error' => 'Unknown action.'], 400);
    }

    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);

} catch (Throwable $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
