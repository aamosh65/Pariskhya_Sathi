<?php
// Parikshya Sathi - Symbol Number Generation & Management
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$programs = get_all_programs();

// Handle Batch Generate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_generate') {
    $progId = (int)($_POST['program_id'] ?? 0);
    $semester = (int)($_POST['semester'] ?? 0);
    $prefix = trim($_POST['prefix'] ?? '2601');
    $startSeq = (int)($_POST['start_seq'] ?? 1);
    $orderBy = $_POST['order_by'] ?? 'roll_no'; // 'roll_no' or 'name'

    $query = "SELECT id, roll_no, name FROM students WHERE academic_year_id = ? AND program_id = ? AND semester = ? AND is_deleted = 0 ORDER BY " . ($orderBy === 'name' ? 'name ASC' : 'CAST(roll_no AS UNSIGNED) ASC, roll_no ASC');
    $stmt = $db->prepare($query);
    $stmt->execute([(int)$activeYear['id'], $progId, $semester]);
    $stList = $stmt->fetchAll();

    $stmtUp = $db->prepare("UPDATE students SET symbol_no = ? WHERE id = ?");
    $seq = $startSeq;
    foreach ($stList as $st) {
        $symbolNo = sprintf('%s%02d', $prefix, $seq++);
        $stmtUp->execute([$symbolNo, $st['id']]);
    }

    set_flash('success', "Batch generated " . count($stList) . " symbol numbers with prefix '{$prefix}'.");
    header('Location: ' . base_url('pages/students/symbols.php?program_id=' . $progId . '&semester=' . $semester));
    exit;
}

// Filter for viewing students
$selProg = (int)($_GET['program_id'] ?? 1);
$selSem = (int)($_GET['semester'] ?? 2);

$stmtStudents = $db->prepare("SELECT s.*, p.code as program_code FROM students s JOIN programs p ON s.program_id = p.id WHERE s.academic_year_id = ? AND s.program_id = ? AND s.semester = ? AND s.is_deleted = 0 ORDER BY s.symbol_no ASC");
$stmtStudents->execute([(int)$activeYear['id'], $selProg, $selSem]);
$cohortStudents = $stmtStudents->fetchAll();

$pageTitle = 'Symbol Numbers';
$pageHeading = 'Symbol Number Management';
$pageBadge = 'AY ' . $activeYear['name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <!-- Generator Tool -->
    <div class="col-12 col-lg-4">
        <div class="apple-card">
            <h3 class="display-md mb-2">Auto-Generate Symbols</h3>
            <div class="caption mb-3">Batch assign sequential exam symbol numbers to a student cohort</div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="batch_generate">

                <div class="mb-3">
                    <label class="form-label caption-strong">Target Program</label>
                    <select name="program_id" class="form-select-apple" required>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $selProg === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['code']) ?> — <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label caption-strong">Semester</label>
                    <select name="semester" class="form-select-apple" required>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= $selSem === $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label caption-strong">Symbol Prefix</label>
                    <input type="text" name="prefix" class="form-control-apple font-monospace" value="260<?= $selProg ?>" placeholder="e.g. 2601" required>
                    <div class="fine-print mt-1">Digits or codes preceding the sequential count</div>
                </div>

                <div class="mb-3">
                    <label class="form-label caption-strong">Starting Sequence Number</label>
                    <input type="number" name="start_seq" class="form-control-apple" value="1" min="1" required>
                </div>

                <div class="mb-4">
                    <label class="form-label caption-strong">Sorting Order</label>
                    <select name="order_by" class="form-select-apple">
                        <option value="roll_no">By Class Roll Number</option>
                        <option value="name">Alphabetical (Student Name)</option>
                    </select>
                </div>

                <button type="submit" class="btn-apple-primary w-100 py-2" onclick="return confirm('Regenerate symbol numbers for this cohort? Existing symbols will be updated.')">
                    <i class="bi bi-hash me-1"></i> Generate Symbol Numbers
                </button>
            </form>
        </div>
    </div>

    <!-- Cohort List & Manual Overrides -->
    <div class="col-12 col-lg-8">
        <div class="apple-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h3 class="display-md mb-0">Cohort Symbol Number Roster</h3>
                    <div class="caption">Review assigned exam numbers for selected cohort</div>
                </div>

                <!-- Fast Cohort Switcher -->
                <form method="GET" class="d-flex gap-2">
                    <select name="program_id" class="form-select-apple form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $selProg === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="semester" class="form-select-apple form-select-sm" onchange="this.form.submit()">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>" <?= $selSem === $i ? 'selected' : '' ?>>Sem <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <?php if (empty($cohortStudents)): ?>
                <div class="text-center py-5 text-muted">
                    <p class="body-text">No students found in this cohort.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-apple">
                        <thead>
                            <tr>
                                <th>Symbol No</th>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Section</th>
                                <th class="text-end">Manual Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cohortStudents as $cs): ?>
                                <tr>
                                    <td>
                                        <span class="badge-apple badge-apple-neutral font-monospace fs-6 fw-bold">
                                            <?= htmlspecialchars($cs['symbol_no']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($cs['roll_no']) ?></td>
                                    <td>
                                        <span class="body-strong"><?= htmlspecialchars($cs['name']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($cs['section']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= base_url('pages/students/edit.php?id=' . $cs['id']) ?>" class="btn-apple-ghost btn-sm">
                                            <i class="bi bi-pencil"></i> Override
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
