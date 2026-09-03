<?php
// Parikshya Sathi - Create Examination Room & Physical Layout
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomName = trim($_POST['room_name'] ?? '');
    $building = trim($_POST['building'] ?? 'Main Building');
    $floor = trim($_POST['floor'] ?? 'Ground Floor');
    $rowsCount = (int)($_POST['rows_count'] ?? 5);
    $colsCount = (int)($_POST['cols_count'] ?? 4);
    $seatsPerBench = (int)($_POST['default_seats_per_bench'] ?? 2);

    if (empty($roomName)) $errors[] = 'Room name / number is required.';
    if ($rowsCount < 1 || $rowsCount > 20) $errors[] = 'Rows must be between 1 and 20.';
    if ($colsCount < 1 || $colsCount > 10) $errors[] = 'Columns must be between 1 and 10.';
    if ($seatsPerBench < 1 || $seatsPerBench > 5) $errors[] = 'Seats per bench must be between 1 and 5.';

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $totalCapacity = $rowsCount * $colsCount * $seatsPerBench;
            $stmt = $db->prepare("INSERT INTO rooms (academic_year_id, room_name, building, floor, rows_count, cols_count, default_seats_per_bench, capacity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([(int)$activeYear['id'], $roomName, $building, $floor, $rowsCount, $colsCount, $seatsPerBench, $totalCapacity]);
            $roomId = (int)$db->lastInsertId();

            // Populate furniture & physical seat slots
            $stmtFurn = $db->prepare("INSERT INTO furniture (room_id, type, row_num, col_num, label, seat_count, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmtSeat = $db->prepare("INSERT INTO seats (furniture_id, room_id, seat_identifier, seat_number_in_bench, row_num, col_num, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");

            for ($r = 1; $r <= $rowsCount; $r++) {
                for ($c = 1; $c <= $colsCount; $c++) {
                    $furnType = ($seatsPerBench === 1) ? 'single_desk' : ('bench_' . $seatsPerBench);
                    $label = "Desk R{$r}-C{$c}";
                    $stmtFurn->execute([$roomId, $furnType, $r, $c, $label, $seatsPerBench]);
                    $furnId = (int)$db->lastInsertId();

                    for ($s = 1; $s <= $seatsPerBench; $s++) {
                        $seatIdent = "R{$r}-C{$c}-S{$s}";
                        $stmtSeat->execute([$furnId, $roomId, $seatIdent, $s, $r, $c]);
                    }
                }
            }

            $db->commit();
            set_flash('success', "Room '{$roomName}' created with {$totalCapacity} physical seats.");
            header('Location: ' . base_url('pages/rooms/index.php'));
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error saving room layout: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Room & Layout';
$pageHeading = 'Configure Examination Room';
$pageBadge = 'AY ' . $activeYear['name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<form method="POST" action="" id="room-layout-form">
    <div class="row g-4">
        <!-- Configuration Controls -->
        <div class="col-12 col-lg-5">
            <div class="apple-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="display-md mb-0">Room Specs</h3>
                        <div class="caption">Physical room & bench parameters</div>
                    </div>
                    <a href="<?= base_url('pages/rooms/index.php') ?>" class="btn-apple-secondary btn-sm">Cancel</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger p-3 mb-3 rounded-3">
                        <ul class="mb-0 fine-print">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label caption-strong">Room / Hall Name <span class="text-danger">*</span></label>
                    <input type="text" name="room_name" class="form-control-apple" placeholder="e.g. Hall 201 or Main Auditorium" value="<?= htmlspecialchars($_POST['room_name'] ?? '') ?>" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label caption-strong">Building / Block</label>
                        <input type="text" name="building" class="form-control-apple" placeholder="e.g. Main Block" value="<?= htmlspecialchars($_POST['building'] ?? 'Main Building') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label caption-strong">Floor Level</label>
                        <input type="text" name="floor" class="form-control-apple" placeholder="e.g. 1st Floor" value="<?= htmlspecialchars($_POST['floor'] ?? '1st Floor') ?>">
                    </div>
                </div>

                <hr class="my-4">

                <h4 class="tagline mb-2">Physical Layout Matrix</h4>
                <div class="caption mb-3">Grid dimensions of desks/benches across the floor</div>

                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <label class="form-label caption-strong">Rows</label>
                        <input type="number" name="rows_count" class="form-control-apple" value="<?= (int)($_POST['rows_count'] ?? 5) ?>" min="1" max="20" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label caption-strong">Columns</label>
                        <input type="number" name="cols_count" class="form-control-apple" value="<?= (int)($_POST['cols_count'] ?? 4) ?>" min="1" max="10" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label caption-strong">Per Bench</label>
                        <select name="default_seats_per_bench" class="form-select-apple">
                            <option value="1" <?= (($_POST['default_seats_per_bench'] ?? '') === '1') ? 'selected' : '' ?>>1 (Single)</option>
                            <option value="2" <?= (($_POST['default_seats_per_bench'] ?? '2') === '2') ? 'selected' : '' ?>>2 Seater</option>
                            <option value="3" <?= (($_POST['default_seats_per_bench'] ?? '') === '3') ? 'selected' : '' ?>>3 Seater</option>
                            <option value="4" <?= (($_POST['default_seats_per_bench'] ?? '') === '4') ? 'selected' : '' ?>>4 Seater</option>
                        </select>
                    </div>
                </div>

                <div class="p-3 rounded-3 mb-4 d-flex justify-content-between align-items-center" style="background-color: var(--surface-pearl);">
                    <div>
                        <div class="caption-strong">Total Calculated Capacity</div>
                        <div class="fine-print text-muted">Rows &times; Columns &times; Bench Seats</div>
                    </div>
                    <span id="calculated-capacity-badge" class="badge-apple badge-apple-primary fs-5 fw-bold">40 Seats</span>
                </div>

                <button type="submit" class="btn-apple-primary w-100 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Save Examination Room
                </button>
            </div>
        </div>

        <!-- Live Interactive Visual Layout Preview -->
        <div class="col-12 col-lg-7">
            <div class="apple-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="display-md mb-0">Live Layout Schematic</h3>
                        <div class="caption">Real-time visualization of floor plan and seat identifiers</div>
                    </div>
                </div>

                <div class="room-grid-canvas" id="room-layout-preview">
                    <!-- Updated live by initRoomBuilder in assets/js/app.js -->
                </div>
            </div>
        </div>
    </div>
</form>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
