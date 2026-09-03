<?php
// Parikshya Sathi - Allocation Events Catalog
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();

// Fetch allocation events
$stmt = $db->prepare("SELECT e.*, 
    v.version_number, v.allocated_count, v.total_students as version_students, v.relaxation_level,
    (SELECT COUNT(*) FROM allocation_versions WHERE allocation_event_id = e.id) as version_count,
    (SELECT COUNT(*) FROM allocation_event_participants WHERE allocation_event_id = e.id) as participant_groups_count,
    (SELECT COUNT(*) FROM allocation_event_rooms WHERE allocation_event_id = e.id) as rooms_count
    FROM allocation_events e 
    LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
    WHERE e.academic_year_id = ? 
    ORDER BY e.event_date DESC, e.id DESC");
$stmt->execute([(int)$activeYear['id']]);
$events = $stmt->fetchAll();

$pageTitle = 'Allocations';
$pageHeading = 'Examination Seating Allocations';
$pageBadge = count($events) . ' Events';

$headerActionHtml = '
<a href="' . base_url('pages/allocations/create.php') . '" class="btn-apple-primary">
    <i class="bi bi-magic me-1"></i> New Allocation Wizard
</a>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <?php if (empty($events)): ?>
        <div class="col-12">
            <div class="apple-card text-center py-5">
                <i class="bi bi-calendar2-plus fs-1 text-secondary mb-2 d-block"></i>
                <h3 class="display-md mb-2">No Allocation Events Recorded</h3>
                <p class="body-text text-muted mb-3">Create an examination event to seat students from multiple programs and faculties automatically.</p>
                <a href="<?= base_url('pages/allocations/create.php') ?>" class="btn-apple-primary">Start Allocation Wizard</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($events as $evt): ?>
            <div class="col-12 col-lg-6">
                <div class="apple-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h3 class="display-md mb-0 fs-4"><?= htmlspecialchars($evt['event_name']) ?></h3>
                                <div class="caption text-muted">
                                    <i class="bi bi-calendar3 me-1"></i> <?= format_date($evt['event_date']) ?> &bull; <?= htmlspecialchars($evt['start_time']) ?> – <?= htmlspecialchars($evt['end_time']) ?>
                                </div>
                            </div>

                            <?php if ($evt['status'] === 'active'): ?>
                                <span class="badge-apple badge-apple-success"><i class="bi bi-check-circle-fill"></i> Active Plan</span>
                            <?php elseif ($evt['status'] === 'draft'): ?>
                                <span class="badge-apple badge-apple-warning"><i class="bi bi-clock-fill"></i> Draft / Partial</span>
                            <?php else: ?>
                                <span class="badge-apple badge-apple-neutral">Archived</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($evt['notes'])): ?>
                            <p class="fine-print text-muted mb-3"><?= htmlspecialchars($evt['notes']) ?></p>
                        <?php endif; ?>

                        <!-- Stats Metrics -->
                        <div class="p-3 my-3 rounded-3" style="background-color: var(--surface-pearl);">
                            <div class="row g-2 text-center">
                                <div class="col-4 border-end border-secondary-subtle">
                                    <div class="stat-label mb-0" style="font-size:11px;">Allocated</div>
                                    <div class="body-strong text-primary"><?= (int)($evt['allocated_count'] ?? 0) ?> Students</div>
                                </div>
                                <div class="col-4 border-end border-secondary-subtle">
                                    <div class="stat-label mb-0" style="font-size:11px;">Halls Used</div>
                                    <div class="body-strong"><?= (int)$evt['rooms_count'] ?> Rooms</div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-label mb-0" style="font-size:11px;">Active Ver</div>
                                    <div class="body-strong">v<?= (int)($evt['version_number'] ?? 1) ?> (<?= (int)$evt['version_count'] ?> total)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions & Print Drops -->
                    <div class="pt-3 border-top border-secondary-subtle d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('pages/allocations/editor.php?id=' . $evt['id']) ?>" class="btn-apple-secondary btn-sm">
                                <i class="bi bi-layout-text-window-reverse me-1"></i> Visual Editor
                            </a>
                            <a href="<?= base_url('pages/allocations/versions.php?event_id=' . $evt['id']) ?>" class="btn-apple-ghost btn-sm">
                                <i class="bi bi-clock-history me-1"></i> Versions
                            </a>
                        </div>

                        <div class="dropdown">
                            <button class="btn-apple-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-printer me-1"></i> Print Reports
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/door-chart.php?event_id=' . $evt['id']) ?>"><i class="bi bi-door-closed me-2"></i> Door Chart</a></li>
                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/seat-plan.php?event_id=' . $evt['id']) ?>"><i class="bi bi-grid-3x3 me-2"></i> Room Seat Plan</a></li>
                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/attendance.php?event_id=' . $evt['id']) ?>"><i class="bi bi-card-checklist me-2"></i> Attendance Sheet</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/summary.php?event_id=' . $evt['id']) ?>"><i class="bi bi-file-text me-2"></i> Allocation Summary</a></li>
                                <li><a class="dropdown-item caption" href="<?= base_url('pages/reports/export-excel.php?event_id=' . $evt['id']) ?>"><i class="bi bi-file-earmark-excel me-2"></i> Export CSV / Excel</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
