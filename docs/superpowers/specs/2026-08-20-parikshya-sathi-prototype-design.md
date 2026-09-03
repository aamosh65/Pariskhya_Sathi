# System Design & Architecture: Parikshya Sathi Prototype

**Document Date:** 2026-08-20  
**Status:** Approved for Implementation  
**Target Platform:** PHP 8+ / Procedural Backend with PDO (SQLite / MySQL), HTML5, CSS3, Bootstrap 5, Vanilla JavaScript  
**Design Standard:** Apple Human Interface System specified in `DESIGN.md`  

---

## 1. Overview & Objectives

Parikshya Sathi is an Exam Seating Allocation System built to automate student seat planning across multiple examination halls, programs, and semesters while producing institution-ready A4 print outputs (Seat Plans, Door Charts, Invigilator Attendance Sheets, Allocation Summaries) and preserving historical academic years.

This prototype provides a 100% complete, fully functional, interactive system implementing all MUST-HAVE and SHOULD-HAVE requirements from `prd.md` and styled strictly according to `DESIGN.md`.

---

## 2. Technology Stack & Runtime Architecture

* **Server Environment:** PHP 8+ (Procedural Architecture with clean modular helper functions). Runs with standard PHP CLI built-in server `php -S localhost:8000` or XAMPP Apache.
* **Database Layer:** PDO abstraction with dual-driver support:
  * **SQLite (Default):** Zero-configuration, file-backed database (`data/parikshya_sathi.sqlite3`) auto-initialized on first load with schema & synthetic seed data.
  * **MySQL (Optional toggle):** XAMPP MySQL support via `config/db.php`.
* **Frontend UI Framework:** HTML5 + Bootstrap 5 + Custom Design System CSS (`assets/css/design-system.css`).
* **Design Language (`DESIGN.md`):**
  * Action Blue `#0066cc` single interactive accent.
  * SF Pro / Inter typography with negative tracking on display headlines (`-0.28px` to `-0.374px`) and 17px body copy.
  * Pure white `#ffffff` canvas alternating with Parchment `#f5f5f7` and dark tiles `#272729`.
  * `rounded.pill` (9999px) for primary CTAs and chips; `rounded.lg` (18px) for cards; `transform: scale(0.95)` on active press.
* **Client-Side Dynamics:** Vanilla JavaScript for interactive room grid layouts, drag-and-drop seat swapping, CSV student import validation, live search/filtering, and print layout pagination.

---

## 3. Directory & File Structure

```text
c:\Parikshya-Sathi\
├── index.php                      # Executive Dashboard & System KPIs
├── config/
│   ├── db.php                     # PDO connection & schema auto-migration
│   └── schema.sql                 # SQL table definitions
├── includes/
│   ├── header.php                 # Global black nav (44px) + Frosted sub-nav (52px)
│   ├── footer.php                 # Parchment footer (64px padding) & modal containers
│   └── functions.php              # Shared procedural helpers (queries, toasts, sanitize, formatters)
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css      # Bootstrap 5 grid & baseline utilities
│   │   └── design-system.css      # Apple Design Tokens & A4 Print CSS
│   └── js/
│       ├── bootstrap.bundle.min.js
│       └── app.js                 # Toast notifications, modals, drag-and-drop seat editor, search
├── pages/
│   ├── academic-years/
│   │   └── index.php              # Academic Year switcher, creator, and archival manager
│   ├── students/
│   │   ├── index.php              # Student directory with search, multi-filter, status toggles
│   │   ├── create.php             # Single student registration
│   │   ├── edit.php               # Student editor with soft-delete support
│   │   ├── import.php             # CSV/XLSX importer with live preview & row-level error validation
│   │   └── symbols.php            # Batch symbol number generator & manual override
│   ├── rooms/
│   │   ├── index.php              # Room catalog with capacity badges & building/floor filters
│   │   ├── create.php             # Room configurator (desk/bench matrix, seats-per-furniture, irregular grids)
│   │   ├── edit.php               # Room modifier with automatic capacity calculation
│   │   └── layout-visualizer.php  # Visual interactive room schematic
│   ├── allocations/
│   │   ├── index.php              # Allocation events dashboard
│   │   ├── create.php             # Allocation Event Wizard (programs, semesters, subjects, rooms, capacity check)
│   │   ├── generate.php           # Randomized allocation engine with rule constraints & relaxation
│   │   ├── editor.php             # Interactive seat plan editor (drag-to-swap student placement)
│   │   └── versions.php           # Allocation version history, compare & active version switcher
│   └── reports/
│       ├── seat-plan.php          # Visual room-wise Seat Plan (A4 print ready)
│       ├── door-chart.php         # Door Chart grouped by program/semester/symbol ranges
│       ├── attendance.php         # Invigilator Attendance Sheet with signature blanks & photo slot
│       ├── summary.php            # Allocation Summary & room utilization breakdown
│       └── export-excel.php       # CSV/Spreadsheet export endpoint
└── api/
    ├── allocate.php               # Engine AJAX runner with progress feedback
    ├── swap-seats.php             # Real-time manual seat assignment updater
    ├── validate-import.php        # Client-side student import parser & error checker
    └── update-symbol.php          # Inline symbol number updater
```

