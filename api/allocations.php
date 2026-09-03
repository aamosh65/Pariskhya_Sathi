<?php
// Parikshya Sathi - Allocation Engine & Operations API Endpoint
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

handle_cors();

$db = get_db();
$activeYear = get_active_academic_year();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $eventId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($eventId) {
            // Fetch Event with active version
            $stmt = $db->prepare("SELECT e.*, v.version_number, v.id as version_id, v.allocated_count, v.total_students as version_students, v.unallocated_count, v.rule_config_json, v.generation_notes
                FROM allocation_events e 
                LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
                WHERE e.id = ? AND e.academic_year_id = ?");
            $stmt->execute([$eventId, (int)$activeYear['id']]);
            $event = $stmt->fetch();

            if (!$event) {
                json_response(['success' => false, 'error' => 'Allocation event not found.'], 404);
            }

            // Fetch Participants
            $stmtPart = $db->prepare("SELECT ep.*, p.name as program_name, p.code as program_code 
                FROM allocation_event_participants ep 
                JOIN programs p ON ep.program_id = p.id 
                WHERE ep.allocation_event_id = ?");
            $stmtPart->execute([$eventId]);
            $participants = $stmtPart->fetchAll();

            // Fetch Rooms involved
            $stmtRooms = $db->prepare("SELECT r.*, er.priority_order 
                FROM allocation_event_rooms er 
                JOIN rooms r ON er.room_id = r.id 
                WHERE er.allocation_event_id = ? 
                ORDER BY er.priority_order ASC");
            $stmtRooms->execute([$eventId]);
            $eventRooms = $stmtRooms->fetchAll();

            // Fetch Versions History
            $stmtVers = $db->prepare("SELECT * FROM allocation_versions WHERE allocation_event_id = ? ORDER BY version_number DESC");
            $stmtVers->execute([$eventId]);
            $versions = $stmtVers->fetchAll();

            // Fetch Room Seating if room_id is passed
            $roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : ($eventRooms[0]['id'] ?? null);
            $roomDetails = null;
            $allocations = [];

            if ($roomId && !empty($event['version_id'])) {
                // Room info
                $stmtR = $db->prepare("SELECT * FROM rooms WHERE id = ?");
                $stmtR->execute([$roomId]);
                $roomDetails = $stmtR->fetch();

                // Furniture
                $stmtF = $db->prepare("SELECT * FROM furniture WHERE room_id = ? ORDER BY row_num ASC, col_num ASC");
                $stmtF->execute([$roomId]);
                $furniture = $stmtF->fetchAll();

                // Seats
                $stmtS = $db->prepare("SELECT * FROM seats WHERE room_id = ? ORDER BY row_num ASC, col_num ASC, seat_number_in_bench ASC");
                $stmtS->execute([$roomId]);
                $seats = $stmtS->fetchAll();

                // Allocations for this room & active version
                $stmtA = $db->prepare("SELECT * FROM allocations WHERE allocation_version_id = ? AND room_id = ?");
                $stmtA->execute([(int)$event['version_id'], $roomId]);
                $allocations = $stmtA->fetchAll();

                $roomDetails['furniture'] = $furniture;
                $roomDetails['seats'] = $seats;
            }

            json_response([
                'success' => true,
                'data' => [
                    'event' => $event,
                    'participants' => $participants,
                    'rooms' => $eventRooms,
                    'versions' => $versions,
                    'selected_room' => $roomDetails,
                    'allocations' => $allocations
                ]
            ]);
        }

        // List all events
        $stmt = $db->prepare("SELECT e.*, v.version_number, v.allocated_count, v.total_students as version_students, v.unallocated_count
            FROM allocation_events e 
            LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
            WHERE e.academic_year_id = ? 
            ORDER BY e.event_date DESC, e.id DESC");
        $stmt->execute([(int)$activeYear['id']]);
        $events = $stmt->fetchAll();

        // Also fetch cohorts and rooms for allocation wizard
        $programs = get_all_programs();
        $rooms = get_all_rooms((int)$activeYear['id']);

        // Fetch distinct program/semester cohorts currently active
        $cohortStmt = $db->prepare("SELECT s.program_id, s.semester, p.name as program_name, p.code as program_code, COUNT(s.id) as student_count
            FROM students s 
            JOIN programs p ON s.program_id = p.id 
            WHERE s.academic_year_id = ? AND s.is_deleted = 0 AND s.status = 'active'
            GROUP BY s.program_id, s.semester, p.name, p.code 
            ORDER BY p.code ASC, s.semester ASC");
        $cohortStmt->execute([(int)$activeYear['id']]);
        $availableCohorts = $cohortStmt->fetchAll();

        json_response([
            'success' => true,
            'data' => [
                'events' => $events,
                'programs' => $programs,
                'rooms' => $rooms,
                'available_cohorts' => $availableCohorts,
                'active_year' => $activeYear
            ]
        ]);
    }

    if ($method === 'POST') {
        $input = get_json_input();
        $action = $input['action'] ?? 'generate';

        if ($action === 'generate') {
            $eventName = trim((string)($input['event_name'] ?? 'Mid-Term Examination'));
            $eventDate = $input['event_date'] ?? date('Y-m-d');
            $startTime = trim((string)($input['start_time'] ?? '09:00 AM'));
            $endTime = trim((string)($input['end_time'] ?? '12:00 PM'));
            $notes = trim((string)($input['notes'] ?? ''));
            $selectedCohorts = $input['cohorts'] ?? [];
            $selectedRooms = $input['rooms'] ?? [];

            $ruleProgramSeparation = !empty($input['rule_program_separation']);
            $ruleSemesterInterleaving = !empty($input['rule_semester_interleaving']);
            $ruleRandomize = !empty($input['rule_randomize']);
            $allowRelaxation = !empty($input['allow_relaxation']);

            $existingEventId = !empty($input['event_id']) ? (int)$input['event_id'] : null;

            if (empty($selectedCohorts)) {
                json_response(['success' => false, 'error' => 'Please select at least one student cohort.'], 400);
            }
            if (empty($selectedRooms)) {
                json_response(['success' => false, 'error' => 'Please select at least one examination hall/room.'], 400);
            }

            $db->beginTransaction();

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
                    $stmtPart->execute([$eventId, (int)$ch['program_id'], (int)$ch['semester'], trim((string)($ch['subject_name'] ?? 'Core Paper'))]);
                }

                // Insert Rooms
                $stmtRoomMap = $db->prepare("INSERT INTO allocation_event_rooms (allocation_event_id, room_id, priority_order) VALUES (?, ?, ?)");
                $priority = 1;
                foreach ($selectedRooms as $rId) {
                    $stmtRoomMap->execute([$eventId, (int)$rId, $priority++]);
                }
            }

            // 2. Fetch all eligible students for selected cohorts
            $studentPool = [];
            foreach ($selectedCohorts as $ch) {
                $pId = (int)$ch['program_id'];
                $sem = (int)$ch['semester'];
                $subj = trim((string)($ch['subject_name'] ?? 'Core Paper'));

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

            // 3. Fetch all active physical seats for selected rooms
            $roomIdsList = array_map('intval', (array)$selectedRooms);
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
            $byProgram = [];
            foreach ($studentPool as $st) {
                $pCode = $st['program_code'];
                $byProgram[$pCode][] = $st;
            }

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

            // Assign students to physical seats
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
                $relaxationLevel = 3;
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

            // Insert Allocations Snapshot
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

            json_response([
                'success' => true,
                'message' => "Allocation generated successfully (Version {$nextVerNumber}): {$allocatedCount} students seated.",
                'event_id' => $eventId,
                'version_id' => $versionId,
                'allocated_count' => $allocatedCount,
                'unallocated_count' => $unallocatedCount
            ]);
        }

        if ($action === 'swap_seats') {
            $versionId = (int)($input['version_id'] ?? 0);
            $seatIdA = (int)($input['seat_id_a'] ?? 0);
            $seatIdB = (int)($input['seat_id_b'] ?? 0);

            if ($versionId <= 0 || $seatIdA <= 0 || $seatIdB <= 0 || $seatIdA === $seatIdB) {
                json_response(['success' => false, 'error' => 'Invalid seat IDs for swap.'], 400);
            }

            $db->beginTransaction();

            $stmtA = $db->prepare("SELECT * FROM allocations WHERE allocation_version_id = ? AND seat_id = ?");
            $stmtA->execute([$versionId, $seatIdA]);
            $allocA = $stmtA->fetch();

            $stmtB = $db->prepare("SELECT * FROM allocations WHERE allocation_version_id = ? AND seat_id = ?");
            $stmtB->execute([$versionId, $seatIdB]);
            $allocB = $stmtB->fetch();

            // Fetch room IDs of each seat
            $sRoomA = (int)$db->query("SELECT room_id FROM seats WHERE id = {$seatIdA}")->fetchColumn();
            $sRoomB = (int)$db->query("SELECT room_id FROM seats WHERE id = {$seatIdB}")->fetchColumn();

            if ($allocA && $allocB) {
                // Swap both
                $db->prepare("UPDATE allocations SET seat_id = ?, room_id = ? WHERE id = ?")->execute([$seatIdB, $sRoomB, $allocA['id']]);
                $db->prepare("UPDATE allocations SET seat_id = ?, room_id = ? WHERE id = ?")->execute([$seatIdA, $sRoomA, $allocB['id']]);
            } elseif ($allocA && !$allocB) {
                // Move A to empty B
                $db->prepare("UPDATE allocations SET seat_id = ?, room_id = ? WHERE id = ?")->execute([$seatIdB, $sRoomB, $allocA['id']]);
            } elseif (!$allocA && $allocB) {
                // Move B to empty A
                $db->prepare("UPDATE allocations SET seat_id = ?, room_id = ? WHERE id = ?")->execute([$seatIdA, $sRoomA, $allocB['id']]);
            }

            $db->commit();

            json_response([
                'success' => true,
                'message' => 'Seating arrangement updated successfully.'
            ]);
        }

        if ($action === 'set_active_version') {
            $eventId = (int)($input['event_id'] ?? 0);
            $versionId = (int)($input['version_id'] ?? 0);

            if ($eventId <= 0 || $versionId <= 0) {
                json_response(['success' => false, 'error' => 'Invalid event or version ID.'], 400);
            }

            $db->prepare("UPDATE allocation_events SET active_version_id = ? WHERE id = ?")->execute([$versionId, $eventId]);

            json_response([
                'success' => true,
                'message' => 'Active allocation version switched.'
            ]);
        }

        if ($action === 'delete_event') {
            $eventId = (int)($input['event_id'] ?? 0);
            if ($eventId <= 0) {
                json_response(['success' => false, 'error' => 'Invalid event ID.'], 400);
            }

            $db->beginTransaction();
            $db->prepare("DELETE FROM allocation_events WHERE id = ? AND academic_year_id = ?")->execute([$eventId, (int)$activeYear['id']]);
            $db->commit();

            json_response([
                'success' => true,
                'message' => 'Allocation event deleted successfully.'
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
