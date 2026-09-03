<?php
// Parikshya Sathi - Add New Student
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$programs = get_all_programs();
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

    // Validation
    if (empty($symbolNo)) $errors[] = 'Symbol number is required.';
    if (empty($name)) $errors[] = 'Student name is required.';
    if ($programId <= 0) $errors[] = 'Please select a program.';

    // Check duplicate symbol number in current academic year
    if (!empty($symbolNo)) {
        $stmt = $db->prepare("SELECT id FROM students WHERE academic_year_id = ? AND symbol_no = ? AND is_deleted = 0");
        $stmt->execute([(int)$activeYear['id'], $symbolNo]);
        if ($stmt->fetch()) {
            $errors[] = "Symbol number '{$symbolNo}' is already assigned to another student in this academic year.";
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO students (academic_year_id, symbol_no, roll_no, name, program_id, semester, section, gender, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([(int)$activeYear['id'], $symbolNo, $rollNo, $name, $programId, $semester, $section, $gender, $phone, $email]);

        set_flash('success', "Student '{$name}' registered successfully.");
        header('Location: ' . base_url('pages/students/index.php'));
        exit;
    }
}

$pageTitle = 'Add Student';
$pageHeading = 'Student Registration';
$pageBadge = 'AY ' . $activeYear['name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="apple-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="display-md mb-0">Student Information</h3>
                    <div class="caption">Enter student academic and examination identifiers</div>
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
                        <input type="text" name="symbol_no" class="form-control-apple font-monospace" placeholder="e.g. 260155" value="<?= htmlspecialchars($_POST['symbol_no'] ?? '') ?>" required>
                        <div class="fine-print mt-1">Unique examination identifier for AY <?= htmlspecialchars($activeYear['name']) ?></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label caption-strong">Class Roll Number</label>
                        <input type="text" name="roll_no" class="form-control-apple" placeholder="e.g. 55" value="<?= htmlspecialchars($_POST['roll_no'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label caption-strong">Full Student Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control-apple" placeholder="e.g. Aayush Sharma" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Academic Program <span class="text-danger">*</span></label>
                        <select name="program_id" class="form-select-apple" required>
                            <option value="">Select Program</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (isset($_POST['program_id']) && (int)$_POST['program_id'] === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['code']) ?> — <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Semester</label>
                        <select name="semester" class="form-select-apple">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i ?>" <?= (isset($_POST['semester']) && (int)$_POST['semester'] === $i) ? 'selected' : '' ?>>
                                    Semester <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Section</label>
                        <select name="section" class="form-select-apple">
                            <option value="A" <?= (($_POST['section'] ?? '') === 'A') ? 'selected' : '' ?>>Section A</option>
                            <option value="B" <?= (($_POST['section'] ?? '') === 'B') ? 'selected' : '' ?>>Section B</option>
                            <option value="C" <?= (($_POST['section'] ?? '') === 'C') ? 'selected' : '' ?>>Section C</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Gender</label>
                        <select name="gender" class="form-select-apple">
                            <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Phone Contact</label>
                        <input type="text" name="phone" class="form-control-apple" placeholder="e.g. 9812345678" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Email Address</label>
                        <input type="email" name="email" class="form-control-apple" placeholder="e.g. student@institution.edu" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="col-12 mt-4 pt-3 border-top border-secondary-subtle d-flex justify-content-end gap-2">
                        <a href="<?= base_url('pages/students/index.php') ?>" class="btn-apple-secondary">Cancel</a>
                        <button type="submit" class="btn-apple-primary">Save Student</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
