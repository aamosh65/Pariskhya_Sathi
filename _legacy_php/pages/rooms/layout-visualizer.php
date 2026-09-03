<?php
// Parikshya Sathi - Room Layout Visualizer
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$roomId = (int)($_GET['id'] ?? 1);

// Fetch Room details
$stmt = $db->prepare("SELECT * FROM rooms WHERE id = ? AND academic_year_id = ?");
$stmt->execute([$roomId, (int)$activeYear['id']]);
$room = $stmt->fetch();

if (!$room) {
    set_flash('danger', 'Room not found.');
    header('Location: ' . base_url('pages/rooms/index.php'));
    exit;
}

// Fetch Furniture and Seats
$stmtFurn = $db->prepare("SELECT * FROM furniture WHERE room_id = ? ORDER BY row_num ASC, col_num ASC");
$stmtFurn->execute([$roomId]);
$furnitureList = $stmtFurn->fetchAll();

$stmtSeats = $db->prepare("SELECT * FROM seats WHERE room_id = ? ORDER BY row_num ASC, col_num ASC, seat_number_in_bench ASC");
$stmtSeats->execute([$roomId]);
$seats = $stmtSeats->fetchAll();

// Group seats by furniture_id
$seatsByFurn = [];
foreach ($seats as $s) {
    $seatsByFurn[$s['furniture_id']][] = $s;
}

// All rooms for fast switcher
$allRooms = get_all_rooms((int)$activeYear['id']);

$pageTitle = $room['room_name'] . ' Layout';
$pageHeading = 'Room Layout Schematic';
$pageBadge = (int)$room['capacity'] . ' Total Seats';

$headerActionHtml = '
<div class="d-flex align-items-center gap-2">
    <a href="' . base_url('pages/rooms/edit.php?id=' . $room['id']) . '" class="btn-apple-secondary">
        <i class="bi bi-pencil-square"></i> Edit Layout
    </a>
    <a href="' . base_url('pages/rooms/index.php') . '" class="btn-apple-dark">
        All Rooms
    </a>
</div>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Room Header Card -->
<div class="apple-card p-3 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
        <h3 class="display-md mb-0"><?= htmlspecialchars($room['room_name']) ?></h3>
        <div class="caption text-muted">
            <i class="bi bi-building me-1"></i> <?= htmlspecialchars($room['building']) ?> &bull; <?= htmlspecialchars($room['floor']) ?> &bull; <?= (int)$room['rows_count'] ?> Rows &times; <?= (int)$room['cols_count'] ?> Columns (<?= (int)$room['default_seats_per_bench'] ?> seats/bench)
        </div>
    </div>

    <!-- Room Switcher -->
    <div class="d-flex align-items-center gap-2">
        <span class="fine-print fw-semibold text-muted">Switch Room:</span>
        <select class="form-select-apple form-select-sm" onchange="location.href='?id=' + this.value" style="width: auto;">
            <?php foreach ($allRooms as $rm): ?>
                <option value="<?= $rm['id'] ?>" <?= $roomId === (int)$rm['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($rm['room_name']) ?> (<?= (int)$rm['capacity'] ?> Seats)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Visual Room Floor Grid -->
<div class="apple-card">
    <div class="room-grid-canvas">
        <div class="teacher-podium">
            <i class="bi bi-person-workspace me-1"></i> Invigilator Desk / Blackboard (Front)
        </div>

        <div class="d-flex flex-column gap-3">
            <?php 
            for ($r = 1; $r <= (int)$room['rows_count']; $r++): 
            ?>
                <div class="d-flex gap-3 justify-content-center align-items-center">
                    <span class="badge bg-secondary rounded-pill" style="font-size:11px; width:55px;">Row <?= $r ?></span>
                    <div class="row g-2 flex-grow-1">
                        <?php for ($c = 1; $c <= (int)$room['cols_count']; $c++): 
                            // Find furniture matching row & col
                            $matchingFurn = null;
                            foreach ($furnitureList as $f) {
                                if ((int)$f['row_num'] === $r && (int)$f['col_num'] === $c) {
                                    $matchingFurn = $f;
                                    break;
                                }
                            }
                            $furnSeats = $matchingFurn ? ($seatsByFurn[$matchingFurn['id']] ?? []) : [];
                        ?>
                            <div class="col">
                                <div class="furniture-bench p-2">
                                    <div class="fine-print fw-bold text-center mb-1 text-muted">Desk <?= $r ?>-<?= $c ?></div>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if (!empty($furnSeats)): ?>
                                            <?php foreach ($furnSeats as $st): ?>
                                                <div class="seat-slot p-2 text-center flex-fill">
                                                    <span class="fine-print fw-bold text-primary"><?= htmlspecialchars($st['seat_identifier']) ?></span>
                                                    <span class="fine-print text-muted">Seat <?= (int)$st['seat_number_in_bench'] ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="seat-slot disabled-seat p-2 text-center flex-fill">
                                                <span class="fine-print text-muted">No seats</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
