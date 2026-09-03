<?php
// Parikshya Sathi - Examination Rooms & Layout Inventory
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();

// Fetch rooms with live seat counts
$stmt = $db->prepare("SELECT r.*, 
    (SELECT COUNT(*) FROM seats WHERE room_id = r.id AND status = 'active') as active_seats_count,
    (SELECT COUNT(*) FROM furniture WHERE room_id = r.id) as benches_count 
    FROM rooms r 
    WHERE r.academic_year_id = ? AND r.is_deleted = 0 
    ORDER BY r.building ASC, r.room_name ASC");
$stmt->execute([(int)$activeYear['id']]);
$rooms = $stmt->fetchAll();

$totalCapacity = array_sum(array_column($rooms, 'capacity'));

$pageTitle = 'Rooms & Layouts';
$pageHeading = 'Room & Layout Management';
$pageBadge = count($rooms) . ' Rooms (' . $totalCapacity . ' Total Seats)';

$headerActionHtml = '
<a href="' . base_url('pages/rooms/create.php') . '" class="btn-apple-primary">
    <i class="bi bi-plus-lg"></i> Add Examination Room
</a>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <?php if (empty($rooms)): ?>
        <div class="col-12">
            <div class="apple-card text-center py-5">
                <i class="bi bi-door-closed fs-1 text-secondary mb-2 d-block"></i>
                <h3 class="display-md mb-2">No Examination Rooms Configured</h3>
                <p class="body-text text-muted mb-3">Add examination halls and configure their desk/bench layouts to enable seating allocation.</p>
                <a href="<?= base_url('pages/rooms/create.php') ?>" class="btn-apple-primary">Add First Room</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($rooms as $rm): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="apple-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h3 class="display-md mb-0 fs-4"><?= htmlspecialchars($rm['room_name']) ?></h3>
                                <div class="caption text-muted">
                                    <i class="bi bi-building me-1"></i> <?= htmlspecialchars($rm['building']) ?> &bull; <?= htmlspecialchars($rm['floor']) ?>
                                </div>
                            </div>
                            <span class="badge-apple badge-apple-primary fs-6">
                                <?= (int)$rm['capacity'] ?> Seats
                            </span>
                        </div>

                        <!-- Room Layout Matrix Specs -->
                        <div class="p-3 my-3 rounded-3" style="background-color: var(--surface-pearl);">
                            <div class="row g-2 text-center">
                                <div class="col-4 border-end border-secondary-subtle">
                                    <div class="stat-label mb-0" style="font-size:11px;">Rows</div>
                                    <div class="body-strong"><?= (int)$rm['rows_count'] ?></div>
                                </div>
                                <div class="col-4 border-end border-secondary-subtle">
                                    <div class="stat-label mb-0" style="font-size:11px;">Columns</div>
                                    <div class="body-strong"><?= (int)$rm['cols_count'] ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-label mb-0" style="font-size:11px;">Per Bench</div>
                                    <div class="body-strong"><?= (int)$rm['default_seats_per_bench'] ?> Seats</div>
                                </div>
                            </div>
                        </div>

                        <div class="fine-print text-muted">
                            <i class="bi bi-grid-3x3 me-1"></i> Total <?= (int)$rm['benches_count'] ?> Benches &bull; <?= (int)$rm['active_seats_count'] ?> Active Usable Seats
                        </div>
                    </div>

                    <div class="pt-3 mt-3 border-top border-secondary-subtle d-flex justify-content-between align-items-center">
                        <a href="<?= base_url('pages/rooms/layout-visualizer.php?id=' . $rm['id']) ?>" class="btn-apple-ghost btn-sm">
                            <i class="bi bi-eye me-1"></i> View Layout
                        </a>
                        <a href="<?= base_url('pages/rooms/edit.php?id=' . $rm['id']) ?>" class="btn-apple-secondary btn-sm">
                            <i class="bi bi-gear me-1"></i> Configure
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
