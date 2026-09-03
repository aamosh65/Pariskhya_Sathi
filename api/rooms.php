<?php
// Parikshya Sathi - Rooms & Seating Matrix API Endpoint
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

handle_cors();

$db = get_db();
$activeYear = get_active_academic_year();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $roomId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($roomId) {
            $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ? AND academic_year_id = ? AND is_deleted = 0");
            $stmt->execute([$roomId, (int)$activeYear['id']]);
            $room = $stmt->fetch();

            if (!$room) {
                json_response(['success' => false, 'error' => 'Room not found.'], 404);
            }

            // Fetch Furniture
            $stmtFurn = $db->prepare("SELECT * FROM furniture WHERE room_id = ? ORDER BY row_num ASC, col_num ASC");
            $stmtFurn->execute([$roomId]);
            $furniture = $stmtFurn->fetchAll();

            // Fetch Seats
            $stmtSeats = $db->prepare("SELECT * FROM seats WHERE room_id = ? ORDER BY row_num ASC, col_num ASC, seat_number_in_bench ASC");
            $stmtSeats->execute([$roomId]);
            $seats = $stmtSeats->fetchAll();

            json_response([
                'success' => true,
                'data' => [
                    'room' => $room,
                    'furniture' => $furniture,
                    'seats' => $seats
                ]
            ]);
        }

        // List all rooms for active academic year
        $query = "SELECT r.*, 
                    (SELECT COUNT(*) FROM seats s WHERE s.room_id = r.id AND s.status = 'active') as active_seats,
                    (SELECT COUNT(*) FROM furniture f WHERE f.room_id = r.id) as bench_count
                  FROM rooms r 
                  WHERE r.academic_year_id = ? AND r.is_deleted = 0 
                  ORDER BY r.building ASC, r.room_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([(int)$activeYear['id']]);
        $rooms = $stmt->fetchAll();

        json_response([
            'success' => true,
            'data' => [
                'rooms' => $rooms,
                'total' => count($rooms),
                'active_year' => $activeYear
            ]
        ]);
    }

    if ($method === 'POST') {
        $input = get_json_input();
        $action = $input['action'] ?? 'create';

        if ($action === 'create') {
            $roomName = trim((string)($input['room_name'] ?? ''));
            $building = trim((string)($input['building'] ?? 'Main Building'));
            $floor = trim((string)($input['floor'] ?? 'Ground Floor'));
            $rows = max(1, min(20, (int)($input['rows_count'] ?? 5)));
            $cols = max(1, min(15, (int)($input['cols_count'] ?? 4)));
            $seatsPerBench = max(1, min(5, (int)($input['default_seats_per_bench'] ?? 2)));

            if (empty($roomName)) {
                json_response(['success' => false, 'error' => 'Room name is required.'], 400);
            }

            $totalCapacity = $rows * $cols * $seatsPerBench;

            $db->beginTransaction();

            $stmtRoom = $db->prepare("INSERT INTO rooms (academic_year_id, room_name, building, floor, rows_count, cols_count, default_seats_per_bench, capacity, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmtRoom->execute([(int)$activeYear['id'], $roomName, $building, $floor, $rows, $cols, $seatsPerBench, $totalCapacity]);
            $newRoomId = (int)$db->lastInsertId();

            $stmtFurn = $db->prepare("INSERT INTO furniture (room_id, type, row_num, col_num, label, seat_count, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmtSeat = $db->prepare("INSERT INTO seats (furniture_id, room_id, seat_identifier, seat_number_in_bench, row_num, col_num, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");

            $furnType = ($seatsPerBench === 1) ? 'single_desk' : ('bench_' . $seatsPerBench);

            for ($r = 1; $r <= $rows; $r++) {
                for ($c = 1; $c <= $cols; $c++) {
                    $benchLabel = sprintf('Desk R%d-C%d', $r, $c);
                    $stmtFurn->execute([$newRoomId, $furnType, $r, $c, $benchLabel, $seatsPerBench]);
                    $furnId = (int)$db->lastInsertId();

                    for ($s = 1; $s <= $seatsPerBench; $s++) {
                        $seatIdent = sprintf('R%d-C%d-S%d', $r, $c, $s);
                        $stmtSeat->execute([$furnId, $newRoomId, $seatIdent, $s, $r, $c]);
                    }
                }
            }

            $db->commit();

            json_response([
                'success' => true,
                'message' => "Room '{$roomName}' configured with {$totalCapacity} physical seats.",
                'id' => $newRoomId
            ]);
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? 0);
            $roomName = trim((string)($input['room_name'] ?? ''));
            $building = trim((string)($input['building'] ?? 'Main Building'));
            $floor = trim((string)($input['floor'] ?? 'Ground Floor'));
            $status = trim((string)($input['status'] ?? 'active'));

            if ($id <= 0 || empty($roomName)) {
                json_response(['success' => false, 'error' => 'Room ID and Name are required.'], 400);
            }

            $stmt = $db->prepare("UPDATE rooms SET room_name = ?, building = ?, floor = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND academic_year_id = ?");
            $stmt->execute([$roomName, $building, $floor, $status, $id, (int)$activeYear['id']]);

            json_response([
                'success' => true,
                'message' => "Room '{$roomName}' updated successfully."
            ]);
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                json_response(['success' => false, 'error' => 'Invalid room ID.'], 400);
            }

            $stmt = $db->prepare("UPDATE rooms SET is_deleted = 1 WHERE id = ? AND academic_year_id = ?");
            $stmt->execute([$id, (int)$activeYear['id']]);

            json_response([
                'success' => true,
                'message' => 'Room deleted successfully.'
            ]);
        }

        if ($action === 'toggle_seat') {
            $seatId = (int)($input['seat_id'] ?? 0);
            $newStatus = trim((string)($input['status'] ?? 'active')); // 'active', 'damaged', 'disabled'

            if ($seatId <= 0) {
                json_response(['success' => false, 'error' => 'Invalid seat ID.'], 400);
            }

            $stmt = $db->prepare("UPDATE seats SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $seatId]);

            json_response([
                'success' => true,
                'message' => "Seat status changed to {$newStatus}."
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
