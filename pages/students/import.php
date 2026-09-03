<?php
// Parikshya Sathi - Bulk Student CSV Import with Preview & Validation
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$programs = get_all_programs();
$programMap = [];
foreach ($programs as $p) {
    $programMap[strtoupper($p['code'])] = (int)$p['id'];
}

// Handle Form Submission of Parsed CSV Data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['parsed_csv_json'])) {
    $jsonData = $_POST['parsed_csv_json'];
    $rows = json_decode($jsonData, true);

    if (is_array($rows) && !empty($rows)) {
        $db->beginTransaction();
        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        $stmtCheck = $db->prepare("SELECT id FROM students WHERE academic_year_id = ? AND symbol_no = ?");
        $stmtInsert = $db->prepare("INSERT INTO students (academic_year_id, symbol_no, roll_no, name, program_id, semester, section, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmtUpdate = $db->prepare("UPDATE students SET roll_no = ?, name = ?, program_id = ?, semester = ?, section = ?, gender = ?, is_deleted = 0 WHERE id = ?");

        foreach ($rows as $row) {
            if (!empty($row['error'])) {
                $skippedCount++;
                continue;
            }

            $symbolNo = trim((string)$row['symbol_no']);
            $rollNo = trim((string)$row['roll_no']);
            $name = trim((string)$row['name']);
            $pCode = strtoupper(trim((string)$row['program_code']));
            $progId = $programMap[$pCode] ?? 1;
            $semester = (int)($row['semester'] ?? 1);
            $section = trim((string)($row['section'] ?? 'A'));
            $gender = trim((string)($row['gender'] ?? 'Male'));

            // Check if student with symbol_no already exists
            $stmtCheck->execute([(int)$activeYear['id'], $symbolNo]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                // Update existing student
                $stmtUpdate->execute([$rollNo, $name, $progId, $semester, $section, $gender, $existing['id']]);
                $updatedCount++;
            } else {
                // Insert new student
                $stmtInsert->execute([(int)$activeYear['id'], $symbolNo, $rollNo, $name, $progId, $semester, $section, $gender]);
                $importedCount++;
            }
        }

        $db->commit();
        set_flash('success', "Import Complete: {$importedCount} new students added, {$updatedCount} updated, {$skippedCount} skipped.");
        header('Location: ' . base_url('pages/students/index.php'));
        exit;
    }
}

$pageTitle = 'Bulk CSV Import';
$pageHeading = 'Student CSV Import';
$pageBadge = 'AY ' . $activeYear['name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <!-- Upload Card -->
        <div class="apple-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="display-md mb-0">Upload Student Data</h3>
                    <div class="caption">Load hundreds or thousands of student records via standard CSV</div>
                </div>
                <a href="<?= base_url('pages/students/index.php') ?>" class="btn-apple-secondary btn-sm">Student Directory</a>
            </div>

            <div class="p-4 border border-2 border-dashed rounded-4 text-center mb-4" style="background-color: var(--surface-pearl);">
                <i class="bi bi-filetype-csv text-primary fs-1 mb-2 d-block"></i>
                <div class="body-strong mb-1">Choose a CSV file to upload & preview</div>
                <div class="fine-print text-muted mb-3">Format: SymbolNo, RollNo, Name, ProgramCode, Semester, Section, Gender</div>

                <div class="d-inline-block">
                    <input type="file" id="student-csv-file" class="form-control-apple" accept=".csv, text/csv">
                </div>
            </div>

            <!-- Download Sample CSV Template -->
            <div class="d-flex justify-content-between align-items-center p-3 rounded-3" style="background-color: var(--canvas-parchment);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-primary"></i>
                    <span class="fine-print text-dark">Need the standard spreadsheet format? Download our pre-filled template.</span>
                </div>
                <button type="button" class="btn-apple-dark btn-sm" onclick="downloadSampleCsv()">
                    <i class="bi bi-download me-1"></i> Sample Template
                </button>
            </div>
        </div>

        <!-- Live Preview Card (Hidden until file selected) -->
        <div id="csv-preview-card" class="apple-card">
            <form method="POST" action="">
                <input type="hidden" name="parsed_csv_json" id="parsed-csv-json" value="">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="tagline mb-0">Import Preview & Validation</h4>
                        <div class="caption">Review parsed rows and detected discrepancies before committing</div>
                    </div>
                    <button type="submit" id="commit-import-btn" class="btn-apple-primary" disabled>
                        <i class="bi bi-cloud-arrow-up me-1"></i> Confirm & Import
                    </button>
                </div>

                <div id="csv-preview-container" class="d-none">
                    <!-- Table rendered dynamically by assets/js/app.js -->
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function downloadSampleCsv() {
    const csvContent = "data:text/csv;charset=utf-8," + 
        "SymbolNo,RollNo,Name,ProgramCode,Semester,Section,Gender\n" +
        "260191,91,Manish Shrestha,BCA,2,A,Male\n" +
        "260192,92,Pooja Thapa,BCA,2,A,Female\n" +
        "260291,91,Sagar Karki,BSc.CSIT,2,B,Male\n" +
        "260391,91,Sneha Basnet,BBM,4,A,Female\n" +
        "260491,91,Rohan Maharjan,BBA,4,A,Male";
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "parikshya_sathi_sample_students.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
