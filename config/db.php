<?php
// Parikshya Sathi - Database Connection and Initialization Manager
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DB_DRIVER = 'sqlite'; // 'sqlite' or 'mysql'
const DB_SQLITE_PATH = __DIR__ . '/../data/parikshya_sathi.sqlite3';
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'parikshya_sathi';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Returns a singleton PDO instance.
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $dir = dirname(DB_SQLITE_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $dsn = 'sqlite:' . DB_SQLITE_PATH;
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA foreign_keys = ON;");
        } else {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        
        // Auto-initialize tables and seed data if needed
        initialize_database($pdo);

    } catch (PDOException $e) {
        die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border-radius:12px;background:#fff0f0;color:#c00;border:1px solid #fcc;'>
            <h3>Database Connection Error</h3>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p>Ensure PHP PDO extension is enabled.</p>
        </div>");
    }

    return $pdo;
}

/**
 * Auto-initializes schema and baseline masters if tables do not exist.
 */
function initialize_database(PDO $pdo): void {
    // Check if academic_years table exists
    $tableExists = false;
    try {
        $check = $pdo->query("SELECT 1 FROM academic_years LIMIT 1");
        if ($check !== false) {
            $tableExists = true;
        }
    } catch (Exception $e) {
        $tableExists = false;
    }

    if (!$tableExists) {
        $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($schemaSql);
        seed_master_data($pdo);
    }
}

/**
 * Seed baseline reference tables (Default active Academic Year).
 */
