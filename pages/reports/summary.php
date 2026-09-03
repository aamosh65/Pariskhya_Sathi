<?php
// Parikshya Sathi - Overall Allocation Summary Report
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$eventId = (int)($_GET['event_id'] ?? 1);

// Fetch Event Details
$stmt = $db->prepare("SELECT e.*, v.version_number, v.id as version_id, v.allocated_count, v.total_students as version_students, v.unallocated_count, v.relaxation_level
    FROM allocation_events e 
    LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
    WHERE e.id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    die('Allocation event not found.');
}

$versionId = (int)($event['version_id'] ?? 0);

// Fetch Room-wise summary breakdown
$stmtRoomSummary = $db->prepare("SELECT r.id, r.room_name, r.building, r.floor, r.capacity,
    COUNT(a.id) as occupied_seats,
    (r.capacity - COUNT(a.id)) as vacant_seats,
    ROUND((CAST(COUNT(a.id) AS FLOAT) / r.capacity) * 100, 1) as utilization_pct
    FROM rooms r
    JOIN allocations a ON r.id = a.room_id AND a.allocation_version_id = ?
    GROUP BY r.id, r.room_name, r.building, r.floor, r.capacity
    ORDER BY r.id ASC");
$stmtRoomSummary->execute([$versionId]);
$roomBreakdown = $stmtRoomSummary->fetchAll();

// Fetch Program breakdown in this allocation
$stmtProgSummary = $db->prepare("SELECT a.program_code_snapshot, a.program_name_snapshot, a.semester_snapshot, a.subject_name_snapshot,
    COUNT(a.id) as student_count
    FROM allocations a
    WHERE a.allocation_version_id = ?
    GROUP BY a.program_code_snapshot, a.program_name_snapshot, a.semester_snapshot, a.subject_name_snapshot
    ORDER BY a.program_code_snapshot ASC, a.semester_snapshot ASC");
$stmtProgSummary->execute([$versionId]);
$programBreakdown = $stmtProgSummary->fetchAll();

$totalAllocated = (int)$event['allocated_count'];
$totalCapacityUsed = array_sum(array_column($roomBreakdown, 'capacity'));
$overallUtilization = $totalCapacityUsed > 0 ? round(($totalAllocated / $totalCapacityUsed) * 100, 1) : 0;

$pageTitle = 'Allocation Summary — ' . $event['event_name'];
$pageHeading = 'Allocation Summary & Utilization';
$pageBadge = $totalAllocated . ' Students Seated (' . $overallUtilization . '% Capacity)';

$headerActionHtml = '
<div class="d-flex align-items-center gap-2">
    <a href="' . base_url('pages/reports/export-excel.php?event_id=' . $eventId) . '" class="btn-apple-secondary">
        <i class="bi bi-file-earmark-excel me-1"></i> Export Spreadsheet
    </a>
    <button onclick="window.print()" class="btn-apple-primary">
        <i class="bi bi-printer me-1"></i> Print Summary
    </button>
</div>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Event Meta Card -->
<div class="apple-card mb-4">
    <div class="row align-items-center gy-3">
        <div class="col-12 col-md-8">
            <span class="badge-apple badge-apple-primary mb-1">Official Summary</span>
            <h2 class="display-lg mb-1 fs-3"><?= htmlspecialchars($event['event_name']) ?></h2>
            <div class="caption text-muted">
                <i class="bi bi-calendar3 me-1"></i> <?= format_date($event['event_date']) ?> &bull; <?= htmlspecialchars($event['start_time']) ?> – <?= htmlspecialchars($event['end_time']) ?> &bull; AY <?= htmlspecialchars($activeYear['name']) ?> &bull; Version <?= (int)$event['version_number'] ?>
            </div>
        </div>
        <div class="col-12 col-md-4 text-md-end">
            <span class="badge-apple badge-apple-success fs-6 py-2 px-3">
                <i class="bi bi-check-circle-fill me-1"></i> Active Version
            </span>
        </div>
    </div>
</div>

<!-- KPI Stats Row -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Total Allocated</div>
            <div class="stat-value text-primary"><?= $totalAllocated ?></div>
            <div class="stat-desc">Students seated successfully</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Total Capacity</div>
            <div class="stat-value"><?= $totalCapacityUsed ?></div>
            <div class="stat-desc">Across <?= count($roomBreakdown) ?> examination halls</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Overall Utilization</div>
            <div class="stat-value text-success"><?= $overallUtilization ?>%</div>
            <div class="stat-desc"><?= $totalCapacityUsed - $totalAllocated ?> buffer vacant seats</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Unallocated Students</div>
            <div class="stat-value <?= (int)$event['unallocated_count'] > 0 ? 'text-danger' : 'text-muted' ?>">
                <?= (int)$event['unallocated_count'] ?>
            </div>
            <div class="stat-desc"><?= (int)$event['unallocated_count'] > 0 ? 'Capacity deficit' : '0 Shortfall' ?></div>
        </div>
    </div>
</div>

<!-- Room-by-Room Utilization Breakdown -->
<div class="apple-card mb-4">
    <h3 class="display-md mb-2">Examination Room Breakdown</h3>
    <div class="caption mb-3">Capacity utilization and occupancy per examination hall</div>

    <div class="table-responsive">
        <table class="table-apple">
            <thead>
                <tr>
                    <th>Examination Hall</th>
                    <th>Location</th>
                    <th>Total Capacity</th>
                    <th>Occupied Seats</th>
                    <th>Vacant Seats</th>
                    <th>Utilization Rate</th>
                    <th class="text-end">Room Reports</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roomBreakdown as $rb): ?>
                    <tr>
                        <td>
                            <span class="body-strong"><?= htmlspecialchars($rb['room_name']) ?></span>
                        </td>
                        <td>
                            <span class="caption text-muted"><?= htmlspecialchars($rb['building']) ?>, <?= htmlspecialchars($rb['floor']) ?></span>
                        </td>
                        <td><?= (int)$rb['capacity'] ?> Seats</td>
                        <td>
                            <span class="body-strong text-primary"><?= (int)$rb['occupied_seats'] ?></span>
                        </td>
                        <td>
                            <span class="caption text-muted"><?= (int)$rb['vacant_seats'] ?></span>
                        </td>
                        <td style="width: 200px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px; border-radius: 9999px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $rb['utilization_pct'] ?>%; border-radius: 9999px;"></div>
                                </div>
                                <span class="caption-strong" style="font-size:12px; min-width: 40px;"><?= $rb['utilization_pct'] ?>%</span>
                            </div>
                        </td>
                        <td class="text-end">
                            <a href="<?= base_url('pages/reports/door-chart.php?event_id=' . $eventId . '&room_id=' . $rb['id']) ?>" class="btn-apple-ghost btn-sm py-0 px-2" title="Door Chart">
                                <i class="bi bi-door-closed"></i> Door
                            </a>
                            <a href="<?= base_url('pages/reports/seat-plan.php?event_id=' . $eventId . '&room_id=' . $rb['id']) ?>" class="btn-apple-ghost btn-sm py-0 px-2" title="Seat Plan">
                                <i class="bi bi-grid-3x3"></i> Plan
                            </a>
                            <a href="<?= base_url('pages/reports/attendance.php?event_id=' . $eventId . '&room_id=' . $rb['id']) ?>" class="btn-apple-ghost btn-sm py-0 px-2" title="Attendance">
                                <i class="bi bi-card-checklist"></i> Att
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Program & Subject Participation Breakdown -->
<div class="apple-card">
    <h3 class="display-md mb-2">Program & Subject Breakdown</h3>
    <div class="caption mb-3">Participating candidates grouped by faculty and paper</div>

    <div class="table-responsive">
        <table class="table-apple">
            <thead>
                <tr>
                    <th>Program Code</th>
                    <th>Program Full Name</th>
                    <th>Semester</th>
                    <th>Subject / Examination Paper</th>
                    <th class="text-end">Candidates Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programBreakdown as $pb): ?>
                    <tr>
                        <td>
                            <span class="badge-apple badge-apple-primary"><?= htmlspecialchars($pb['program_code_snapshot']) ?></span>
                        </td>
                        <td>
                            <span class="body-strong"><?= htmlspecialchars($pb['program_name_snapshot']) ?></span>
                        </td>
                        <td>
                            <span class="caption">Semester <?= (int)$pb['semester_snapshot'] ?></span>
                        </td>
                        <td>
                            <span class="caption-strong"><?= htmlspecialchars($pb['subject_name_snapshot'] ?: 'Core Paper') ?></span>
                        </td>
                        <td class="text-end">
                            <span class="body-strong text-primary fs-6"><?= (int)$pb['student_count'] ?> Students</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
