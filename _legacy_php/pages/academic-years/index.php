<?php
// Parikshya Sathi - Academic Year Management
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();

// Handle Switch Active Year
if (isset($_GET['switch'])) {
    $switchId = (int)$_GET['switch'];
    $stmt = $db->prepare("SELECT * FROM academic_years WHERE id = ?");
    $stmt->execute([$switchId]);
    $target = $stmt->fetch();
    if ($target) {
        $_SESSION['active_academic_year_id'] = $target['id'];
        set_flash('success', "Active academic year switched to {$target['name']}.");
    }
    header('Location: ' . base_url('pages/academic-years/index.php'));
    exit;
}

// Handle Add New Academic Year
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_year') {
    $name = trim($_POST['name'] ?? '');
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $setAsActive = isset($_POST['set_active']) ? 1 : 0;

    if (!empty($name)) {
        if ($setAsActive) {
            $db->exec("UPDATE academic_years SET is_active = 0");
        }
        $stmt = $db->prepare("INSERT INTO academic_years (name, start_date, end_date, status, is_active) VALUES (?, ?, ?, 'active', ?)");
        $stmt->execute([$name, $startDate, $endDate, $setAsActive]);
        $newId = (int)$db->lastInsertId();

        if ($setAsActive) {
            $_SESSION['active_academic_year_id'] = $newId;
        }

        set_flash('success', "Academic Year {$name} created successfully.");
        header('Location: ' . base_url('pages/academic-years/index.php'));
        exit;
    }
}

// Handle Archive Academic Year
if (isset($_GET['archive'])) {
    $archiveId = (int)$_GET['archive'];
    $db->prepare("UPDATE academic_years SET status = 'archived' WHERE id = ?")->execute([$archiveId]);
    set_flash('info', "Academic year archived. Historical allocations and student records are safely preserved.");
    header('Location: ' . base_url('pages/academic-years/index.php'));
    exit;
}

// Fetch all Academic Years with student and allocation counts
$years = $db->query("SELECT y.*, 
    (SELECT COUNT(*) FROM students WHERE academic_year_id = y.id AND is_deleted = 0) as student_count,
    (SELECT COUNT(*) FROM allocation_events WHERE academic_year_id = y.id) as event_count 
    FROM academic_years y 
    ORDER BY y.id DESC")->fetchAll();

$pageTitle = 'Academic Years';
$pageHeading = 'Academic Year Management';
$pageBadge = 'History & Scope';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="apple-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="display-md mb-0">Academic Years Directory</h3>
                    <div class="caption">Manage institutional cohorts and switch current operational scope</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-apple">
                    <thead>
                        <tr>
                            <th>Academic Year</th>
                            <th>Status</th>
                            <th>Enrolled Students</th>
                            <th>Allocation Events</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($years as $yr): ?>
                            <?php $isCurrentlyActive = ((int)$yr['id'] === (int)$activeYear['id']); ?>
                            <tr class="<?= $isCurrentlyActive ? 'table-light' : '' ?>">
                                <td>
                                    <div class="body-strong fs-6">
                                        <?= htmlspecialchars($yr['name']) ?>
                                        <?php if ($isCurrentlyActive): ?>
                                            <span class="badge-apple badge-apple-primary ms-2"><i class="bi bi-check-circle-fill"></i> Current Scope</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fine-print text-muted">
                                        <?= format_date($yr['start_date']) ?> – <?= format_date($yr['end_date']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($yr['status'] === 'active'): ?>
                                        <span class="badge-apple badge-apple-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge-apple badge-apple-neutral">Archived</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="caption-strong"><?= (int)$yr['student_count'] ?> Students</span>
                                </td>
                                <td>
                                    <span class="caption"><?= (int)$yr['event_count'] ?> Events</span>
                                </td>
                                <td class="text-end">
                                    <?php if (!$isCurrentlyActive): ?>
                                        <a href="?switch=<?= $yr['id'] ?>" class="btn-apple-secondary btn-sm py-1 px-3">
                                            Switch To This Year
                                        </a>
                                    <?php else: ?>
                                        <span class="text-success caption fw-semibold me-2"><i class="bi bi-eye"></i> Viewing</span>
                                    <?php endif; ?>

                                    <?php if ($yr['status'] === 'active' && !$isCurrentlyActive): ?>
                                        <a href="?archive=<?= $yr['id'] ?>" class="btn-apple-ghost btn-sm py-1 px-2 text-muted" onclick="return confirm('Archive this academic year? Historical records will remain viewable.')">
                                            Archive
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="apple-card">
            <h3 class="display-md mb-2">Create Academic Year</h3>
            <div class="caption mb-3">Establish a new academic calendar period</div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="create_year">

                <div class="mb-3">
                    <label class="form-label caption-strong">Academic Year Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control-apple" placeholder="e.g. 2027/28" required>
                    <div class="fine-print mt-1">Common format: YYYY/YY (e.g. 2026/27)</div>
                </div>

                <div class="mb-3">
                    <label class="form-label caption-strong">Session Start Date</label>
                    <input type="date" name="start_date" class="form-control-apple" value="<?= date('Y-01-01', strtotime('+1 year')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label caption-strong">Session End Date</label>
                    <input type="date" name="end_date" class="form-control-apple" value="<?= date('Y-12-31', strtotime('+1 year')) ?>">
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="set_active" id="set_active_check" value="1" checked>
                    <label class="form-check-label caption" for="set_active_check">
                        Set as Active Working Scope Immediately
                    </label>
                </div>

                <button type="submit" class="btn-apple-primary w-100 py-2">
                    <i class="bi bi-calendar-plus me-1"></i> Save Academic Year
                </button>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
