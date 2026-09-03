-- Parikshya Sathi Database Schema
-- Compatible with SQLite and MySQL / MariaDB

-- 1. Academic Years
CREATE TABLE IF NOT EXISTS academic_years (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- 'active', 'archived'
    is_active INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Programs / Faculties
CREATE TABLE IF NOT EXISTS programs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    department VARCHAR(100) NULL,
    total_semesters INTEGER NOT NULL DEFAULT 8,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Students
CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    academic_year_id INTEGER NOT NULL,
    symbol_no VARCHAR(50) NOT NULL,
    roll_no VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    program_id INTEGER NOT NULL,
    semester INTEGER NOT NULL DEFAULT 1,
    section VARCHAR(10) NOT NULL DEFAULT 'A',
    gender VARCHAR(10) NOT NULL DEFAULT 'Other',
    phone VARCHAR(30) NULL,
    email VARCHAR(100) NULL,
    photo VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- 'active', 'inactive'
    is_deleted INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

CREATE INDEX IF NOT EXISTS idx_students_academic_year ON students(academic_year_id);
CREATE INDEX IF NOT EXISTS idx_students_symbol_no ON students(symbol_no);
CREATE INDEX IF NOT EXISTS idx_students_program_sem ON students(program_id, semester);

-- 4. Rooms
CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    academic_year_id INTEGER NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    building VARCHAR(100) NOT NULL DEFAULT 'Main Building',
    floor VARCHAR(50) NOT NULL DEFAULT 'Ground Floor',
    rows_count INTEGER NOT NULL DEFAULT 5,
    cols_count INTEGER NOT NULL DEFAULT 4,
    default_seats_per_bench INTEGER NOT NULL DEFAULT 2,
    capacity INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- 'active', 'inactive'
    is_deleted INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);

-- 5. Furniture (Desks / Benches in Room)
CREATE TABLE IF NOT EXISTS furniture (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'bench_2', -- 'single_desk', 'bench_2', 'bench_3', 'bench_4'
    row_num INTEGER NOT NULL,
    col_num INTEGER NOT NULL,
    label VARCHAR(50) NOT NULL,
    seat_count INTEGER NOT NULL DEFAULT 2,
    is_disabled INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- 6. Seats
CREATE TABLE IF NOT EXISTS seats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    furniture_id INTEGER NOT NULL,
    room_id INTEGER NOT NULL,
    seat_identifier VARCHAR(50) NOT NULL, -- e.g. "R1-C1-S1"
    seat_number_in_bench INTEGER NOT NULL DEFAULT 1,
    row_num INTEGER NOT NULL,
    col_num INTEGER NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- 'active', 'damaged', 'disabled'
    FOREIGN KEY (furniture_id) REFERENCES furniture(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_seats_room ON seats(room_id);

-- 7. Allocation Events
CREATE TABLE IF NOT EXISTS allocation_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    academic_year_id INTEGER NOT NULL,
    event_name VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    start_time VARCHAR(20) NOT NULL DEFAULT '09:00 AM',
    end_time VARCHAR(20) NOT NULL DEFAULT '12:00 PM',
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- 'draft', 'active', 'archived'
    active_version_id INTEGER NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);

-- 8. Allocation Event Participants & Subjects
CREATE TABLE IF NOT EXISTS allocation_event_participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    allocation_event_id INTEGER NOT NULL,
    program_id INTEGER NOT NULL,
    semester INTEGER NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    subject_code VARCHAR(50) NULL,
    FOREIGN KEY (allocation_event_id) REFERENCES allocation_events(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- 9. Allocation Event Rooms
CREATE TABLE IF NOT EXISTS allocation_event_rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    allocation_event_id INTEGER NOT NULL,
    room_id INTEGER NOT NULL,
    priority_order INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (allocation_event_id) REFERENCES allocation_events(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- 10. Allocation Versions
CREATE TABLE IF NOT EXISTS allocation_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    allocation_event_id INTEGER NOT NULL,
    version_number INTEGER NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- 'draft', 'active', 'archived'
    rule_config_json TEXT NULL,
    relaxation_level INTEGER NOT NULL DEFAULT 0,
    total_students INTEGER NOT NULL DEFAULT 0,
    allocated_count INTEGER NOT NULL DEFAULT 0,
    unallocated_count INTEGER NOT NULL DEFAULT 0,
    generation_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (allocation_event_id) REFERENCES allocation_events(id) ON DELETE CASCADE
);

-- 11. Allocations (Individual Student-Seat Mapping)
CREATE TABLE IF NOT EXISTS allocations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    allocation_version_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    room_id INTEGER NOT NULL,
    seat_id INTEGER NOT NULL,
    -- Historical snapshots for immutable archival
    symbol_no_snapshot VARCHAR(50) NOT NULL,
    student_name_snapshot VARCHAR(150) NOT NULL,
    program_name_snapshot VARCHAR(100) NOT NULL,
    program_code_snapshot VARCHAR(20) NOT NULL,
    semester_snapshot INTEGER NOT NULL,
    subject_name_snapshot VARCHAR(150) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
    FOREIGN KEY (allocation_version_id) REFERENCES allocation_versions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id)
);

CREATE INDEX IF NOT EXISTS idx_allocations_version ON allocations(allocation_version_id);
CREATE INDEX IF NOT EXISTS idx_allocations_room ON allocations(room_id);
CREATE INDEX IF NOT EXISTS idx_allocations_seat ON allocations(seat_id);
CREATE INDEX IF NOT EXISTS idx_allocations_student ON allocations(student_id);
