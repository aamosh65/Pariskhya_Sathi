<?php
// Parikshya Sathi - Allocation Event Wizard
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$programs = get_all_programs();
$rooms = get_all_rooms((int)$activeYear['id']);

// Fetch student counts grouped by program & semester for live calculation
$stmtCounts = $db->prepare("SELECT program_id, semester, COUNT(*) as count FROM students WHERE academic_year_id = ? AND is_deleted = 0 GROUP BY program_id, semester");
$stmtCounts->execute([(int)$activeYear['id']]);
$cohortCounts = $stmtCounts->fetchAll();

$countsMap = [];
foreach ($cohortCounts as $c) {
    $countsMap[$c['program_id'] . '_' . $c['semester']] = (int)$c['count'];
}

$pageTitle = 'New Allocation Wizard';
$pageHeading = 'Create Allocation Event';
$pageBadge = 'AY ' . $activeYear['name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<form method="POST" action="<?= base_url('pages/allocations/generate.php') ?>" id="allocation-wizard-form">
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <!-- Step 1: Examination Session Details -->
            <div class="apple-card mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge-apple badge-apple-primary">Step 1</span>
                    <h3 class="display-md mb-0">Examination Session Details</h3>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label caption-strong">Event Name / Exam Title <span class="text-danger">*</span></label>
                        <input type="text" name="event_name" class="form-control-apple" placeholder="e.g. End-Semester Final Examination 2026 (Morning Shift)" value="Semester Final Examination 2026 - Morning Session" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Examination Date <span class="text-danger">*</span></label>
                        <input type="date" name="event_date" class="form-control-apple" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">Start Time</label>
                        <input type="text" name="start_time" class="form-control-apple" value="09:00 AM" placeholder="09:00 AM" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label caption-strong">End Time</label>
                        <input type="text" name="end_time" class="form-control-apple" value="12:00 PM" placeholder="12:00 PM" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label caption-strong">Administrative Notes</label>
                        <textarea name="notes" class="form-control-apple" rows="2" placeholder="Optional notes (e.g. Special instructions for invigilators, strict program mixing)"></textarea>
                    </div>
                </div>
            </div>

            <!-- Step 2: Participating Programs, Semesters & Subjects -->
            <div class="apple-card mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-apple badge-apple-primary">Step 2</span>
                        <h3 class="display-md mb-0">Participating Cohorts & Papers</h3>
                    </div>
                    <span class="fine-print text-muted">Select cohorts taking exam concurrently</span>
                </div>

                <div class="table-responsive">
                    <table class="table-apple">
                        <thead>
                            <tr>
                                <th style="width: 40px;">Select</th>
                                <th>Program / Faculty</th>
                                <th>Semester</th>
                                <th>Available Students</th>
                                <th>Subject / Paper Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $cohortRowId = 0;
                            foreach ($programs as $p): 
                                for ($sem = 2; $sem <= 4; $sem += 2): // Demo Sem 2 and Sem 4
                                    $cKey = $p['id'] . '_' . $sem;
                                    $studentCount = $countsMap[$cKey] ?? 0;
                                    if ($studentCount === 0) continue;
                                    $cohortRowId++;
                                    $isDefaultSelected = ($cohortRowId <= 2);
                            ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="cohorts[<?= $cohortRowId ?>][selected]" value="1" class="form-check-input cohort-checkbox" data-count="<?= $studentCount ?>" <?= $isDefaultSelected ? 'checked' : '' ?>>
                                        <input type="hidden" name="cohorts[<?= $cohortRowId ?>][program_id]" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="cohorts[<?= $cohortRowId ?>][semester]" value="<?= $sem ?>">
                                    </td>
                                    <td>
                                        <div class="body-strong"><?= htmlspecialchars($p['code']) ?></div>
                                        <div class="fine-print text-muted"><?= htmlspecialchars($p['name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-apple badge-apple-neutral">Semester <?= $sem ?></span>
                                    </td>
                                    <td>
                                        <span class="caption-strong"><?= $studentCount ?> Students</span>
                                    </td>
                                    <td>
                                        <input type="text" name="cohorts[<?= $cohortRowId ?>][subject_name]" class="form-control-apple form-control-sm" placeholder="e.g. Paper Title" value="<?= $p['code'] === 'BCA' ? 'Data Structures & Algorithms' : ($p['code'] === 'BSc.CSIT' ? 'Object Oriented Programming' : 'Business Statistics') ?>">
                                    </td>
                                </tr>
                            <?php 
                                endfor;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Step 3: Select Examination Rooms -->
            <div class="apple-card mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-apple badge-apple-primary">Step 3</span>
                        <h3 class="display-md mb-0">Select Examination Halls</h3>
                    </div>
                    <span class="fine-print text-muted">Available rooms for this session</span>
                </div>

                <div class="row g-3">
                    <?php foreach ($rooms as $index => $rm): ?>
                        <div class="col-12 col-md-6">
                            <label class="apple-card p-3 mb-0 d-flex align-items-center justify-content-between w-100 cursor-pointer" style="cursor: pointer;">
                                <div class="d-flex align-items-center gap-3">
                                    <input type="checkbox" name="rooms[]" value="<?= $rm['id'] ?>" class="form-check-input room-checkbox" data-capacity="<?= (int)$rm['capacity'] ?>" <?= $index < 3 ? 'checked' : '' ?>>
                                    <div>
                                        <div class="body-strong"><?= htmlspecialchars($rm['room_name']) ?></div>
                                        <div class="fine-print text-muted"><?= htmlspecialchars($rm['building']) ?> &bull; <?= htmlspecialchars($rm['floor']) ?></div>
                                    </div>
                                </div>
                                <span class="badge-apple badge-apple-primary"><?= (int)$rm['capacity'] ?> Seats</span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Capacity Meter, Rules & Run Button -->
        <div class="col-12 col-lg-4">
            <!-- Capacity Validator Widget -->
            <div class="apple-card mb-4" style="background-color: var(--surface-pearl);">
                <div class="caption-strong text-uppercase text-muted mb-2">Live Capacity Validator</div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="caption">Students to Allocate:</span>
                    <span id="wizard-selected-students" class="body-strong text-primary">80 Students</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="caption">Total Room Capacity:</span>
                    <span id="wizard-selected-capacity" class="body-strong text-success">96 Seats</span>
                </div>

                <div class="progress mb-3" style="height: 10px; border-radius: 9999px;">
                    <div id="wizard-capacity-bar" class="progress-bar bg-success" role="progressbar" style="width: 83%; border-radius: 9999px;"></div>
                </div>

                <div id="wizard-capacity-message" class="fine-print text-success fw-medium">
                    <i class="bi bi-check-circle-fill me-1"></i> Capacity is sufficient (+16 buffer seats).
                </div>
            </div>

            <!-- Seating Rules Configurator -->
            <div class="apple-card mb-4">
                <h4 class="tagline mb-2">Seating Rules & Constraints</h4>
                <div class="caption mb-3">Algorithmic separation policies</div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="rule_program_separation" id="r_prog" value="1" checked>
                    <label class="form-check-label caption-strong" for="r_prog">
                        Program Separation
                    </label>
                    <div class="fine-print text-muted">Never place students of the same program/faculty on the same bench.</div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="rule_semester_interleaving" id="r_sem" value="1" checked>
                    <label class="form-check-label caption-strong" for="r_sem">
                        Semester Interleaving
                    </label>
                    <div class="fine-print text-muted">Alternate semester cohorts across adjacent seats.</div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="rule_randomize" id="r_rand" value="1" checked>
                    <label class="form-check-label caption-strong" for="r_rand">
                        Randomize Student Order
                    </label>
                    <div class="fine-print text-muted">Apply Fisher-Yates shuffle to randomize seat placement.</div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="allow_relaxation" id="r_relax" value="1" checked>
                    <label class="form-check-label caption-strong" for="r_relax">
                        Enable Rule Relaxation Ladder (FR-10)
                    </label>
                    <div class="fine-print text-muted">Automatically relax constraints if strict arrangement is impossible.</div>
                </div>

                <button type="submit" id="generate-allocation-btn" class="btn-apple-primary w-100 py-3 fs-6">
                    <i class="bi bi-magic me-1"></i> Run Allocation Engine
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cohortCheckboxes = document.querySelectorAll('.cohort-checkbox');
    const roomCheckboxes = document.querySelectorAll('.room-checkbox');
    const studentsLabel = document.getElementById('wizard-selected-students');
    const capacityLabel = document.getElementById('wizard-selected-capacity');
    const progressBar = document.getElementById('wizard-capacity-bar');
    const msg = document.getElementById('wizard-capacity-message');
    const submitBtn = document.getElementById('generate-allocation-btn');

    function calculate() {
        let totalStudents = 0;
        cohortCheckboxes.forEach(cb => {
            if (cb.checked) totalStudents += parseInt(cb.getAttribute('data-count') || 0, 10);
        });

        let totalCap = 0;
        roomCheckboxes.forEach(cb => {
            if (cb.checked) totalCap += parseInt(cb.getAttribute('data-capacity') || 0, 10);
        });

        if (studentsLabel) studentsLabel.innerText = totalStudents + ' Students';
        if (capacityLabel) capacityLabel.innerText = totalCap + ' Seats';

        const percent = totalCap > 0 ? Math.min(Math.round((totalStudents / totalCap) * 100), 100) : 0;
        if (progressBar) {
            progressBar.style.width = percent + '%';
            if (totalStudents > totalCap) {
                progressBar.className = 'progress-bar bg-danger';
            } else {
                progressBar.className = 'progress-bar bg-success';
            }
        }

        if (totalStudents === 0) {
            msg.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i> Please select at least one cohort.</span>';
            submitBtn.disabled = true;
        } else if (totalStudents > totalCap) {
            const deficit = totalStudents - totalCap;
            msg.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Insufficient Capacity: Shortfall of ${deficit} seats. Select more rooms.</span>`;
            submitBtn.disabled = false; // Allow draft allocation as per FR-10 / FR-14
        } else {
            const buffer = totalCap - totalStudents;
            msg.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Capacity is sufficient (+${buffer} buffer seats).</span>`;
            submitBtn.disabled = false;
        }
    }

    cohortCheckboxes.forEach(cb => cb.addEventListener('change', calculate));
    roomCheckboxes.forEach(cb => cb.addEventListener('change', calculate));
    calculate();
});
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
