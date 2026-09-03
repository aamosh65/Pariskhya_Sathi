<?php
// Parikshya Sathi - Interactive Seating Arrangement Editor
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$eventId = (int)($_GET['id'] ?? 1);

// Fetch Event Details
$stmt = $db->prepare("SELECT e.*, v.version_number, v.id as version_id, v.allocated_count, v.total_students as version_students, v.unallocated_count
    FROM allocation_events e 
    LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
    WHERE e.id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    set_flash('danger', 'Allocation event not found.');
    header('Location: ' . base_url('pages/allocations/index.php'));
    exit;
}

$versionId = (int)($event['version_id'] ?? 0);

// Fetch Rooms used in this allocation
$stmtRooms = $db->prepare("SELECT DISTINCT r.* 
    FROM rooms r 
    JOIN seats s ON r.id = s.room_id 
    JOIN allocations a ON s.id = a.seat_id 
    WHERE a.allocation_version_id = ?
    ORDER BY r.id ASC");
$stmtRooms->execute([$versionId]);
$occupiedRooms = $stmtRooms->fetchAll();

if (empty($occupiedRooms)) {
    // If empty, fetch all event rooms
    $stmtFallback = $db->prepare("SELECT r.* FROM rooms r JOIN allocation_event_rooms er ON r.id = er.room_id WHERE er.allocation_event_id = ?");
    $stmtFallback->execute([$eventId]);
    $occupiedRooms = $stmtFallback->fetchAll();
}

$selectedRoomId = (int)($_GET['room_id'] ?? ($occupiedRooms[0]['id'] ?? 1));

// Fetch Selected Room details
$stmtSelRoom = $db->prepare("SELECT * FROM rooms WHERE id = ?");
$stmtSelRoom->execute([$selectedRoomId]);
$currentRoom = $stmtSelRoom->fetch();

// Fetch Furniture and Seats in this room
$stmtFurn = $db->prepare("SELECT * FROM furniture WHERE room_id = ? ORDER BY row_num ASC, col_num ASC");
$stmtFurn->execute([$selectedRoomId]);
$furnitureList = $stmtFurn->fetchAll();

$stmtSeats = $db->prepare("SELECT * FROM seats WHERE room_id = ? ORDER BY row_num ASC, col_num ASC, seat_number_in_bench ASC");
$stmtSeats->execute([$selectedRoomId]);
$roomSeats = $stmtSeats->fetchAll();

// Fetch Allocations for this room in active version
$stmtAlloc = $db->prepare("SELECT a.* FROM allocations a WHERE a.allocation_version_id = ? AND a.room_id = ?");
$stmtAlloc->execute([$versionId, $selectedRoomId]);
$roomAllocations = $stmtAlloc->fetchAll();

$allocBySeatId = [];
foreach ($roomAllocations as $al) {
    $allocBySeatId[(int)$al['seat_id']] = $al;
}

// Group seats by furniture_id
$seatsByFurn = [];
foreach ($roomSeats as $st) {
    $seatsByFurn[(int)$st['furniture_id']][] = $st;
}

$pageTitle = 'Edit Seating Plan';
$pageHeading = 'Interactive Seating Editor';
$pageBadge = 'v' . (int)$event['version_number'] . ' (' . (int)$event['allocated_count'] . ' Seated)';

$headerActionHtml = '
<div class="d-flex align-items-center gap-2">
    <a href="' . base_url('pages/reports/seat-plan.php?event_id=' . $eventId . '&room_id=' . $selectedRoomId) . '" class="btn-apple-secondary" target="_blank">
        <i class="bi bi-printer"></i> Print A4 Plan
    </a>
    <a href="' . base_url('pages/allocations/versions.php?event_id=' . $eventId) . '" class="btn-apple-dark">
        <i class="bi bi-clock-history"></i> Versions
    </a>
</div>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header Info & Controls Strip -->
<div class="apple-card p-3 mb-4">
    <div class="row align-items-center gy-3">
        <div class="col-12 col-md-6">
            <h3 class="display-md mb-0 fs-4"><?= htmlspecialchars($event['event_name']) ?></h3>
            <div class="caption text-muted">
                <i class="bi bi-calendar3 me-1"></i> <?= format_date($event['event_date']) ?> &bull; <?= htmlspecialchars($event['start_time']) ?> – <?= htmlspecialchars($event['end_time']) ?> &bull; <span class="fw-semibold text-primary">Version <?= (int)$event['version_number'] ?></span>
            </div>
        </div>

        <div class="col-12 col-md-6 text-md-end d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <!-- Room Tabs Switcher -->
            <div class="btn-group" role="group">
                <?php foreach ($occupiedRooms as $rm): ?>
                    <a href="?id=<?= $eventId ?>&room_id=<?= $rm['id'] ?>" class="btn btn-sm <?= $selectedRoomId === (int)$rm['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>" style="font-size: 13px;">
                        <?= htmlspecialchars($rm['room_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Instructions Banner -->
<div class="apple-card p-3 mb-4 d-flex justify-content-between align-items-center" style="background-color: var(--surface-pearl);">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-arrows-move text-primary fs-5"></i>
        <div>
            <div class="body-strong fs-6" id="swap-instructions">Click any occupied seat to begin moving or swapping students.</div>
            <div class="fine-print text-muted">Manual rearrangements are unrestricted and will not be blocked by separation rules (FR-11).</div>
        </div>
    </div>
    <span id="swap-status-badge" class="badge-apple badge-apple-warning d-none">
        <i class="bi bi-cursor-fill me-1"></i> 1st Seat Selected
    </span>
</div>

<!-- Visual Room Grid Canvas with Allocated Students -->
<div class="apple-card" data-seat-editor data-version-id="<?= $versionId ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="tagline mb-0"><?= htmlspecialchars($currentRoom['room_name'] ?? 'Room') ?> Seating Layout</h4>
            <div class="caption text-muted"><?= htmlspecialchars($currentRoom['building'] ?? '') ?>, <?= htmlspecialchars($currentRoom['floor'] ?? '') ?> &bull; <?= count($roomAllocations) ?> / <?= (int)($currentRoom['capacity'] ?? 0) ?> seats occupied</div>
        </div>

        <!-- Legend -->
        <div class="d-flex flex-wrap gap-2 fine-print">
            <span class="d-flex align-items-center gap-1"><span style="width:12px; height:12px; background:#0066cc; border-radius:3px;"></span> BCA</span>
            <span class="d-flex align-items-center gap-1"><span style="width:12px; height:12px; background:#34c759; border-radius:3px;"></span> BSc.CSIT</span>
            <span class="d-flex align-items-center gap-1"><span style="width:12px; height:12px; background:#ff9500; border-radius:3px;"></span> BBM</span>
            <span class="d-flex align-items-center gap-1"><span style="width:12px; height:12px; background:#af52de; border-radius:3px;"></span> BBA</span>
        </div>
    </div>

    <div class="room-grid-canvas">
        <div class="teacher-podium">
            <i class="bi bi-person-workspace me-1"></i> Teacher Podium / Blackboard (Front of Hall)
        </div>

        <div class="d-flex flex-column gap-3">
            <?php 
            $rowsCount = (int)($currentRoom['rows_count'] ?? 5);
            $colsCount = (int)($currentRoom['cols_count'] ?? 4);

            for ($r = 1; $r <= $rowsCount; $r++): 
            ?>
                <div class="d-flex gap-3 justify-content-center align-items-center">
                    <span class="badge bg-secondary rounded-pill" style="font-size:11px; width:55px;">Row <?= $r ?></span>
                    <div class="row g-2 flex-grow-1">
                        <?php for ($c = 1; $c <= $colsCount; $c++): 
                            $matchingFurn = null;
                            foreach ($furnitureList as $f) {
                                if ((int)$f['row_num'] === $r && (int)$f['col_num'] === $c) {
                                    $matchingFurn = $f;
                                    break;
                                }
                            }
                            $furnSeats = $matchingFurn ? ($seatsByFurn[(int)$matchingFurn['id']] ?? []) : [];
                        ?>
                            <div class="col">
                                <div class="furniture-bench p-2">
                                    <div class="fine-print fw-bold text-center mb-1 text-muted">Desk <?= $r ?>-<?= $c ?></div>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <?php if (!empty($furnSeats)): ?>
                                            <?php foreach ($furnSeats as $st): 
                                                $alloc = $allocBySeatId[(int)$st['id']] ?? null;
                                                $pCode = $alloc ? strtolower(str_replace('.', '', $alloc['program_code_snapshot'])) : '';
                                            ?>
                                                <div class="seat-slot <?= $alloc ? 'occupied occupied-' . $pCode : '' ?> p-2 flex-fill" 
                                                     data-seat-id="<?= $st['id'] ?>" 
                                                     data-student-id="<?= $alloc ? $alloc['student_id'] : '' ?>" 
                                                     data-alloc-id="<?= $alloc ? $alloc['id'] : '' ?>"
                                                     title="<?= $alloc ? htmlspecialchars($alloc['student_name_snapshot']) . ' (' . htmlspecialchars($alloc['symbol_no_snapshot']) . ')' : 'Empty Seat' ?>">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fine-print fw-bold text-muted"><?= htmlspecialchars($st['seat_identifier']) ?></span>
                                                        <?php if ($alloc): ?>
                                                            <span class="badge-apple badge-apple-primary py-0 px-1" style="font-size:10px;"><?= htmlspecialchars($alloc['program_code_snapshot']) ?></span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($alloc): ?>
                                                        <div class="fw-bold font-monospace text-dark" style="font-size:13px;"><?= htmlspecialchars($alloc['symbol_no_snapshot']) ?></div>
                                                        <div class="text-truncate fine-print text-muted" style="max-width: 100px;"><?= htmlspecialchars($alloc['student_name_snapshot']) ?></div>
                                                    <?php else: ?>
                                                        <div class="text-center text-muted fine-print py-1">— Empty —</div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="seat-slot disabled-seat p-2 text-center flex-fill">
                                                <span class="fine-print text-muted">No seat</span>
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
