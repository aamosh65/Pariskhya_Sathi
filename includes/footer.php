<?php
// Parikshya Sathi - Footer Layout (Apple Human Interface)
declare(strict_types=1);
?>
</main>

<footer class="footer mt-5 py-5 no-print" style="background-color: var(--canvas-parchment); border-top: 1px solid var(--hairline);">
    <div class="container-fluid px-lg-4" style="max-width: 1440px;">
        <div class="row gy-4 mb-4">
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    <span class="body-strong">Parikshya Sathi</span>
                </div>
                <p class="fine-print text-muted mb-0">
                    Automated examination seating allocation system designed for multi-program, multi-hall academic institutions with customizable seating rules, instant draft relaxation, and A4 print documentation.
                </p>
            </div>
            <div class="col-6 col-md-2">
                <div class="caption-strong mb-2">Academic Data</div>
                <ul class="list-unstyled fine-print d-flex flex-column gap-1">
                    <li><a href="<?= base_url('pages/students/index.php') ?>" class="text-secondary text-decoration-none">Student Master</a></li>
                    <li><a href="<?= base_url('pages/students/import.php') ?>" class="text-secondary text-decoration-none">Bulk CSV Import</a></li>
                    <li><a href="<?= base_url('pages/students/symbols.php') ?>" class="text-secondary text-decoration-none">Symbol Numbers</a></li>
                    <li><a href="<?= base_url('pages/academic-years/index.php') ?>" class="text-secondary text-decoration-none">Academic Years</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-2">
                <div class="caption-strong mb-2">Halls & Layouts</div>
                <ul class="list-unstyled fine-print d-flex flex-column gap-1">
                    <li><a href="<?= base_url('pages/rooms/index.php') ?>" class="text-secondary text-decoration-none">Examination Rooms</a></li>
                    <li><a href="<?= base_url('pages/rooms/create.php') ?>" class="text-secondary text-decoration-none">Add New Room</a></li>
                    <li><a href="<?= base_url('pages/rooms/layout-visualizer.php') ?>" class="text-secondary text-decoration-none">Layout Visualizer</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-2">
                <div class="caption-strong mb-2">Allocations</div>
                <ul class="list-unstyled fine-print d-flex flex-column gap-1">
                    <li><a href="<?= base_url('pages/allocations/index.php') ?>" class="text-secondary text-decoration-none">Allocation Events</a></li>
                    <li><a href="<?= base_url('pages/allocations/create.php') ?>" class="text-secondary text-decoration-none">Generate Allocation</a></li>
                    <li><a href="<?= base_url('pages/allocations/editor.php?id=1') ?>" class="text-secondary text-decoration-none">Interactive Editor</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-2">
                <div class="caption-strong mb-2">Print & Reports</div>
                <ul class="list-unstyled fine-print d-flex flex-column gap-1">
                    <li><a href="<?= base_url('pages/reports/door-chart.php?event_id=1') ?>" class="text-secondary text-decoration-none">Door Charts</a></li>
                    <li><a href="<?= base_url('pages/reports/seat-plan.php?event_id=1') ?>" class="text-secondary text-decoration-none">Room Seat Plans</a></li>
                    <li><a href="<?= base_url('pages/reports/attendance.php?event_id=1') ?>" class="text-secondary text-decoration-none">Attendance Sheets</a></li>
                    <li><a href="<?= base_url('pages/reports/summary.php?event_id=1') ?>" class="text-secondary text-decoration-none">Allocation Summary</a></li>
                </ul>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pt-3 border-top border-secondary-subtle fine-print text-muted">
            <div>&copy; 2026 Parikshya Sathi — Examination Seating Allocation System. Version 1.0 MVP.</div>
            <div class="mt-2 mt-md-0">Apple Human Interface Standard &bull; Responsive &bull; A4 Ready</div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Application Script -->
<script src="<?= base_url('assets/js/app.js') ?>"></script>

</body>
</html>
