<?php
// Parikshya Sathi - Students API Endpoint
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

handle_cors();

$db = get_db();
$activeYear = get_active_academic_year();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $singleId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($singleId) {
            $stmt = $db->prepare("SELECT s.*, p.name as program_name, p.code as program_code 
                FROM students s 
                JOIN programs p ON s.program_id = p.id 
                WHERE s.id = ? AND s.academic_year_id = ? AND s.is_deleted = 0");
            $stmt->execute([$singleId, (int)$activeYear['id']]);
            $student = $stmt->fetch();

            if (!$student) {
                json_response(['success' => false, 'error' => 'Student not found.'], 404);
            }

            json_response(['success' => true, 'data' => $student]);
        }

        // List with filters
        $search = trim((string)($_GET['search'] ?? ''));
        $programId = isset($_GET['program_id']) && $_GET['program_id'] !== '' ? (int)$_GET['program_id'] : null;
        $semester = isset($_GET['semester']) && $_GET['semester'] !== '' ? (int)$_GET['semester'] : null;
        $section = trim((string)($_GET['section'] ?? ''));

        $query = "SELECT s.*, p.name as program_name, p.code as program_code 
                  FROM students s 
                  JOIN programs p ON s.program_id = p.id 
                  WHERE s.academic_year_id = ? AND s.is_deleted = 0";
        $params = [(int)$activeYear['id']];

        if ($search !== '') {
            $query .= " AND (s.name LIKE ? OR s.symbol_no LIKE ? OR s.roll_no LIKE ?)";
            $wild = '%' . $search . '%';
            $params[] = $wild;
            $params[] = $wild;
            $params[] = $wild;
        }

        if ($programId !== null) {
            $query .= " AND s.program_id = ?";
            $params[] = $programId;
        }

        if ($semester !== null) {
            $query .= " AND s.semester = ?";
            $params[] = $semester;
        }

        if ($section !== '') {
            $query .= " AND s.section = ?";
            $params[] = $section;
        }

        $query .= " ORDER BY s.program_id ASC, s.semester ASC, s.symbol_no ASC, s.roll_no ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll();

        // Get all programs for filter dropdowns
        $programs = get_all_programs();

        json_response([
            'success' => true,
            'data' => [
                'students' => $students,
                'programs' => $programs,
                'total' => count($students),
                'active_year' => $activeYear
            ]
        ]);
    }

    if ($method === 'POST') {
        $input = get_json_input();
        $action = $input['action'] ?? 'create';

        if ($action === 'create') {
            $name = trim((string)($input['name'] ?? ''));
            $symbolNo = trim((string)($input['symbol_no'] ?? ''));
            $rollNo = trim((string)($input['roll_no'] ?? ''));
            $programId = (int)($input['program_id'] ?? 1);
            $semester = (int)($input['semester'] ?? 1);
            $section = trim((string)($input['section'] ?? 'A'));
            $gender = trim((string)($input['gender'] ?? 'Male'));
            $phone = trim((string)($input['phone'] ?? ''));
            $email = trim((string)($input['email'] ?? ''));

            if (empty($name) || empty($symbolNo) || empty($rollNo)) {
                json_response(['success' => false, 'error' => 'Name, Symbol No, and Roll No are required.'], 400);
            }

            if ($programId <= 0) {
                json_response(['success' => false, 'error' => 'Please enroll and select a valid program first.'], 400);
            }

            $chkProg = $db->prepare("SELECT id FROM programs WHERE id = ?");
            $chkProg->execute([$programId]);
            if (!$chkProg->fetch()) {
                json_response(['success' => false, 'error' => 'Selected program was not found. Please add the program first.'], 400);
            }

            // Check duplicate symbol number in this academic year
            $chk = $db->prepare("SELECT id FROM students WHERE academic_year_id = ? AND symbol_no = ? AND is_deleted = 0");
            $chk->execute([(int)$activeYear['id'], $symbolNo]);
            if ($chk->fetch()) {
                json_response(['success' => false, 'error' => "Symbol number '{$symbolNo}' is already registered in this academic year."], 400);
            }

            $stmt = $db->prepare("INSERT INTO students (academic_year_id, symbol_no, roll_no, name, program_id, semester, section, gender, phone, email, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([(int)$activeYear['id'], $symbolNo, $rollNo, $name, $programId, $semester, $section, $gender, $phone, $email]);

            json_response([
                'success' => true,
                'message' => "Student '{$name}' created successfully.",
                'id' => (int)$db->lastInsertId()
            ]);
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? 0);
            $name = trim((string)($input['name'] ?? ''));
            $symbolNo = trim((string)($input['symbol_no'] ?? ''));
            $rollNo = trim((string)($input['roll_no'] ?? ''));
            $programId = (int)($input['program_id'] ?? 1);
            $semester = (int)($input['semester'] ?? 1);
            $section = trim((string)($input['section'] ?? 'A'));
            $gender = trim((string)($input['gender'] ?? 'Male'));
            $phone = trim((string)($input['phone'] ?? ''));
            $email = trim((string)($input['email'] ?? ''));
            $status = trim((string)($input['status'] ?? 'active'));

            if ($id <= 0 || empty($name) || empty($symbolNo) || empty($rollNo)) {
                json_response(['success' => false, 'error' => 'ID, Name, Symbol No, and Roll No are required.'], 400);
            }

            // Check duplicate symbol number for different student
            $chk = $db->prepare("SELECT id FROM students WHERE academic_year_id = ? AND symbol_no = ? AND id != ? AND is_deleted = 0");
            $chk->execute([(int)$activeYear['id'], $symbolNo, $id]);
            if ($chk->fetch()) {
                json_response(['success' => false, 'error' => "Symbol number '{$symbolNo}' is already taken by another student."], 400);
            }

            $stmt = $db->prepare("UPDATE students SET name = ?, symbol_no = ?, roll_no = ?, program_id = ?, semester = ?, section = ?, gender = ?, phone = ?, email = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND academic_year_id = ?");
            $stmt->execute([$name, $symbolNo, $rollNo, $programId, $semester, $section, $gender, $phone, $email, $status, $id, (int)$activeYear['id']]);

            json_response([
                'success' => true,
                'message' => "Student '{$name}' updated successfully."
            ]);
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                json_response(['success' => false, 'error' => 'Invalid student ID.'], 400);
            }

            $stmt = $db->prepare("UPDATE students SET is_deleted = 1 WHERE id = ? AND academic_year_id = ?");
            $stmt->execute([$id, (int)$activeYear['id']]);

            json_response([
                'success' => true,
                'message' => 'Student removed successfully.'
            ]);
        }

        if ($action === 'batch_symbols') {
            $programId = (int)($input['program_id'] ?? 0);
            $semester = (int)($input['semester'] ?? 0);
            $prefix = trim((string)($input['prefix'] ?? ''));
            $startNum = (int)($input['start_number'] ?? 1);
            $padding = (int)($input['padding'] ?? 2);

            if ($programId <= 0 || $semester <= 0 || empty($prefix)) {
                json_response(['success' => false, 'error' => 'Program, Semester, and Prefix are required.'], 400);
            }

            $stmtFetch = $db->prepare("SELECT id FROM students WHERE academic_year_id = ? AND program_id = ? AND semester = ? AND is_deleted = 0 ORDER BY CAST(roll_no AS UNSIGNED) ASC, roll_no ASC");
            $stmtFetch->execute([(int)$activeYear['id'], $programId, $semester]);
            $cohortStudents = $stmtFetch->fetchAll();

            $db->beginTransaction();
            $stmtUpd = $db->prepare("UPDATE students SET symbol_no = ? WHERE id = ?");
            $cur = $startNum;
            foreach ($cohortStudents as $s) {
                $newSymbol = sprintf("%s%0{$padding}d", $prefix, $cur++);
                $stmtUpd->execute([$newSymbol, (int)$s['id']]);
            }
            $db->commit();

            json_response([
                'success' => true,
                'message' => 'Generated symbol numbers for ' . count($cohortStudents) . ' students successfully.'
            ]);
        }

        if ($action === 'import_csv') {
            $rows = $input['rows'] ?? [];
            if (empty($rows) || !is_array($rows)) {
                json_response(['success' => false, 'error' => 'No CSV rows provided.'], 400);
            }

            $programs = get_all_programs();
            $programCodeMap = [];
            foreach ($programs as $p) {
                $programCodeMap[strtoupper(trim($p['code']))] = (int)$p['id'];
            }

            $db->beginTransaction();
            $stmtInsert = $db->prepare("INSERT INTO students (academic_year_id, symbol_no, roll_no, name, program_id, semester, section, gender, phone, email, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

            $imported = 0;
            $skipped = 0;

            foreach ($rows as $r) {
                $name = trim((string)($r['name'] ?? ''));
                $symbol = trim((string)($r['symbol_no'] ?? ''));
                $roll = trim((string)($r['roll_no'] ?? ''));
                $progCode = strtoupper(trim((string)($r['program_code'] ?? 'BCA')));
                $sem = (int)($r['semester'] ?? 1);
                $sec = trim((string)($r['section'] ?? 'A'));
                $gender = trim((string)($r['gender'] ?? 'Other'));
                $phone = trim((string)($r['phone'] ?? ''));
                $email = trim((string)($r['email'] ?? ''));

                if (empty($name) || empty($symbol) || empty($roll)) {
                    $skipped++;
                    continue;
                }

                if (!isset($programCodeMap[$progCode])) {
                    $stmtNewProg = $db->prepare("INSERT INTO programs (name, code, department, total_semesters, status) VALUES (?, ?, 'Academic Department', 8, 'active')");
                    $stmtNewProg->execute([$progCode, $progCode]);
                    $newProgId = (int)$db->lastInsertId();
                    $programCodeMap[$progCode] = $newProgId;
                }
                $pId = $programCodeMap[$progCode];

                // Check duplicate
                $chk = $db->prepare("SELECT id FROM students WHERE academic_year_id = ? AND symbol_no = ? AND is_deleted = 0");
                $chk->execute([(int)$activeYear['id'], $symbol]);
                if ($chk->fetch()) {
                    $skipped++;
                    continue;
                }

                $stmtInsert->execute([(int)$activeYear['id'], $symbol, $roll, $name, $pId, $sem, $sec, $gender, $phone, $email]);
                $imported++;
            }

            $db->commit();

            json_response([
                'success' => true,
                'message' => "Successfully imported {$imported} students ({$skipped} skipped due to duplicates or missing fields)."
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