---

## 4. Database Schema Specification

1. **`academic_years`**: `id`, `name` (e.g. "2026/27"), `status` ('active', 'archived'), `created_at`, `updated_at`.
2. **`programs`**: `id`, `name`, `code` ("BCA", "BSc.CSIT", "BBM", "BBA"), `total_semesters`, `status`.
3. **`students`**: `id`, `academic_year_id`, `symbol_no` (unique per year), `roll_no`, `name`, `program_id`, `semester`, `section`, `gender`, `phone`, `email`, `status` ('active', 'inactive'), `is_deleted` (0/1).
4. **`rooms`**: `id`, `academic_year_id`, `room_name`, `building`, `floor`, `capacity`, `status`, `is_deleted`.
5. **`furniture`**: `id`, `room_id`, `type` ('single_desk', 'bench_2', 'bench_3', 'bench_4'), `row_num`, `col_num`, `label`, `seat_count`, `status`.
6. **`seats`**: `id`, `furniture_id`, `seat_identifier` (e.g. "R1-C1-S1"), `seat_number_in_bench`, `status` ('active', 'damaged', 'disabled').
7. **`allocation_events`**: `id`, `academic_year_id`, `event_name`, `event_date`, `start_time`, `end_time`, `status` ('draft', 'active', 'archived'), `notes`.
8. **`allocation_event_participants`**: `id`, `allocation_event_id`, `program_id`, `semester`, `subject_name`, `student_id`.
9. **`allocation_versions`**: `id`, `allocation_event_id`, `version_number`, `status` ('draft', 'active', 'archived'), `rule_config_json`, `relaxation_level`, `unallocated_count`, `created_at`.
10. **`allocations`**: `id`, `allocation_version_id`, `student_id`, `room_id`, `seat_id`, `symbol_no_snapshot`, `student_name_snapshot`, `program_snapshot`, `semester_snapshot`, `status`.

---

## 5. Allocation Engine & Rule Relaxation Specification

* **Algorithm Workflow:**
  1. Fetch all eligible students for selected programs/semesters/subjects.
  2. Fetch all selected room seats in physical spatial sequence (Room $\to$ Row $\to$ Column $\to$ Bench Seat).
  3. Validate Capacity: Total active seats $\ge$ Total participating students. If shortfall, flag failure with exact deficit numbers.
  4. Randomization: Seeded Fisher-Yates shuffle on student pool with stratification.
  5. **Constraint Rules Applied:**
     * **Rule 1 (Strict Program Separation):** Students of the same program/faculty must NOT sit on the same bench or directly adjacent seats.
     * **Rule 2 (Semester Interleaving):** Alternating semesters across consecutive seats.
     * **Rule 3 (Randomized Distribution):** Even distribution across available rooms.
  6. **Rule Relaxation Ladder (FR-10):**
     * *Level 0:* Strict all-rules enforced.
     * *Level 1:* Allow same program on different benches in the same column (relax column separation).
     * *Level 2:* Allow same program on multi-seat benches if different semesters.
     * *Level 3:* Partial/Draft allocation with explicit unallocated list and corrective suggestions.

---

## 6. Report & Print Layout Standards (A4 Optimization)

* Print stylesheet `@media print` eliminates navigation, sidebars, buttons, and URL footers.
* Exact dimensions configured for standard A4 (`210mm x 297mm`) with `12mm` margins.
* **Seat Plan:** Displays room layout grid with teacher podium at top, bench outlines, seat numbers, symbol numbers, and student names.
* **Door Chart:** Header with institution name, exam title, date/time, room number, followed by clean card blocks showing Program, Semester, Subject, and Symbol Number ranges (e.g. `BCA 2nd Sem: 260101 - 260145 (45 Students)`).
* **Attendance Sheet:** Official tabular roster with columns: `S.N.`, `Symbol No.`, `Student Name`, `Program & Sem`, `Subject`, `Student Signature`, `Invigilator Initials`, `Remarks`.
* **Summary Sheet:** Metric cards and comprehensive room-wise utilization matrix with percentage progress bars.

---

## 7. Verification Plan

1. **Academic Year & Student Import Test:** Upload synthetic 50-student CSV with intentional duplicates to verify preview, error highlighting, and batch commit.
2. **Room Layout Builder Test:** Create Hall 101 with 5 rows $\times$ 4 columns of 2-seater benches (40 seats) + irregular layout to verify real-time capacity calculation.
3. **Allocation Engine Test:** Run allocation with BCA 2nd Sem + BSc CSIT 2nd Sem students to verify random interleaving and zero duplicate seat assignments.
4. **Manual Seat Adjustment Test:** Drag student from Room A, Seat 1 to Room A, Seat 2, verify swap persistence and active version update.
5. **Print View Verification:** Open Print Previews for Seat Plan, Door Chart, and Attendance Sheet to verify clean A4 single/multi-page layouts.
