<?php
// Parikshya Sathi - Allocation Version History Manager
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$eventId = (int)($_GET['event_id'] ?? 1);

// Fetch Event Details
$stmt = $db->prepare("SELECT * FROM allocation_events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    set_flash('danger', 'Allocation event not found.');
    header('Location: ' . base_url('pages/allocations/index.php'));
    exit;
}

// Handle Set Active Version
if (isset($_GET['set_active'])) {
    $verId = (int)$_GET['set_active'];
    $db->prepare("UPDATE allocation_events SET active_version_id = ? WHERE id = ?")->execute([$verId, $eventId]);
    $db->prepare("UPDATE allocation_versions SET status = 'active' WHERE id = ?")->execute([$verId]);
    set_flash('success', "Version promoted to Active. Reports will now reflect this version.");
    header('Location: ' . base_url('pages/allocations/versions.php?event_id=' . $eventId));
    exit;
}

// Fetch all versions for this event
$stmtVer = $db->prepare("SELECT v.*, 
    (SELECT COUNT(DISTINCT room_id) FROM allocations WHERE allocation_version_id = v.id) as rooms_count
    FROM allocation_versions v 
    WHERE v.allocation_event_id = ? 
    ORDER BY v.version_number DESC");
$stmtVer->execute([$eventId]);
$versions = $stmtVer->fetchAll();

$pageTitle = 'Allocation Versions';
$pageHeading = 'Version History: ' . $event['event_name'];
$pageBadge = count($versions) . ' Versions Preserved';

$headerActionHtml = '
<div class="d-flex align-items-center gap-2">
    <a href="' . base_url('pages/allocations/editor.php?id=' . $eventId) . '" class="btn-apple-secondary">
        <i class="bi bi-layout-text-window-reverse"></i> Visual Editor
    </a>
    <a href="' . base_url('pages/allocations/index.php') . '" class="btn-apple-dark">
        All Events
    </a>
</div>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="apple-card p-0 overflow-hidden">
    <div class="p-3 d-flex justify-content-between align-items-center border-bottom border-secondary-subtle">
        <div>
            <h3 class="display-md mb-0 fs-5"><?= htmlspecialchars($event['event_name']) ?></h3>
            <div class="caption text-muted"><?= format_date($event['event_date']) ?> &bull; <?= htmlspecialchars($event['start_time']) ?> – <?= htmlspecialchars($event['end_time']) ?></div>
        </div>
        <a href="<?= base_url('pages/allocations/create.php') ?>" class="btn-apple-primary btn-sm">
            <i class="bi bi-magic me-1"></i> Generate New Version
        </a>
    </div>

    <div class="table-responsive">
        <table class="table-apple">
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Students Allocated</th>
                    <th>Relaxation Level</th>
                    <th>Rooms Occupied</th>
                    <th>Generated Timestamp</th>
                    <th>Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($versions as $v): ?>
                    <?php $isActiveVer = ((int)$event['active_version_id'] === (int)$v['id']); ?>
                    <tr class="<?= $isActiveVer ? 'table-light' : '' ?>">
                        <td>
                            <span class="body-strong fs-6">v<?= (int)$v['version_number'] ?></span>
                            <?php if ($isActiveVer): ?>
                                <span class="badge-apple badge-apple-primary ms-2"><i class="bi bi-check-circle-fill"></i> Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($v['status'] === 'active' || $isActiveVer): ?>
                                <span class="badge-apple badge-apple-success">Active</span>
                            <?php elseif ($v['status'] === 'draft'): ?>
                                <span class="badge-apple badge-apple-warning">Draft</span>
                            <?php else: ?>
                                <span class="badge-apple badge-apple-neutral">Archived</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="body-strong text-primary"><?= (int)$v['allocated_count'] ?></span> / <?= (int)$v['total_students'] ?>
                            <?php if ((int)$v['unallocated_count'] > 0): ?>
                                <span class="badge-apple badge-apple-danger py-0 px-1 ms-1"><?= (int)$v['unallocated_count'] ?> unallocated</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-apple badge-apple-neutral">Level <?= (int)$v['relaxation_level'] ?></span>
                        </td>
                        <td>
                            <span class="caption"><?= (int)$v['rooms_count'] ?> Halls</span>
                        </td>
                        <td>
                            <span class="fine-print text-muted"><?= htmlspecialchars($v['created_at']) ?></span>
                        </td>
                        <td>
                            <span class="fine-print text-muted"><?= htmlspecialchars($v['generation_notes'] ?: '—') ?></span>
                        </td>
                        <td class="text-end">
                            <?php if (!$isActiveVer): ?>
                                <a href="?event_id=<?= $eventId ?>&set_active=<?= $v['id'] ?>" class="btn-apple-secondary btn-sm py-1 px-3">
                                    Promote to Active
                                </a>
                            <?php else: ?>
                                <a href="<?= base_url('pages/allocations/editor.php?id=' . $eventId) ?>" class="btn-apple-primary btn-sm py-1 px-3">
                                    Edit Active Plan
                                </a>
                            <?php endif; ?>
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
