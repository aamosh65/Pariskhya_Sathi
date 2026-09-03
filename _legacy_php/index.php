<?php
// Parikshya Sathi - Executive Dashboard
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$activeYear = get_active_academic_year();
$stats = get_dashboard_stats((int)$activeYear['id']);

$pageTitle = 'Dashboard';
$pageHeading = 'Overview';
$pageBadge = 'Academic Year ' . $activeYear['name'];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner (Apple Dark Tile Aesthetic) -->
<div class="apple-card-dark mb-4">
    <div class="row align-items-center gy-3">
        <div class="col-lg-8">
            <span class="badge-apple badge-apple-primary text-white bg-primary mb-2">Automated Seating System</span>
            <h2 class="display-lg text-white mb-2">Welcome to Parikshya Sathi</h2>
            <p class="body-text mb-0" style="color: rgba(255,255,255,0.85);">
                Configure rooms, manage student cohorts, run intelligent randomized allocations with configurable separation rules, and generate print-ready institutional documentation.
            </p>
        </div>
        <div class="col-lg-4 text-lg-end d-flex flex-wrap gap-2 justify-content-lg-end">
            <a href="<?= base_url('pages/allocations/create.php') ?>" class="btn-apple-primary">
                <i class="bi bi-magic me-1"></i> New Allocation
            </a>
            <a href="<?= base_url('pages/students/import.php') ?>" class="btn-apple-secondary">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Students
            </a>
        </div>
    </div>
</div>

