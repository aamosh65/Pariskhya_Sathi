<?php
// Parikshya Sathi - Room-Wise Visual Seat Plan Report (A4 Print Ready)
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$eventId = (int)($_GET['event_id'] ?? 1);
$filterRoomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;

// Fetch Event Details
$stmt = $db->prepare("SELECT e.*, v.version_number, v.id as version_id 
    FROM allocation_events e 
    LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
    WHERE e.id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    die('Allocation event not found.');
}

$versionId = (int)($event['version_id'] ?? 0);

// Fetch Rooms
$roomQuery = "SELECT DISTINCT r.* FROM rooms r JOIN allocations a ON r.id = a.room_id WHERE a.allocation_version_id = ?";
$params = [$versionId];
if ($filterRoomId) {
    $roomQuery .= " AND r.id = ?";
    $params[] = $filterRoomId;
}
$roomQuery .= " ORDER BY r.id ASC";

$stmtRooms = $db->prepare($roomQuery);
$stmtRooms->execute($params);
$rooms = $stmtRooms->fetchAll();

$pageTitle = 'Seat Plan — ' . $event['event_name'];
$pageHeading = 'Visual Room Seat Plan';
$pageBadge = count($rooms) . ' Halls';

$headerActionHtml = '
<div class="d-flex align-items-center gap-2">
    <button onclick="window.print()" class="btn-apple-primary">
        <i class="bi bi-printer me-1"></i> Print / Save PDF
    </button>
    <a href="' . base_url('pages/allocations/editor.php?id=' . $eventId) . '" class="btn-apple-secondary">
        Back to Editor
    </a>
</div>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Print Controls Strip (Hidden in Print) -->
<div class="no-print apple-card p-3 mb-4 d-flex justify-content-between align-items-center" style="background-color: var(--surface-pearl);">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-grid-3x3 text-primary fs-5"></i>
        <div class="fine-print text-dark">
            <strong>Room Seat Plan:</strong> Visual schematic showing precise desk-by-desk seating arrangements for invigilators and room supervisors.
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if ($filterRoomId): ?>
            <a href="?event_id=<?= $eventId ?>" class="btn-apple-secondary btn-sm">Show All Rooms</a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn-apple-primary btn-sm">
            <i class="bi bi-printer"></i> Print Plan
        </button>
    </div>
</div>

<?php foreach ($rooms as $rm): 
    $rId = (int)$rm['id'];

    // Fetch furniture & seats
    $stmtFurn = $db->prepare("SELECT * FROM furniture WHERE room_id = ? ORDER BY row_num ASC, col_num ASC");
    $stmtFurn->execute([$rId]);
    $furnitureList = $stmtFurn->fetchAll();

    $stmtSeats = $db->prepare("SELECT * FROM seats WHERE room_id = ? ORDER BY row_num ASC, col_num ASC, seat_number_in_bench ASC");
    $stmtSeats->execute([$rId]);
    $seats = $stmtSeats->fetchAll();

    $stmtAlloc = $db->prepare("SELECT * FROM allocations WHERE allocation_version_id = ? AND room_id = ?");
    $stmtAlloc->execute([$versionId, $rId]);
    $allocList = $stmtAlloc->fetchAll();

    $allocMap = [];
    foreach ($allocList as $al) {
        $allocMap[(int)$al['seat_id']] = $al;
    }

    $seatsByFurn = [];
    foreach ($seats as $s) {
        $seatsByFurn[(int)$s['furniture_id']][] = $s;
    }
?>
    <div class="apple-card p-4 mb-5 page-break" style="border: 2px solid var(--hairline);">
        <!-- Institution / Event Header -->
        <div class="text-center mb-3 pb-2 border-bottom border-dark">
            <h3 class="h4 fw-bold text-uppercase mb-1">Examination Seat Plan</h3>
            <div class="h6 mb-1"><?= htmlspecialchars($event['event_name']) ?> &bull; AY <?= htmlspecialchars($activeYear['name']) ?></div>
            <div class="small text-muted"><?= format_date($event['event_date']) ?> (<?= htmlspecialchars($event['start_time']) ?> &ndash; <?= htmlspecialchars($event['end_time']) ?>)</div>
        </div>

        <!-- Room Meta Info -->
        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-2 border">
            <div>
                <span class="body-strong fs-5"><?= htmlspecialchars($rm['room_name']) ?></span>
                <span class="caption text-muted ms-2">(<?= htmlspecialchars($rm['building']) ?>, <?= htmlspecialchars($rm['floor']) ?>)</span>
            </div>
            <div class="body-strong text-primary">
                <?= count($allocList) ?> / <?= (int)$rm['capacity'] ?> Seats Occupied
            </div>
        </div>

        <!-- Room Visual Schematic Grid -->
        <div class="room-grid-canvas my-3">
            <div class="teacher-podium">
                <i class="bi bi-person-workspace me-1"></i> Invigilator Desk / Blackboard (Front of Room)
            </div>

            <div class="d-flex flex-column gap-3">
                <?php for ($r = 1; $r <= (int)$rm['rows_count']; $r++): ?>
                    <div class="d-flex gap-2 justify-content-center align-items-center">
                        <span class="badge bg-secondary" style="font-size:10px; width:45px;">Row <?= $r ?></span>
                        <div class="row g-2 flex-grow-1">
                            <?php for ($c = 1; $c <= (int)$rm['cols_count']; $c++): 
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
                                    <div class="furniture-bench p-1" style="background:#ffffff; border:1px solid #999;">
                                        <div class="fine-print text-center text-muted" style="font-size:10px;">Desk <?= $r ?>-<?= $c ?></div>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <?php if (!empty($furnSeats)): ?>
                                                <?php foreach ($furnSeats as $st): 
                                                    $alloc = $allocMap[(int)$st['id']] ?? null;
                                                ?>
                                                    <div class="seat-slot p-1 text-center flex-fill" style="min-height: 48px; border:1px solid #ccc; font-size:11px;">
                                                        <div class="fw-bold font-monospace text-primary" style="font-size:12px;">
                                                            <?= $alloc ? htmlspecialchars($alloc['symbol_no_snapshot']) : '<span class="text-muted">—</span>' ?>
                                                        </div>
                                                        <div class="text-truncate fine-print text-dark" style="font-size:10px; max-width:85px;">
                                                            <?= $alloc ? htmlspecialchars($alloc['student_name_snapshot']) : 'Vacant' ?>
                                                        </div>
                                                        <?php if ($alloc): ?>
                                                            <div class="badge-apple badge-apple-neutral py-0 px-1" style="font-size:9px;">
                                                                <?= htmlspecialchars($alloc['program_code_snapshot']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="seat-slot disabled-seat p-1 text-center flex-fill" style="font-size:10px;">—</div>
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

        <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top fine-print text-muted">
            <div>Parikshya Sathi Generated &bull; Ver <?= (int)$event['version_number'] ?></div>
            <div>Authorized Signature: _______________________</div>
        </div>
    </div>
<?php endforeach; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
