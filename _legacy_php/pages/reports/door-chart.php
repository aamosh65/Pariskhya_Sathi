<?php
// Parikshya Sathi - Examination Door Chart (A4 Print Ready)
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$eventId = (int)($_GET['event_id'] ?? 1);

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

// Fetch all rooms with allocated students
$stmtRooms = $db->prepare("SELECT DISTINCT r.* 
    FROM rooms r 
    JOIN allocations a ON r.id = a.room_id 
    WHERE a.allocation_version_id = ? 
    ORDER BY r.id ASC");
$stmtRooms->execute([$versionId]);
$rooms = $stmtRooms->fetchAll();

// Fetch allocations grouped by room, program, semester
$stmtAlloc = $db->prepare("SELECT a.*, r.room_name 
    FROM allocations a 
    JOIN rooms r ON a.room_id = r.id 
    WHERE a.allocation_version_id = ? 
    ORDER BY a.room_id ASC, a.program_code_snapshot ASC, a.semester_snapshot ASC, a.symbol_no_snapshot ASC");
$stmtAlloc->execute([$versionId]);
$allocations = $stmtAlloc->fetchAll();

// Group allocations by Room ID -> Program Code + Sem
$groupedData = [];
foreach ($allocations as $al) {
    $rId = (int)$al['room_id'];
    $progSemKey = $al['program_code_snapshot'] . ' - Sem ' . $al['semester_snapshot'];
    $groupedData[$rId][$progSemKey][] = $al;
}

$pageTitle = 'Door Chart — ' . $event['event_name'];
$pageHeading = 'Examination Door Chart';
$pageBadge = 'A4 Print Ready';

$headerActionHtml = '
<div class="d-flex align-items-center gap-2">
    <button onclick="window.print()" class="btn-apple-primary">
        <i class="bi bi-printer me-1"></i> Print / Save as PDF
    </button>
    <a href="' . base_url('pages/allocations/editor.php?id=' . $eventId) . '" class="btn-apple-secondary">
        Back to Editor
    </a>
</div>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Print-only Institution Header -->
<div class="print-only d-none text-center mb-4 pb-3 border-bottom border-dark">
    <h2 class="h3 fw-bold text-uppercase mb-1">Tribhuvan University / Affiliated Academic Institution</h2>
    <h4 class="h5 fw-semibold mb-1">OFFICE OF THE EXAMINATION CONTROLLER</h4>
    <div class="small fw-medium"><?= htmlspecialchars($event['event_name']) ?> &bull; AY <?= htmlspecialchars($activeYear['name']) ?></div>
</div>

<div class="no-print apple-card p-3 mb-4 d-flex justify-content-between align-items-center" style="background-color: var(--surface-pearl);">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-info-circle text-primary fs-5"></i>
        <div class="fine-print text-dark">
            <strong>Door Chart:</strong> Standardized document designed to be printed on A4 paper and posted outside examination room doors for students to identify their assigned halls by symbol numbers.
        </div>
    </div>
    <button onclick="window.print()" class="btn-apple-primary btn-sm">
        <i class="bi bi-printer"></i> Print Now
    </button>
</div>

<!-- Door Charts (One Block per Room) -->
<?php foreach ($rooms as $index => $rm): 
    $rId = (int)$rm['id'];
    $cohorts = $groupedData[$rId] ?? [];
    $totalRoomAllocated = 0;
    foreach ($cohorts as $cList) {
        $totalRoomAllocated += count($cList);
    }
?>
    <div class="apple-card p-4 mb-4 page-break" style="border: 2px solid var(--hairline);">
        <!-- Room Header -->
        <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom border-2 border-dark">
            <div>
                <span class="badge-apple badge-apple-primary mb-1">EXAMINATION HALL</span>
                <h2 class="display-lg mb-0 text-dark fw-bold"><?= htmlspecialchars($rm['room_name']) ?></h2>
                <div class="caption text-muted"><?= htmlspecialchars($rm['building']) ?> &bull; <?= htmlspecialchars($rm['floor']) ?></div>
            </div>
            <div class="text-end">
                <div class="body-strong fs-4 text-primary"><?= $totalRoomAllocated ?> Students</div>
                <div class="fine-print text-muted"><?= format_date($event['event_date']) ?> (<?= htmlspecialchars($event['start_time']) ?> - <?= htmlspecialchars($event['end_time']) ?>)</div>
            </div>
        </div>

        <!-- Symbol Number Ranges Grid -->
        <div class="row g-3 mb-4">
            <?php foreach ($cohorts as $cName => $studentsInCohort): 
                $firstSymbol = $studentsInCohort[0]['symbol_no_snapshot'];
                $lastSymbol = $studentsInCohort[count($studentsInCohort) - 1]['symbol_no_snapshot'];
                $subjectName = $studentsInCohort[0]['subject_name_snapshot'] ?? 'Core Paper';
            ?>
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded-3" style="background-color: var(--surface-pearl); border: 1px solid var(--hairline);">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="body-strong text-dark"><?= htmlspecialchars($cName) ?></span>
                            <span class="badge-apple badge-apple-neutral fw-bold"><?= count($studentsInCohort) ?> Students</span>
                        </div>
                        <div class="fine-print text-muted mb-2"><i class="bi bi-journal-text me-1"></i> <?= htmlspecialchars($subjectName) ?></div>

                        <div class="p-2 bg-white rounded-2 border text-center">
                            <div class="fine-print text-muted text-uppercase mb-1">Assigned Symbol Range</div>
                            <div class="fs-5 font-monospace fw-bold text-primary">
                                <?= htmlspecialchars($firstSymbol) ?> &mdash; <?= htmlspecialchars($lastSymbol) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Detailed Symbol Table for Room -->
        <h4 class="tagline mb-2">Student Symbol Roll for this Room</h4>
        <div class="table-responsive">
            <table class="table-apple" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th style="width: 50px;">S.N.</th>
                        <th>Symbol Number</th>
                        <th>Student Name</th>
                        <th>Program</th>
                        <th>Semester</th>
                        <th>Subject</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sn = 1;
                    foreach ($cohorts as $cName => $studentsInCohort): 
                        foreach ($studentsInCohort as $st):
                    ?>
                        <tr>
                            <td><?= $sn++ ?></td>
                            <td class="font-monospace fw-bold"><?= htmlspecialchars($st['symbol_no_snapshot']) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($st['student_name_snapshot']) ?></td>
                            <td><?= htmlspecialchars($st['program_code_snapshot']) ?></td>
                            <td>Sem <?= (int)$st['semester_snapshot'] ?></td>
                            <td class="fine-print text-muted"><?= htmlspecialchars($st['subject_name_snapshot'] ?: '—') ?></td>
                        </tr>
                    <?php 
                        endforeach;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top fine-print text-muted">
            <div>Parikshya Sathi Generated &bull; Ver <?= (int)$event['version_number'] ?></div>
            <div>Printed: <?= date('Y-m-d H:i') ?></div>
        </div>
    </div>
<?php endforeach; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
