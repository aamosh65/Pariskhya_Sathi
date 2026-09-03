<?php
// Parikshya Sathi - Invigilator Room Attendance Sheet (A4 Print Ready)
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

$pageTitle = 'Attendance Sheet — ' . $event['event_name'];
$pageHeading = 'Invigilator Attendance Sheet';
$pageBadge = 'A4 Print Ready';

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

<!-- Print Controls (Hidden in Print) -->
<div class="no-print apple-card p-3 mb-4 d-flex justify-content-between align-items-center" style="background-color: var(--surface-pearl);">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-card-checklist text-primary fs-5"></i>
        <div class="fine-print text-dark">
            <strong>Attendance Sheet:</strong> Official room-specific roster for invigilators to record student signatures and track absentees during the exam.
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if ($filterRoomId): ?>
            <a href="?event_id=<?= $eventId ?>" class="btn-apple-secondary btn-sm">Show All Rooms</a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn-apple-primary btn-sm">
            <i class="bi bi-printer"></i> Print Sheets
        </button>
    </div>
</div>

<?php foreach ($rooms as $rm): 
    $rId = (int)$rm['id'];

    // Fetch allocations for this room
    $stmtAlloc = $db->prepare("SELECT a.*, s.seat_identifier 
        FROM allocations a 
        JOIN seats s ON a.seat_id = s.id 
        WHERE a.allocation_version_id = ? AND a.room_id = ? 
        ORDER BY a.program_code_snapshot ASC, a.semester_snapshot ASC, a.symbol_no_snapshot ASC");
    $stmtAlloc->execute([$versionId, $rId]);
    $allocatedStudents = $stmtAlloc->fetchAll();
?>
    <div class="apple-card p-4 mb-5 page-break" style="border: 2px solid var(--hairline);">
        <!-- Official Institutional Header -->
        <div class="text-center mb-3 pb-2 border-bottom border-2 border-dark">
            <h3 class="h4 fw-bold text-uppercase mb-1">OFFICIAL EXAMINATION ATTENDANCE SHEET</h3>
            <div class="h6 fw-semibold mb-1"><?= htmlspecialchars($event['event_name']) ?> &bull; AY <?= htmlspecialchars($activeYear['name']) ?></div>
            <div class="small text-muted">
                <strong>Date:</strong> <?= format_date($event['event_date']) ?> &bull; 
                <strong>Time:</strong> <?= htmlspecialchars($event['start_time']) ?> &ndash; <?= htmlspecialchars($event['end_time']) ?>
            </div>
        </div>

        <!-- Room & Hall Metadata Strip -->
        <div class="row g-2 mb-3 p-2 bg-light rounded-2 border align-items-center" style="font-size: 13px;">
            <div class="col-4">
                <strong>Examination Hall:</strong> <?= htmlspecialchars($rm['room_name']) ?>
            </div>
            <div class="col-4 text-center">
                <strong>Building / Floor:</strong> <?= htmlspecialchars($rm['building']) ?>, <?= htmlspecialchars($rm['floor']) ?>
            </div>
            <div class="col-4 text-end">
                <strong>Total Allocated:</strong> <?= count($allocatedStudents) ?> Students
            </div>
        </div>

        <!-- Attendance Roster Table -->
        <div class="table-responsive">
            <table class="table-apple" style="border: 1px solid #000; font-size: 12px;">
                <thead>
                    <tr style="border-bottom: 2px solid #000; background: #f0f0f0;">
                        <th style="width: 40px; text-align: center;">S.N.</th>
                        <th style="width: 100px;">Symbol No</th>
                        <th style="width: 80px;">Seat Code</th>
                        <th>Student Full Name</th>
                        <th style="width: 110px;">Program & Sem</th>
                        <th>Subject / Paper</th>
                        <th style="width: 140px; text-align: center;">Student Signature</th>
                        <th style="width: 100px; text-align: center;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sn = 1;
                    foreach ($allocatedStudents as $st): 
                    ?>
                        <tr style="height: 38px;">
                            <td style="text-align: center;"><?= $sn++ ?></td>
                            <td class="font-monospace fw-bold"><?= htmlspecialchars($st['symbol_no_snapshot']) ?></td>
                            <td class="font-monospace text-muted"><?= htmlspecialchars($st['seat_identifier']) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($st['student_name_snapshot']) ?></td>
                            <td><?= htmlspecialchars($st['program_code_snapshot']) ?> - Sem <?= (int)$st['semester_snapshot'] ?></td>
                            <td class="text-truncate" style="max-width: 160px;"><?= htmlspecialchars($st['subject_name_snapshot'] ?: 'Core Paper') ?></td>
                            <td style="border-bottom: 1px solid #aaa;"></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Invigilator Verification Box at Bottom -->
        <div class="row g-3 mt-4 pt-3 border-top border-dark" style="font-size: 12px;">
            <div class="col-4">
                <div><strong>Present Students:</strong> _______</div>
                <div><strong>Absent Students:</strong> _______</div>
                <div><strong>Total Candidates:</strong> <?= count($allocatedStudents) ?></div>
            </div>
            <div class="col-4 text-center">
                <div class="mb-4">Invigilator 1 Signature: __________________</div>
                <div>Name: _______________________________</div>
            </div>
            <div class="col-4 text-end">
                <div class="mb-4">Invigilator 2 Signature: __________________</div>
                <div>Name: _______________________________</div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
