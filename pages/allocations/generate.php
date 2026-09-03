<?php
// Parikshya Sathi - Allocation Engine Processor
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('pages/allocations/index.php'));
    exit;
}

$eventName = trim($_POST['event_name'] ?? 'Mid-Term Examination');
$eventDate = $_POST['event_date'] ?? date('Y-m-d');
$startTime = trim($_POST['start_time'] ?? '09:00 AM');
$endTime = trim($_POST['end_time'] ?? '12:00 PM');
$notes = trim($_POST['notes'] ?? '');
$selectedCohorts = $_POST['cohorts'] ?? [];
$selectedRooms = $_POST['rooms'] ?? [];

$ruleProgramSeparation = isset($_POST['rule_program_separation']);
$ruleSemesterInterleaving = isset($_POST['rule_semester_interleaving']);
$ruleRandomize = isset($_POST['rule_randomize']);
$allowRelaxation = isset($_POST['allow_relaxation']);

// Check if regenerating for an existing event
$existingEventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;

$db->beginTransaction();
try {
    // 1. Create or fetch Allocation Event
    if ($existingEventId) {
        $eventId = $existingEventId;
    } else {
        $stmtEvent = $db->prepare("INSERT INTO allocation_events (academic_year_id, event_name, event_date, start_time, end_time, status, notes) VALUES (?, ?, ?, ?, ?, 'draft', ?)");
        $stmtEvent->execute([(int)$activeYear['id'], $eventName, $eventDate, $startTime, $endTime, $notes]);
        $eventId = (int)$db->lastInsertId();

        // Insert Participants
        $stmtPart = $db->prepare("INSERT INTO allocation_event_participants (allocation_event_id, program_id, semester, subject_name) VALUES (?, ?, ?, ?)");
        foreach ($selectedCohorts as $ch) {
            if (!empty($ch['selected'])) {
                $stmtPart->execute([$eventId, (int)$ch['program_id'], (int)$ch['semester'], trim($ch['subject_name'] ?? 'Core Paper')]);
            }
        }

        // Insert Rooms
        $stmtRoomMap = $db->prepare("INSERT INTO allocation_event_rooms (allocation_event_id, room_id, priority_order) VALUES (?, ?, ?)");
        $priority = 1;
        foreach ($selectedRooms as $rId) {
            $stmtRoomMap->execute([$eventId, (int)$rId, $priority++]);
        }
    }

    // 2. Fetch all eligible students for the selected cohorts
    $studentPool = [];
    $subjectMap = [];
    foreach ($selectedCohorts as $ch) {
        if (!empty($ch['selected'])) {
            $pId = (int)$ch['program_id'];
            $sem = (int)$ch['semester'];
            $subj = trim($ch['subject_name'] ?? 'Core Paper');

            $stmtSt = $db->prepare("SELECT s.*, p.name as program_name, p.code as program_code 
                FROM students s 
                JOIN programs p ON s.program_id = p.id 
                WHERE s.academic_year_id = ? AND s.program_id = ? AND s.semester = ? AND s.is_deleted = 0 AND s.status = 'active'
                ORDER BY s.symbol_no ASC");
            $stmtSt->execute([(int)$activeYear['id'], $pId, $sem]);
            $cohortStudents = $stmtSt->fetchAll();

            foreach ($cohortStudents as $cStudent) {
                $cStudent['subject_name'] = $subj;
                $studentPool[] = $cStudent;
            }
        }
    }

    // 3. Fetch all active physical seats for selected rooms
    $roomIdsList = array_map('intval', (array)$selectedRooms);
    if (empty($roomIdsList)) {
        // Fallback: fetch all rooms of active year if none checked
        $allRms = get_all_rooms((int)$activeYear['id']);
        $roomIdsList = array_column($allRms, 'id');
    }

    $inClause = implode(',', $roomIdsList);
    $seatQuery = "SELECT s.id, s.room_id, s.seat_identifier, s.row_num, s.col_num, s.seat_number_in_bench, r.room_name, r.building, r.floor
                  FROM seats s
                  JOIN rooms r ON s.room_id = r.id
                  WHERE s.room_id IN ({$inClause}) AND s.status = 'active' AND r.is_deleted = 0 AND r.status = 'active'
                  ORDER BY s.room_id ASC, s.row_num ASC, s.col_num ASC, s.seat_number_in_bench ASC";
    $seats = $db->query($seatQuery)->fetchAll();

    $totalStudents = count($studentPool);
    $totalSeats = count($seats);

    // 4. Calculate Version Number
    $stmtVerNum = $db->prepare("SELECT COALESCE(MAX(version_number), 0) + 1 FROM allocation_versions WHERE allocation_event_id = ?");
    $stmtVerNum->execute([$eventId]);
    $nextVerNumber = (int)$stmtVerNum->fetchColumn();

    // 5. Intelligent Multi-Rule Allocation Algorithm
    // Group students by program
    $byProgram = [];
    foreach ($studentPool as $st) {
        $pCode = $st['program_code'];
        $byProgram[$pCode][] = $st;
    }

    // Shuffle within cohorts if randomization is enabled
    if ($ruleRandomize) {
        foreach ($byProgram as $pCode => &$pList) {
            shuffle($pList);
        }
        unset($pList);
    }

    // Interleave queue across programs
    $interleavedQueue = [];
    $programKeys = array_keys($byProgram);
    $maxCountInProg = max(array_map('count', $byProgram) ?: [0]);

    for ($idx = 0; $idx < $maxCountInProg; $idx++) {
        foreach ($programKeys as $pk) {
            if (isset($byProgram[$pk][$idx])) {
                $interleavedQueue[] = $byProgram[$pk][$idx];
            }
        }
    }

    // Allocate queue into seats
    $allocationsToInsert = [];
    $allocatedCount = 0;
    $unallocatedCount = 0;
    $relaxationLevel = 0;

    foreach ($interleavedQueue as $studentIndex => $student) {
        if ($studentIndex < $totalSeats) {
            $seat = $seats[$studentIndex];
            $allocationsToInsert[] = [
                'student_id' => $student['id'],
                'room_id' => $seat['room_id'],
                'seat_id' => $seat['id'],
                'symbol_no' => $student['symbol_no'],
                'student_name' => $student['name'],
                'program_name' => $student['program_name'],
                'program_code' => $student['program_code'],
                'semester' => $student['semester'],
                'subject_name' => $student['subject_name']
            ];
            $allocatedCount++;
        } else {
            $unallocatedCount++;
        }
    }

    $status = ($unallocatedCount > 0) ? 'draft' : 'active';
    if ($unallocatedCount > 0 && $allowRelaxation) {
        $relaxationLevel = 3; // Partial draft with unallocated students
    }

    $ruleConfig = json_encode([
        'program_separation' => $ruleProgramSeparation,
        'semester_interleaving' => $ruleSemesterInterleaving,
        'randomize' => $ruleRandomize,
        'relaxation_allowed' => $allowRelaxation,
    ]);

    $notesSummary = "Allocated {$allocatedCount}/{$totalStudents} students across " . count($roomIdsList) . " rooms.";
    if ($unallocatedCount > 0) {
        $notesSummary .= " Capacity Shortfall: {$unallocatedCount} unallocated students. Saved as Draft.";
    }

    // Insert Version
    $stmtVer = $db->prepare("INSERT INTO allocation_versions (allocation_event_id, version_number, status, rule_config_json, relaxation_level, total_students, allocated_count, unallocated_count, generation_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtVer->execute([$eventId, $nextVerNumber, $status, $ruleConfig, $relaxationLevel, $totalStudents, $allocatedCount, $unallocatedCount, $notesSummary]);
    $versionId = (int)$db->lastInsertId();

    // Insert Allocations
    $stmtAlloc = $db->prepare("INSERT INTO allocations (allocation_version_id, student_id, room_id, seat_id, symbol_no_snapshot, student_name_snapshot, program_name_snapshot, program_code_snapshot, semester_snapshot, subject_name_snapshot, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
    foreach ($allocationsToInsert as $a) {
        $stmtAlloc->execute([
            $versionId,
            $a['student_id'],
            $a['room_id'],
            $a['seat_id'],
            $a['symbol_no'],
            $a['student_name'],
            $a['program_name'],
            $a['program_code'],
            $a['semester'],
            $a['subject_name']
        ]);
    }

    // Update Event Active Version
    $db->prepare("UPDATE allocation_events SET active_version_id = ?, status = ? WHERE id = ?")->execute([$versionId, $status, $eventId]);

    $db->commit();

    if ($unallocatedCount > 0) {
        set_flash('warning', "Allocation generated as Draft (v{$nextVerNumber}): {$allocatedCount} students seated, {$unallocatedCount} unallocated due to room capacity limits.");
    } else {
        set_flash('success', "Allocation generated successfully (v{$nextVerNumber}): {$allocatedCount} students seated across configured rooms.");
    }

    header('Location: ' . base_url('pages/allocations/editor.php?id=' . $eventId));
    exit;

} catch (Exception $e) {
    $db->rollBack();
    set_flash('danger', 'Allocation failed: ' . $e->getMessage());
    header('Location: ' . base_url('pages/allocations/create.php'));
    exit;
}
