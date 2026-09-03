<?php
// Parikshya Sathi - Edit Room & Physical Layout
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$activeYear = get_active_academic_year();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();

if (!$room) {
    set_flash('danger', 'Room not found.');
    header('Location: ' . base_url('pages/rooms/index.php'));
    exit;
}

// Handle Soft Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $db->prepare("UPDATE rooms SET is_deleted = 1 WHERE id = ?")->execute([$id]);
    set_flash('info', "Room '{$room['room_name']}' deactivated (soft deleted). Historical seating plans remain intact.");
    header('Location: ' . base_url('pages/rooms/index.php'));
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomName = trim($_POST['room_name'] ?? '');
    $building = trim($_POST['building'] ?? 'Main Building');
    $floor = trim($_POST['floor'] ?? 'Ground Floor');
    $rowsCount = (int)($_POST['rows_count'] ?? $room['rows_count']);
    $colsCount = (int)($_POST['cols_count'] ?? $room['cols_count']);
    $seatsPerBench = (int)($_POST['default_seats_per_bench'] ?? $room['default_seats_per_bench']);

    if (empty($roomName)) $errors[] = 'Room name is required.';

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $totalCapacity = $rowsCount * $colsCount * $seatsPerBench;
            $stmtUp = $db->prepare("UPDATE rooms SET room_name = ?, building = ?, floor = ?, rows_count = ?, cols_count = ?, default_seats_per_bench = ?, capacity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtUp->execute([$roomName, $building, $floor, $rowsCount, $colsCount, $seatsPerBench, $totalCapacity, $id]);

            // Rebuild furniture and seats if dimensions changed
            if ($rowsCount !== (int)$room['rows_count'] || $colsCount !== (int)$room['cols_count'] || $seatsPerBench !== (int)$room['default_seats_per_bench']) {
                $db->prepare("DELETE FROM seats WHERE room_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM furniture WHERE room_id = ?")->execute([$id]);

                $stmtFurn = $db->prepare("INSERT INTO furniture (room_id, type, row_num, col_num, label, seat_count, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmtSeat = $db->prepare("INSERT INTO seats (furniture_id, room_id, seat_identifier, seat_number_in_bench, row_num, col_num, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");

                for ($r = 1; $r <= $rowsCount; $r++) {
                    for ($c = 1; $c <= $colsCount; $c++) {
                        $furnType = ($seatsPerBench === 1) ? 'single_desk' : ('bench_' . $seatsPerBench);
                        $label = "Desk R{$r}-C{$c}";
                        $stmtFurn->execute([$id, $furnType, $r, $c, $label, $seatsPerBench]);
                        $furnId = (int)$db->lastInsertId();

                        for ($s = 1; $s <= $seatsPerBench; $s++) {
                            $seatIdent = "R{$r}-C{$c}-S{$s}";
                            $stmtSeat->execute([$furnId, $id, $seatIdent, $s, $r, $c]);
                        }
                    }
                }
            }

            $db->commit();
            set_flash('success', "Room '{$roomName}' updated successfully.");
            header('Location: ' . base_url('pages/rooms/index.php'));
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Error updating room: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Room';
$pageHeading = 'Edit Examination Room';
$pageBadge = $room['room_name'];

require_once __DIR__ . '/../../includes/header.php';
?>

<form method="POST" action="" id="room-layout-form">
    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="apple-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="display-md mb-0">Room Specs</h3>
                        <div class="caption">Modify physical room properties</div>
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
                    <label class="form-label caption-strong">Room Name / Number <span class="text-danger">*</span></label>
                    <input type="text" name="room_name" class="form-control-apple" value="<?= htmlspecialchars($_POST['room_name'] ?? $room['room_name']) ?>" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label caption-strong">Building</label>
                        <input type="text" name="building" class="form-control-apple" value="<?= htmlspecialchars($_POST['building'] ?? $room['building']) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label caption-strong">Floor</label>
                        <input type="text" name="floor" class="form-control-apple" value="<?= htmlspecialchars($_POST['floor'] ?? $room['floor']) ?>">
                    </div>
                </div>

                <hr class="my-4">
                <h4 class="tagline mb-2">Grid Dimensions</h4>

                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <label class="form-label caption-strong">Rows</label>
                        <input type="number" name="rows_count" class="form-control-apple" value="<?= (int)($_POST['rows_count'] ?? $room['rows_count']) ?>" min="1" max="20" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label caption-strong">Columns</label>
                        <input type="number" name="cols_count" class="form-control-apple" value="<?= (int)($_POST['cols_count'] ?? $room['cols_count']) ?>" min="1" max="10" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label caption-strong">Per Bench</label>
                        <select name="default_seats_per_bench" class="form-select-apple">
                            <option value="1" <?= ((int)($_POST['default_seats_per_bench'] ?? $room['default_seats_per_bench']) === 1) ? 'selected' : '' ?>>1 (Single)</option>
                            <option value="2" <?= ((int)($_POST['default_seats_per_bench'] ?? $room['default_seats_per_bench']) === 2) ? 'selected' : '' ?>>2 Seater</option>
                            <option value="3" <?= ((int)($_POST['default_seats_per_bench'] ?? $room['default_seats_per_bench']) === 3) ? 'selected' : '' ?>>3 Seater</option>
                            <option value="4" <?= ((int)($_POST['default_seats_per_bench'] ?? $room['default_seats_per_bench']) === 4) ? 'selected' : '' ?>>4 Seater</option>
                        </select>
                    </div>
                </div>

                <div class="p-3 rounded-3 mb-4 d-flex justify-content-between align-items-center" style="background-color: var(--surface-pearl);">
                    <div>
                        <div class="caption-strong">Updated Capacity</div>
                        <div class="fine-print text-muted">Calculated available slots</div>
                    </div>
                    <span id="calculated-capacity-badge" class="badge-apple badge-apple-primary fs-5 fw-bold"><?= (int)$room['capacity'] ?> Seats</span>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">
                    <a href="?id=<?= $room['id'] ?>&action=delete" class="btn-apple-ghost text-danger" onclick="return confirm('Deactivate this room? Historical allocations remain safe.')">
                        <i class="bi bi-trash3"></i> Deactivate
                    </a>
                    <button type="submit" class="btn-apple-primary">Update Room Layout</button>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="apple-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="display-md mb-0">Visual Floor Plan</h3>
                        <div class="caption">Updated bench positions and seating layout</div>
                    </div>
                </div>

                <div class="room-grid-canvas" id="room-layout-preview">
                    <!-- Dynamic preview rendered by app.js -->
                </div>
            </div>
        </div>
    </div>
</form>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
