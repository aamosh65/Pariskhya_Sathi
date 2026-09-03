<?php
// Parikshya Sathi - Export Allocation to CSV / Spreadsheet
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$db = get_db();
$eventId = (int)($_GET['event_id'] ?? 1);

// Fetch Event Details
$stmt = $db->prepare("SELECT e.*, v.version_number, v.id as version_id 
    FROM allocation_events e 
    LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
    WHERE e.id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    die('Allocation event not found.');
}

$versionId = (int)($event['version_id'] ?? 0);

// Fetch All Allocations with Room and Seat details
$stmtAlloc = $db->prepare("SELECT 
    a.symbol_no_snapshot as symbol_no,
    st.roll_no,
    a.student_name_snapshot as student_name,
    a.program_code_snapshot as program_code,
    a.program_name_snapshot as program_name,
    a.semester_snapshot as semester,
    st.section,
    st.gender,
    a.subject_name_snapshot as subject_name,
    r.room_name,
    r.building,
    r.floor,
    s.seat_identifier,
    s.row_num,
    s.col_num,
    s.seat_number_in_bench
    FROM allocations a
    JOIN rooms r ON a.room_id = r.id
    JOIN seats s ON a.seat_id = s.id
    LEFT JOIN students st ON a.student_id = st.id
    WHERE a.allocation_version_id = ?
    ORDER BY r.id ASC, s.row_num ASC, s.col_num ASC, s.seat_number_in_bench ASC");
$stmtAlloc->execute([$versionId]);
$rows = $stmtAlloc->fetchAll();

// Set Headers for CSV Download
$filename = 'Seating_Allocation_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $event['event_name']) . '_v' . $event['version_number'] . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Output CSV Header
fputcsv($output, [
    'S.N.',
    'Exam Symbol No',
    'Class Roll No',
    'Student Name',
    'Program Code',
    'Program Name',
    'Semester',
    'Section',
    'Gender',
    'Subject / Paper',
    'Examination Hall',
    'Building',
    'Floor',
    'Seat Identifier',
    'Row',
    'Column',
    'Seat on Bench'
]);

$sn = 1;
foreach ($rows as $r) {
    fputcsv($output, [
        $sn++,
        $r['symbol_no'],
        $r['roll_no'] ?: '—',
        $r['student_name'],
        $r['program_code'],
        $r['program_name'],
        'Semester ' . $r['semester'],
        $r['section'] ?: 'A',
        $r['gender'] ?: 'Other',
        $r['subject_name'] ?: 'Core Paper',
        $r['room_name'],
        $r['building'],
        $r['floor'],
        $r['seat_identifier'],
        $r['row_num'],
        $r['col_num'],
        $r['seat_number_in_bench']
    ]);
}

fclose($output);
exit;
