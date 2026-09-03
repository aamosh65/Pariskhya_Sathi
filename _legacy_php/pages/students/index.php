<?php
// Parikshya Sathi - Student Master Directory
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$programs = get_all_programs();

// Filter parameters
$filterProgram = isset($_GET['program_id']) && $_GET['program_id'] !== '' ? (int)$_GET['program_id'] : null;
$filterSemester = isset($_GET['semester']) && $_GET['semester'] !== '' ? (int)$_GET['semester'] : null;
$filterSection = isset($_GET['section']) && $_GET['section'] !== '' ? trim($_GET['section']) : null;
$filterSearch = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build Query
$query = "SELECT s.*, p.name as program_name, p.code as program_code 
          FROM students s 
          JOIN programs p ON s.program_id = p.id 
          WHERE s.academic_year_id = ? AND s.is_deleted = 0";
$params = [(int)$activeYear['id']];

if ($filterProgram) {
    $query .= " AND s.program_id = ?";
    $params[] = $filterProgram;
}
if ($filterSemester) {
    $query .= " AND s.semester = ?";
    $params[] = $filterSemester;
}
if ($filterSection) {
    $query .= " AND s.section = ?";
    $params[] = $filterSection;
}
if (!empty($filterSearch)) {
    $query .= " AND (s.name LIKE ? OR s.symbol_no LIKE ? OR s.roll_no LIKE ? OR s.email LIKE ?)";
    $like = "%{$filterSearch}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$query .= " ORDER BY p.code ASC, s.semester ASC, s.symbol_no ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = 'Students Directory';
$pageHeading = 'Student Management';
$pageBadge = count($students) . ' Students Loaded';

$headerActionHtml = '
<div class="d-flex align-items-center gap-2">
    <a href="' . base_url('pages/students/import.php') . '" class="btn-apple-secondary">
        <i class="bi bi-file-earmark-arrow-up"></i> Bulk Import
    </a>
    <a href="' . base_url('pages/students/create.php') . '" class="btn-apple-primary">
        <i class="bi bi-person-plus"></i> Add Student
    </a>
</div>
';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filter & Search Bar -->
<div class="apple-card p-3 mb-4">
    <form method="GET" action="" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <input type="text" name="q" value="<?= htmlspecialchars($filterSearch) ?>" class="search-input-pill" placeholder="Search by name, symbol no, roll no..." data-table-search="#students-table">
        </div>
        <div class="col-6 col-md-2">
            <select name="program_id" class="form-select-apple" onchange="this.form.submit()">
                <option value="">All Programs</option>
                <?php foreach ($programs as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $filterProgram === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['code']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="semester" class="form-select-apple" onchange="this.form.submit()">
                <option value="">All Semesters</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i ?>" <?= $filterSemester === $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="section" class="form-select-apple" onchange="this.form.submit()">
                <option value="">All Sections</option>
                <option value="A" <?= $filterSection === 'A' ? 'selected' : '' ?>>Section A</option>
                <option value="B" <?= $filterSection === 'B' ? 'selected' : '' ?>>Section B</option>
                <option value="C" <?= $filterSection === 'C' ? 'selected' : '' ?>>Section C</option>
            </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
            <button type="submit" class="btn-apple-dark w-100 justify-content-center">Filter</button>
            <?php if ($filterProgram || $filterSemester || $filterSection || $filterSearch): ?>
                <a href="<?= base_url('pages/students/index.php') ?>" class="btn-apple-secondary py-1 px-2" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Student Master Table -->
<div class="apple-card p-0 overflow-hidden">
    <div class="p-3 d-flex justify-content-between align-items-center border-bottom border-secondary-subtle">
        <div class="caption-strong">Enrolled Students in AY <?= htmlspecialchars($activeYear['name']) ?></div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('pages/students/symbols.php') ?>" class="btn-apple-ghost btn-sm">
                <i class="bi bi-hash"></i> Batch Symbol Numbers
            </a>
        </div>
    </div>

    <?php if (empty($students)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-person-x fs-1 mb-2 d-block text-secondary"></i>
            <p class="body-text mb-2">No students match the selected filter criteria.</p>
            <a href="<?= base_url('pages/students/create.php') ?>" class="btn-apple-primary btn-sm">Register Student</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-apple" id="students-table">
                <thead>
                    <tr>
                        <th>Symbol No</th>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Program / Faculty</th>
                        <th>Semester</th>
                        <th>Section</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $st): ?>
                        <tr>
                            <td>
                                <span class="badge-apple badge-apple-neutral font-monospace fw-bold fs-6">
                                    <?= htmlspecialchars($st['symbol_no']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($st['roll_no']) ?></td>
                            <td>
                                <div class="body-strong"><?= htmlspecialchars($st['name']) ?></div>
                                <div class="fine-print text-muted"><?= htmlspecialchars($st['email'] ?: 'No email registered') ?></div>
                            </td>
                            <td>
                                <span class="badge-apple badge-apple-primary"><?= htmlspecialchars($st['program_code']) ?></span>
                            </td>
                            <td>
                                <span class="caption">Sem <?= (int)$st['semester'] ?></span>
                            </td>
                            <td>
                                <span class="caption"><?= htmlspecialchars($st['section']) ?></span>
                            </td>
                            <td>
                                <span class="fine-print text-muted"><?= htmlspecialchars($st['gender']) ?></span>
                            </td>
                            <td>
                                <span class="fine-print text-muted"><?= htmlspecialchars($st['phone'] ?: '—') ?></span>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('pages/students/edit.php?id=' . $st['id']) ?>" class="btn-apple-secondary btn-sm py-1 px-3">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