<!-- 1. Key Metrics Cards Row -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label"><i class="bi bi-people me-1"></i> Total Students</div>
            <div class="stat-value"><?= number_format($stats['total_students']) ?></div>
            <div class="stat-desc">Enrolled in AY <?= htmlspecialchars($activeYear['name']) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label"><i class="bi bi-door-open me-1"></i> Active Rooms</div>
            <div class="stat-value"><?= number_format($stats['total_rooms']) ?></div>
            <div class="stat-desc">Configured exam halls</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label"><i class="bi bi-grid-3x3-gap me-1"></i> Physical Seats</div>
            <div class="stat-value"><?= number_format($stats['total_seats']) ?></div>
            <div class="stat-desc">Total capacity across rooms</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label"><i class="bi bi-calendar-event me-1"></i> Allocation Events</div>
            <div class="stat-value"><?= number_format($stats['total_events']) ?></div>
            <div class="stat-desc">Recorded examination sessions</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 2. Recent Allocation Events & Live Operations -->
    <div class="col-12 col-lg-8">
        <div class="apple-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="display-md mb-0">Examination Allocations</h3>
                    <div class="caption">Active and draft seating plans</div>
                </div>
                <a href="<?= base_url('pages/allocations/index.php') ?>" class="btn-apple-ghost">View All <i class="bi bi-chevron-right"></i></a>
            </div>

            <?php if (empty($stats['recent_events'])): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-calendar-x fs-1 mb-2 d-block text-secondary"></i>
                    <p class="body-text mb-2">No examination allocation events created yet.</p>
                    <a href="<?= base_url('pages/allocations/create.php') ?>" class="btn-apple-primary btn-sm">Create First Allocation</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-apple">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Exam Date</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_events'] as $evt): ?>
                                <tr>
                                    <td>
                                        <div class="body-strong"><?= htmlspecialchars($evt['event_name']) ?></div>
                                        <div class="fine-print text-muted"><?= htmlspecialchars($evt['start_time']) ?> – <?= htmlspecialchars($evt['end_time']) ?></div>
                                    </td>
                                    <td>
                                        <span class="caption"><?= format_date($evt['event_date']) ?></span>
                                    </td>
                                    <td>
                                        <div class="caption-strong"><?= (int)($evt['allocated_count'] ?? 0) ?> Students</div>
                                        <div class="fine-print text-muted">Ver <?= (int)($evt['version_number'] ?? 1) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($evt['status'] === 'active'): ?>
                                            <span class="badge-apple badge-apple-success"><i class="bi bi-check-circle-fill"></i> Active</span>
                                        <?php elseif ($evt['status'] === 'draft'): ?>
                                            <span class="badge-apple badge-apple-warning"><i class="bi bi-clock-fill"></i> Draft</span>
                                        <?php else: ?>
                                            <span class="badge-apple badge-apple-neutral">Archived</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown d-inline-block">
                                            <a href="<?= base_url('pages/allocations/editor.php?id=' . $evt['id']) ?>" class="btn-apple-secondary btn-sm py-1 px-3 me-1">
                                                <i class="bi bi-layout-text-window-reverse"></i> Edit Plan
                                            </a>
                                            <button class="btn-apple-dark btn-sm py-1 px-2 dropdown-toggle" type="button" data-bs-dismiss="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/door-chart.php?event_id=' . $evt['id']) ?>"><i class="bi bi-door-closed me-2"></i> Door Chart</a></li>
                                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/seat-plan.php?event_id=' . $evt['id']) ?>"><i class="bi bi-grid-3x3 me-2"></i> Room Seat Plan</a></li>
                                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/attendance.php?event_id=' . $evt['id']) ?>"><i class="bi bi-card-checklist me-2"></i> Attendance Sheet</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/summary.php?event_id=' . $evt['id']) ?>"><i class="bi bi-file-text me-2"></i> Allocation Summary</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Room Capacity Breakdown -->
        <div class="apple-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="display-md mb-0">Exam Halls Capacity</h3>
                    <div class="caption">Physical room inventory & seating capabilities</div>
                </div>
                <a href="<?= base_url('pages/rooms/create.php') ?>" class="btn-apple-secondary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Add Hall
                </a>
            </div>

            <div class="row g-3">
                <?php foreach ($stats['room_stats'] as $rm): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="furniture-bench p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="body-strong"><?= htmlspecialchars($rm['room_name']) ?></div>
                                <span class="badge-apple badge-apple-primary"><?= (int)$rm['capacity'] ?> Seats</span>
                            </div>
                            <div class="caption text-muted mb-2">
                                <i class="bi bi-building me-1"></i> <?= htmlspecialchars($rm['building']) ?>, <?= htmlspecialchars($rm['floor']) ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary-subtle">
                                <span class="fine-print text-muted"><?= (int)$rm['active_seats'] ?> active physical slots</span>
                                <a href="<?= base_url('pages/rooms/edit.php?id=' . $rm['id']) ?>" class="btn-apple-ghost btn-sm py-0 px-2" style="font-size:12px;">Edit Layout</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 3. Sidebar / Right Column (Program Distribution & Quick Tools) -->
    <div class="col-12 col-lg-4">
        <!-- Program Cohorts -->
        <div class="apple-card">
            <h3 class="display-md mb-2">Programs & Cohorts</h3>
            <div class="caption mb-3">Student distribution by academic faculty</div>

            <div class="d-flex flex-column gap-3">
                <?php 
                $maxStudents = max(array_column($stats['program_stats'], 'student_count') ?: [1]);
                foreach ($stats['program_stats'] as $prog): 
                    $percent = $maxStudents > 0 ? round(($prog['student_count'] / $maxStudents) * 100) : 0;
                ?>
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="caption-strong"><?= htmlspecialchars($prog['code']) ?> <span class="text-muted fw-normal">(<?= htmlspecialchars($prog['name']) ?>)</span></div>
                            <span class="badge-apple badge-apple-neutral"><?= (int)$prog['student_count'] ?></span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 9999px; background-color: var(--divider-soft);">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percent ?>%; border-radius: 9999px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 pt-3 border-top border-secondary-subtle d-flex justify-content-between">
                <a href="<?= base_url('pages/students/index.php') ?>" class="text-primary text-decoration-none caption fw-semibold">Manage Students &rarr;</a>
                <a href="<?= base_url('pages/students/symbols.php') ?>" class="text-primary text-decoration-none caption fw-semibold">Symbol Numbers &rarr;</a>
            </div>
        </div>

        <!-- Fast Actions Card -->
        <div class="apple-card" style="background-color: var(--surface-pearl);">
            <div class="caption-strong text-uppercase text-muted mb-2">Fast Actions</div>
            <div class="d-flex flex-column gap-2">
                <a href="<?= base_url('pages/students/import.php') ?>" class="apple-card p-3 mb-0 d-flex align-items-center justify-content-between text-decoration-none text-dark">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>
                        <div>
                            <div class="body-strong fs-6">Bulk CSV Import</div>
                            <div class="fine-print text-muted">Upload student lists with validation</div>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?= base_url('pages/allocations/create.php') ?>" class="apple-card p-3 mb-0 d-flex align-items-center justify-content-between text-decoration-none text-dark">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="bi bi-cpu"></i>
                        </div>
                        <div>
                            <div class="body-strong fs-6">Run Allocation Engine</div>
                            <div class="fine-print text-muted">Intelligent randomized seat assignment</div>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?= base_url('pages/reports/summary.php?event_id=1') ?>" class="apple-card p-3 mb-0 d-flex align-items-center justify-content-between text-decoration-none text-dark">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                            <i class="bi bi-printer"></i>
                        </div>
                        <div>
                            <div class="body-strong fs-6">Print Center</div>
                            <div class="fine-print text-muted">A4 Door charts, seat plans & attendance</div>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
