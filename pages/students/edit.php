<?php
// Parikshya Sathi - Edit Student Details & Soft Delete
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$programs = get_all_programs();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('danger', 'Student not found.');
    header('Location: ' . base_url('pages/students/index.php'));
    exit;
}

// Handle Soft Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $db->prepare("UPDATE students SET is_deleted = 1 WHERE id = ?")->execute([$id]);
    set_flash('info', "Student '{$student['name']}' has been removed (soft deleted). Historical allocations are preserved.");
    header('Location: ' . base_url('pages/students/index.php'));
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbolNo = trim($_POST['symbol_no'] ?? '');
    $rollNo = trim($_POST['roll_no'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $programId = (int)($_POST['program_id'] ?? 0);
    $semester = (int)($_POST['semester'] ?? 1);
    $section = trim($_POST['section'] ?? 'A');
    $gender = trim($_POST['gender'] ?? 'Male');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    if (empty($symbolNo)) $errors[] = 'Symbol number is required.';
    if (empty($name)) $errors[] = 'Student name is required.';
    if ($programId <= 0) $errors[] = 'Please select a program.';

    // Check duplicate symbol number excluding current student
    if (!empty($symbolNo)) {
        $stmtDup = $db->prepare("SELECT id FROM students WHERE academic_year_id = ? AND symbol_no = ? AND id != ? AND is_deleted = 0");
        $stmtDup->execute([(int)$student['academic_year_id'], $symbolNo, $id]);
        if ($stmtDup->fetch()) {
            $errors[] = "Symbol number '{$symbolNo}' is already taken by another student in this academic year.";
        }
    }

    if (empty($errors)) {
        $stmtUpdate = $db->prepare("UPDATE students SET symbol_no = ?, roll_no = ?, name = ?, program_id = ?, semester = ?, section = ?, gender = ?, phone = ?, email = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmtUpdate->execute([$symbolNo, $rollNo, $name, $programId, $semester, $section, $gender, $phone, $email, $status, $id]);

        set_flash('success', "Student '{$name}' updated successfully.");
        header('Location: ' . base_url('pages/students/index.php'));
        exit;
    }
}

$pageTitle = 'Edit Student';
$pageHeading = 'Edit Student Information';
$pageBadge = $student['symbol_no'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="apple-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="display-md mb-0">Edit: <?= htmlspecialchars($student['name']) ?></h3>
                    <div class="caption">Update student academic records and contact details</div>
                </div>
                <a href="<?= base_url('pages/students/index.php') ?>" class="btn-apple-secondary btn-sm">Back to List</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger p-3 mb-4 rounded-3">
                    <ul class="mb-0 fine-print">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label caption-strong">Exam Symbol Number <span class="text-danger">*</span></label>
                        <input type="text" name="symbol_no" class="form-control-apple font-monospace" value="<?= htmlspecialchars($_POST['symbol_no'] ?? $student['symbol_no']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label caption-strong">Class Roll Number</label>
                        <input type="text" name="roll_no" class="form-control-apple" value="<?= htmlspecialchars($_POST['roll_no'] ?? $student['roll_no']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label caption-strong">Full Student Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control-apple" value="<?= htmlspecialchars($_POST['name'] ?? $student['name']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Academic Program <span class="text-danger">*</span></label>
                        <select name="program_id" class="form-select-apple" required>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ((int)($_POST['program_id'] ?? $student['program_id']) === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['code']) ?> — <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Semester</label>
                        <select name="semester" class="form-select-apple">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i ?>" <?= ((int)($_POST['semester'] ?? $student['semester']) === $i) ? 'selected' : '' ?>>
                                    Semester <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Section</label>
                        <select name="section" class="form-select-apple">
                            <option value="A" <?= (($_POST['section'] ?? $student['section']) === 'A') ? 'selected' : '' ?>>Section A</option>
                            <option value="B" <?= (($_POST['section'] ?? $student['section']) === 'B') ? 'selected' : '' ?>>Section B</option>
                            <option value="C" <?= (($_POST['section'] ?? $student['section']) === 'C') ? 'selected' : '' ?>>Section C</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Gender</label>
                        <select name="gender" class="form-select-apple">
                            <option value="Male" <?= (($_POST['gender'] ?? $student['gender']) === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (($_POST['gender'] ?? $student['gender']) === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (($_POST['gender'] ?? $student['gender']) === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Phone Contact</label>
                        <input type="text" name="phone" class="form-control-apple" value="<?= htmlspecialchars($_POST['phone'] ?? $student['phone']) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Email Address</label>
                        <input type="email" name="email" class="form-control-apple" value="<?= htmlspecialchars($_POST['email'] ?? $student['email']) ?>">
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top border-secondary-subtle d-flex justify-content-between align-items-center">
                        <a href="?id=<?= $student['id'] ?>&action=delete" class="btn-apple-ghost text-danger" onclick="return confirm('Soft-delete this student? Allocation history will be preserved.')">
                            <i class="bi bi-trash3"></i> Delete Student
                        </a>
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('pages/students/index.php') ?>" class="btn-apple-secondary">Cancel</a>
                            <button type="submit" class="btn-apple-primary">Update Student</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
