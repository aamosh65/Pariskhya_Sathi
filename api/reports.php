<?php
// Parikshya Sathi - Reports Data API Endpoint
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

handle_cors();

$db = get_db();
$activeYear = get_active_academic_year();
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$type = trim((string)($_GET['type'] ?? 'summary'));
$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;

try {
    if ($eventId <= 0) {
        // Fallback to most recent event of active year if none specified
        $stmtLatest = $db->prepare("SELECT id FROM allocation_events WHERE academic_year_id = ? ORDER BY id DESC LIMIT 1");
        $stmtLatest->execute([(int)$activeYear['id']]);
        $eventId = (int)$stmtLatest->fetchColumn();
    }

    if ($eventId <= 0) {
        json_response(['success' => false, 'error' => 'No allocation events found for active academic year.'], 404);
    }

    // Fetch Event & Active Version
    $stmt = $db->prepare("SELECT e.*, v.version_number, v.id as version_id, v.allocated_count, v.total_students as version_students, v.unallocated_count
        FROM allocation_events e 
        LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
        WHERE e.id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        json_response(['success' => false, 'error' => 'Allocation event not found.'], 404);
    }

    $versionId = (int)($event['version_id'] ?? 0);

    // Fetch Rooms used in this allocation
    $stmtRooms = $db->prepare("SELECT DISTINCT r.* 
        FROM rooms r 
        JOIN allocations a ON r.id = a.room_id 
        WHERE a.allocation_version_id = ? 
        ORDER BY r.id ASC");
    $stmtRooms->execute([$versionId]);
    $rooms = $stmtRooms->fetchAll();

    if ($type === 'door_chart') {
        // Allocations grouped by room -> program + semester
        $stmtAlloc = $db->prepare("SELECT a.*, r.room_name, r.building, r.floor 
            FROM allocations a 
            JOIN rooms r ON a.room_id = r.id 
            WHERE a.allocation_version_id = ? 
            ORDER BY a.room_id ASC, a.program_code_snapshot ASC, a.semester_snapshot ASC, a.symbol_no_snapshot ASC");
        $stmtAlloc->execute([$versionId]);
        $allocations = $stmtAlloc->fetchAll();

        $groupedByRoom = [];
        foreach ($allocations as $a) {
            $rId = (int)$a['room_id'];
            $key = $a['program_code_snapshot'] . ' - Semester ' . $a['semester_snapshot'];
            $groupedByRoom[$rId]['room_name'] = $a['room_name'];
            $groupedByRoom[$rId]['building'] = $a['building'];
            $groupedByRoom[$rId]['floor'] = $a['floor'];
            $groupedByRoom[$rId]['cohorts'][$key][] = $a;
        }

        json_response([
            'success' => true,
            'data' => [
                'event' => $event,
                'rooms' => $rooms,
                'door_chart' => $groupedByRoom
            ]
        ]);
    }

    if ($type === 'seat_plan') {
        $targetRoomId = $roomId ?: ($rooms[0]['id'] ?? 1);

        $stmtR = $db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmtR->execute([$targetRoomId]);
        $room = $stmtR->fetch();

        $stmtFurn = $db->prepare("SELECT * FROM furniture WHERE room_id = ? ORDER BY row_num ASC, col_num ASC");
        $stmtFurn->execute([$targetRoomId]);
        $furniture = $stmtFurn->fetchAll();

        $stmtSeats = $db->prepare("SELECT * FROM seats WHERE room_id = ? ORDER BY row_num ASC, col_num ASC, seat_number_in_bench ASC");
        $stmtSeats->execute([$targetRoomId]);
        $seats = $stmtSeats->fetchAll();

        $stmtAlloc = $db->prepare("SELECT * FROM allocations WHERE allocation_version_id = ? AND room_id = ?");
        $stmtAlloc->execute([$versionId, $targetRoomId]);
        $allocations = $stmtAlloc->fetchAll();

        json_response([
            'success' => true,
            'data' => [
                'event' => $event,
                'rooms' => $rooms,
                'current_room' => $room,
                'furniture' => $furniture,
                'seats' => $seats,
                'allocations' => $allocations
            ]
        ]);
    }

    if ($type === 'attendance') {
        $targetRoomId = $roomId;
        $query = "SELECT a.*, r.room_name, r.building, r.floor, s.roll_no, s.gender 
                  FROM allocations a 
                  JOIN rooms r ON a.room_id = r.id 
                  LEFT JOIN students s ON a.student_id = s.id 
                  WHERE a.allocation_version_id = ?";
        $params = [$versionId];

        if ($targetRoomId) {
            $query .= " AND a.room_id = ?";
            $params[] = $targetRoomId;
        }

        $query .= " ORDER BY a.room_id ASC, a.program_code_snapshot ASC, a.semester_snapshot ASC, a.symbol_no_snapshot ASC";

        $stmtAtt = $db->prepare($query);
        $stmtAtt->execute($params);
        $attendees = $stmtAtt->fetchAll();

        json_response([
            'success' => true,
            'data' => [
                'event' => $event,
                'rooms' => $rooms,
                'selected_room_id' => $targetRoomId,
                'attendees' => $attendees,
                'total_count' => count($attendees)
            ]
        ]);
    }

    if ($type === 'summary') {
        // Program & Subject breakdown
        $stmtProg = $db->prepare("SELECT program_name_snapshot, program_code_snapshot, semester_snapshot, subject_name_snapshot, COUNT(*) as count 
            FROM allocations 
            WHERE allocation_version_id = ? 
            GROUP BY program_name_snapshot, program_code_snapshot, semester_snapshot, subject_name_snapshot 
            ORDER BY count DESC");
        $stmtProg->execute([$versionId]);
        $programSummary = $stmtProg->fetchAll();

        // Room utilization breakdown
        $stmtRoomUtil = $db->prepare("SELECT r.id, r.room_name, r.building, r.floor, r.capacity, COUNT(a.id) as seated_count 
            FROM rooms r 
            LEFT JOIN allocations a ON r.id = a.room_id AND a.allocation_version_id = ? 
            WHERE r.id IN (SELECT room_id FROM allocations WHERE allocation_version_id = ?) 
            GROUP BY r.id, r.room_name, r.building, r.floor, r.capacity 
            ORDER BY r.id ASC");
        $stmtRoomUtil->execute([$versionId, $versionId]);
        $roomUtilization = $stmtRoomUtil->fetchAll();

        json_response([
            'success' => true,
            'data' => [
                'event' => $event,
                'rooms' => $rooms,
                'program_summary' => $programSummary,
                'room_utilization' => $roomUtilization
            ]
        ]);
    }

    json_response(['success' => false, 'error' => 'Unknown report type.'], 400);

} catch (Throwable $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