function seed_master_data(PDO $pdo): void {
    $pdo->beginTransaction();
    try {
        // Default Active Academic Year
        $pdo->exec("INSERT INTO academic_years (id, name, start_date, end_date, status, is_active) VALUES 
            (1, '2026/27', '2026-01-01', '2026-12-31', 'active', 1)");

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Seed realistic synthetic dataset for testing all PRD features.
 */
function seed_synthetic_data(PDO $pdo): void {
    $pdo->beginTransaction();
    try {
        // 1. Academic Years
        $pdo->exec("INSERT INTO academic_years (id, name, start_date, end_date, status, is_active) VALUES 
            (1, '2026/27', '2026-01-01', '2026-12-31', 'active', 1),
            (2, '2025/26', '2025-01-01', '2025-12-31', 'archived', 0)");

        // 2. Programs
        $pdo->exec("INSERT INTO programs (id, name, code, department, total_semesters, status) VALUES 
            (1, 'Bachelor of Computer Application', 'BCA', 'Computer Science & IT', 8, 'active'),
            (2, 'B.Sc. Computer Science & IT', 'BSc.CSIT', 'Computer Science & IT', 8, 'active'),
            (3, 'Bachelor of Business Management', 'BBM', 'Faculty of Management', 8, 'active'),
            (4, 'Bachelor of Business Administration', 'BBA', 'Faculty of Management', 8, 'active')");

        // 3. Rooms & Physical Layouts
        // Room 1: Examination Hall A (Main Building, 1st Floor) - 5 rows x 4 cols of 2-seater benches (40 seats)
        // Room 2: Examination Hall B (Main Building, 2nd Floor) - 4 rows x 4 cols of 2-seater benches (32 seats)
        // Room 3: Room 101 (Annex Building, Ground Floor) - 4 rows x 3 cols of 2-seater benches (24 seats)
        // Room 4: Room 102 (Annex Building, 1st Floor) - 4 rows x 3 cols of 2-seater benches (24 seats)
        // Room 5: Seminar Hall (Library Block, 3rd Floor) - 6 rows x 4 cols of 2-seater benches (48 seats)
        
        $roomsData = [
            ['id' => 1, 'name' => 'Examination Hall A', 'building' => 'Main Building', 'floor' => '1st Floor', 'rows' => 5, 'cols' => 4, 'seats_per_bench' => 2],
            ['id' => 2, 'name' => 'Examination Hall B', 'building' => 'Main Building', 'floor' => '2nd Floor', 'rows' => 4, 'cols' => 4, 'seats_per_bench' => 2],
            ['id' => 3, 'name' => 'Room 101', 'building' => 'Annex Wing', 'floor' => 'Ground Floor', 'rows' => 4, 'cols' => 3, 'seats_per_bench' => 2],
            ['id' => 4, 'name' => 'Room 102', 'building' => 'Annex Wing', 'floor' => '1st Floor', 'rows' => 4, 'cols' => 3, 'seats_per_bench' => 2],
            ['id' => 5, 'name' => 'Seminar Hall', 'building' => 'Library Block', 'floor' => '3rd Floor', 'rows' => 6, 'cols' => 4, 'seats_per_bench' => 2],
        ];

        $stmtRoom = $pdo->prepare("INSERT INTO rooms (id, academic_year_id, room_name, building, floor, rows_count, cols_count, default_seats_per_bench, capacity, status) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmtFurniture = $pdo->prepare("INSERT INTO furniture (id, room_id, type, row_num, col_num, label, seat_count, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmtSeat = $pdo->prepare("INSERT INTO seats (id, furniture_id, room_id, seat_identifier, seat_number_in_bench, row_num, col_num, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");

        $furnitureIdCounter = 1;
        $seatIdCounter = 1;

        foreach ($roomsData as $r) {
            $totalRoomSeats = $r['rows'] * $r['cols'] * $r['seats_per_bench'];
            $stmtRoom->execute([$r['id'], $r['name'], $r['building'], $r['floor'], $r['rows'], $r['cols'], $r['seats_per_bench'], $totalRoomSeats]);

            for ($row = 1; $row <= $r['rows']; $row++) {
                for ($col = 1; $col <= $r['cols']; $col++) {
                    $furnitureType = ($r['seats_per_bench'] === 1) ? 'single_desk' : ('bench_' . $r['seats_per_bench']);
                    $benchLabel = sprintf('Desk R%d-C%d', $row, $col);
                    $fId = $furnitureIdCounter++;
                    $stmtFurniture->execute([$fId, $r['id'], $furnitureType, $row, $col, $benchLabel, $r['seats_per_bench']]);

                    for ($s = 1; $s <= $r['seats_per_bench']; $s++) {
                        $seatIdent = sprintf('R%d-C%d-S%d', $row, $col, $s);
                        $sId = $seatIdCounter++;
                        $stmtSeat->execute([$sId, $fId, $r['id'], $seatIdent, $s, $row, $col]);
                    }
                }
            }
        }

        // 4. Students (Generate ~120 students across BCA, BSc.CSIT, BBM, BBA)
        $firstNamesMale = ['Aarav', 'Bibek', 'Rohan', 'Dipesh', 'Suman', 'Bikash', 'Prashant', 'Kiran', 'Niroj', 'Anil', 'Sagar', 'Prakash', 'Suraj', 'Manish', 'Roshan', 'Bijay'];
        $firstNamesFemale = ['Aayusha', 'Puja', 'Shreya', 'Anjali', 'Sneha', 'Pratima', 'Sujata', 'Kritika', 'Manisha', 'Pabitra', 'Smarika', 'Binita', 'Neha', 'Roshani'];
        $lastNames = ['Sharma', 'Shrestha', 'Adhikari', 'Thapa', 'Karki', 'Bhandari', 'Giri', 'Basnet', 'Maharjan', 'Tamang', 'Rai', 'Gurung', 'Pandey', 'Khadka', 'Oli', 'Bhattarai'];

        $stmtStudent = $pdo->prepare("INSERT INTO students (academic_year_id, symbol_no, roll_no, name, program_id, semester, section, gender, phone, email, status) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

        $studentList = [];
        $programsConfig = [
            ['prog_id' => 1, 'sem' => 2, 'prefix' => '2601', 'count' => 45], // BCA 2nd Sem
            ['prog_id' => 2, 'sem' => 2, 'prefix' => '2602', 'count' => 35], // BSc.CSIT 2nd Sem
            ['prog_id' => 3, 'sem' => 4, 'prefix' => '2603', 'count' => 30], // BBM 4th Sem
            ['prog_id' => 4, 'sem' => 4, 'prefix' => '2604', 'count' => 20], // BBA 4th Sem
        ];

        $studentIdAcc = 1;
        foreach ($programsConfig as $pc) {
            for ($i = 1; $i <= $pc['count']; $i++) {
                $isFemale = ($i % 3 === 0);
                $fn = $isFemale ? $firstNamesFemale[array_rand($firstNamesFemale)] : $firstNamesMale[array_rand($firstNamesMale)];
                $ln = $lastNames[array_rand($lastNames)];
                $name = $fn . ' ' . $ln;
                $gender = $isFemale ? 'Female' : 'Male';
                $symbolNo = sprintf('%s%02d', $pc['prefix'], $i);
                $rollNo = sprintf('%d', $i);
                $section = ($i % 2 === 0) ? 'B' : 'A';
                $phone = '98' . rand(10000000, 99999999);
                $email = strtolower($fn . '.' . $ln . rand(10, 99) . '@institution.edu');

                $stmtStudent->execute([$symbolNo, $rollNo, $name, $pc['prog_id'], $pc['sem'], $section, $gender, $phone, $email]);

                $studentList[] = [
                    'id' => $studentIdAcc++,
                    'symbol_no' => $symbolNo,
                    'roll_no' => $rollNo,
                    'name' => $name,
                    'program_id' => $pc['prog_id'],
                    'semester' => $pc['sem'],
                    'section' => $section
                ];
            }
        }

        // 5. Seed an Initial Completed Allocation Event
        // Event: "Mid-Term Examination 2026 - Morning Session"
        // Participants: BCA 2nd Sem (Data Structures) + BSc.CSIT 2nd Sem (Object Oriented Programming) = 45 + 35 = 80 students
        // Rooms used: Examination Hall A (40 seats) + Examination Hall B (32 seats) + Room 101 (24 seats) -> Total 96 seats available

        $pdo->exec("INSERT INTO allocation_events (id, academic_year_id, event_name, event_date, start_time, end_time, status, notes) VALUES 
            (1, 1, 'Mid-Term Examination 2026 - Morning Session', '2026-09-15', '09:00 AM', '12:00 PM', 'active', 'Regular Mid-Term Examination for BCA and BSc.CSIT 2nd Semester students.')");

        $pdo->exec("INSERT INTO allocation_event_participants (allocation_event_id, program_id, semester, subject_name, subject_code) VALUES 
            (1, 1, 2, 'Data Structures & Algorithms', 'CACS151'),
            (1, 2, 2, 'Object Oriented Programming (C++)', 'CSC161')");

        $pdo->exec("INSERT INTO allocation_event_rooms (allocation_event_id, room_id, priority_order) VALUES 
            (1, 1, 1),
            (1, 2, 2),
            (1, 3, 3)");

        // 6. Allocation Version 1
        $pdo->exec("INSERT INTO allocation_versions (id, allocation_event_id, version_number, status, rule_config_json, relaxation_level, total_students, allocated_count, unallocated_count, generation_notes) VALUES 
            (1, 1, 1, 'active', '{\"program_separation\":true,\"semester_interleaving\":true,\"randomize\":true}', 0, 80, 80, 0, 'Auto-generated with strict program separation and alternating bench seats.')");

        $pdo->exec("UPDATE allocation_events SET active_version_id = 1 WHERE id = 1");

        // 7. Seed Initial Allocation mappings (interleave BCA & BSc.CSIT across Hall A & Hall B)
        $bcaStudents = array_filter($studentList, fn($s) => $s['program_id'] === 1 && $s['semester'] === 2);
        $csitStudents = array_filter($studentList, fn($s) => $s['program_id'] === 2 && $s['semester'] === 2);
        shuffle($bcaStudents);
        shuffle($csitStudents);

        // Fetch seats of Hall A (Room 1) and Hall B (Room 2) and Room 101 (Room 3)
        $seatQuery = $pdo->query("SELECT id, room_id, seat_identifier, row_num, col_num, seat_number_in_bench FROM seats WHERE room_id IN (1, 2, 3) ORDER BY room_id ASC, row_num ASC, col_num ASC, seat_number_in_bench ASC");
        $availableSeats = $seatQuery->fetchAll();

        $stmtAlloc = $pdo->prepare("INSERT INTO allocations (allocation_version_id, student_id, room_id, seat_id, symbol_no_snapshot, student_name_snapshot, program_name_snapshot, program_code_snapshot, semester_snapshot, subject_name_snapshot, status) VALUES 
            (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");

        $seatIndex = 0;
        // Interleave by taking from BCA and CSIT alternately
        while (!empty($bcaStudents) || !empty($csitStudents)) {
            if (!empty($bcaStudents) && isset($availableSeats[$seatIndex])) {
                $st = array_shift($bcaStudents);
                $seat = $availableSeats[$seatIndex++];
                $stmtAlloc->execute([$st['id'], $seat['room_id'], $seat['id'], $st['symbol_no'], $st['name'], 'Bachelor of Computer Application', 'BCA', 2, 'Data Structures & Algorithms']);
            }
            if (!empty($csitStudents) && isset($availableSeats[$seatIndex])) {
                $st = array_shift($csitStudents);
                $seat = $availableSeats[$seatIndex++];
                $stmtAlloc->execute([$st['id'], $seat['room_id'], $seat['id'], $st['symbol_no'], $st['name'], 'B.Sc. Computer Science & IT', 'BSc.CSIT', 2, 'Object Oriented Programming (C++)']);
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get active academic year row.
 */
function get_active_academic_year(): array {
    $db = get_db();
    $yearId = $_SESSION['active_academic_year_id'] ?? null;
    if ($yearId) {
        $stmt = $db->prepare("SELECT * FROM academic_years WHERE id = ?");
        $stmt->execute([$yearId]);
        $year = $stmt->fetch();
        if ($year) return $year;
    }
    
    // Default active
    $year = $db->query("SELECT * FROM academic_years WHERE is_active = 1 LIMIT 1")->fetch();
    if (!$year) {
        $year = $db->query("SELECT * FROM academic_years ORDER BY id DESC LIMIT 1")->fetch();
    }
    if ($year) {
        $_SESSION['active_academic_year_id'] = $year['id'];
    }
    return $year ?: ['id' => 1, 'name' => '2026/27', 'status' => 'active'];
}
