# Product Requirements Document (PRD)
## Parikshya Sathi — Exam Seating Allocation System

**Document Status:** Draft / Authoritative MVP Specification  
**Version:** 1.0  
**Date:** 20 August 2026  
**Project Type:** Individual BCA Academic Project  
**Target:** Generic academic institutions; future commercial expansion  
**MVP Delivery Target:** Approximately 4 weeks

---

# 1. Executive Summary

## 1.1 Product Purpose

Parikshya Sathi is a web-based **Exam Seating Allocation System** designed to automate the process of assigning students to examination rooms and physical seats.

The system is intentionally narrower than a complete examination-management platform. It does not manage question papers, marks, results, fees, online examinations, or general academic administration.

The core product workflow is:

1. Establish an academic year.
2. Load student data.
3. Assign or generate student symbol numbers.
4. Configure available examination rooms and their physical layouts.
5. Create a lightweight allocation event describing when and which students/papers require seating.
6. Automatically generate a randomized seating arrangement according to configurable seating rules.
7. Allow an administrator to manually modify the generated arrangement.
8. Generate printable A4 outputs:
   - Seat Plan
   - Door Chart
   - Room-specific Attendance Sheet
   - Allocation Summary
9. Preserve historical academic-year and allocation data.

## 1.2 Target Users

### Primary User
**Administrator / Examination Coordinator**

The administrator owns the academic data, room information, allocation process, generated plans, and printed outputs.

### Secondary Users
There are no direct secondary users in the MVP. Teachers/invigilators and students are consumers of printed outputs rather than application users.

### Future Users
Future commercial versions may support:

- Multiple administrators
- Examination coordinators
- Invigilators
- Students
- Institution-level administrators

## 1.3 Expected Outcomes

The system should reduce the manual effort required to prepare examination seating plans and reduce allocation errors by:

- Maintaining reusable academic and room data.
- Automatically assigning students to available seats.
- Supporting multiple programs, faculties, semesters, and subjects in a single allocation event.
- Handling different physical room layouts.
- Producing ready-to-print administrative documents.
- Preserving historical allocation records.

## 1.4 MVP Success Criteria

The MVP is successful when an administrator can:

- Load a complete academic year's student data.
- Configure rooms and their physical seats.
- Create an allocation event involving students from multiple programs/semesters.
- Automatically generate a randomized allocation.
- Handle insufficient-capacity or rule-conflict situations without data corruption.
- Manually adjust the allocation.
- Regenerate and retain allocation versions.
- Produce correct room-wise seat plans, door charts, attendance sheets, and summaries.
- Preserve previous academic-year records.
- Deploy and operate the system in a normal web browser.

---

# 2. Problem Statement & Opportunity

## 2.1 Problem Statement

Academic institutions may have hundreds or thousands of students from different programs, semesters, sections, and faculties taking examinations at the same time.

Manually preparing examination seating plans requires staff to:

- Collect student information.
- Identify students participating in each examination.
- Determine available room capacity.
- Arrange students across rooms.
- Apply separation rules.
- Avoid duplicate or missing assignments.
- Prepare room-wise attendance sheets.
- Prepare door charts.
- Recalculate arrangements when rooms or students change.
- Recreate documents for every examination.

This becomes increasingly error-prone as the number of students and rooms increases.

## 2.2 Opportunity

Parikshya Sathi can transform the recurring seating process into a reusable workflow.

Once the academic-year data and room structures are loaded, subsequent allocation events can be generated from existing data rather than manually rebuilding the seating plan.

The future commercial opportunity is to generalize the system for different institutions with different room layouts, seating structures, and allocation rules.

---

# 3. Goals & Objectives

## 3.1 Product Goals

### Goal 1 — Automate seating allocation

Generate a seating arrangement for up to **5,000 students in one allocation event**.

**KPI:** At least 99% of valid allocation requests should complete successfully when sufficient seats and satisfiable rules exist.

### Goal 2 — Reduce manual preparation

Allow an administrator to generate the initial allocation without manually assigning every student to a seat.

**KPI:** The system should perform the initial allocation automatically; manual work should be limited to reviewing or modifying exceptions.

### Goal 3 — Prevent invalid core allocations

The system must prevent duplicate assignment of a student or physical seat within an active allocation version.

**KPI:** Zero duplicate student-seat assignments in a successfully generated allocation.

### Goal 4 — Produce usable documents

Generate A4-friendly output for each required report.

**KPI:** Seat plans, door charts, attendance sheets, and summaries must be printable without requiring manual layout correction under supported browser print settings.

### Goal 5 — Preserve academic history

Historical allocation records must remain available after an academic year is archived.

**KPI:** Historical allocations remain viewable after the academic year is no longer active.

## 3.2 Project Objectives

Within approximately four weeks:

- Complete the relational database.
- Implement student and room data management.
- Implement allocation generation.
- Implement configurable seating-rule architecture.
- Implement manual allocation editing.
- Implement reports.
- Perform synthetic-data testing.
- Deploy the application.
- Prepare documentation and presentation materials.

---

# 4. User Personas & Stakeholders

## 4.1 Persona: Administrator

**Role:** Examination/data administrator

**Goals:**
- Maintain student data.
- Maintain room configurations.
- Create allocation events.
- Generate seating plans.
- Resolve allocation problems.
- Print required documents.

**Constraints:**
- May not be technically advanced.
- Needs clear feedback.
- Should not need to understand database operations.
- Primarily uses a desktop/laptop.

## 4.2 Persona: Student — Future

Students may eventually access the system to search their symbol number and view their assigned room/seat.

**MVP status:** Not accessible.

## 4.3 Persona: Invigilator — Future/Indirect MVP User

Invigilators consume printed attendance sheets and seating plans.

**MVP application access:** Not required.

## 4.4 Stakeholders

| Stakeholder | Interest |
|---|---|
| Project developer | Design, development, testing, deployment |
| Project coordinator | Academic evaluation and validation |
| Institution administrator | Operational use |
| Invigilators | Accurate room attendance documents |
| Students | Correct room/seat assignment in future versions |

---

# 5. Scope

## 5.1 In Scope

- Academic-year management.
- Student data management.
- Student CSV/XLSX import.
- Student validation.
- Symbol-number generation and override.
- Roll-number storage.
- Program/faculty/semester/section data.
- Room management.
- Building/floor information.
- Physical room layout management.
- Desk/bench/furniture configuration.
- Single-seat and multi-seat furniture.
- Irregular room layouts.
- Automatic capacity calculation.
- Allocation events.
- Multiple programs/faculties in one allocation.
- Multiple semesters in one allocation.
- Multiple papers/subjects in one allocation event.
- Randomized allocation.
- Configurable seating-rule architecture.
- Rule relaxation when strict allocation is impossible.
- Draft allocations.
- Manual seat movement.
- Allocation regeneration.
- Allocation versioning.
- Historical allocation preservation.
- Search and filtering.
- Dashboard.
- Seat plans.
- Door charts.
- Room-specific attendance sheets.
- Allocation summaries.
- Browser print / Save as PDF.
- Excel export.
- Toast notifications.
- Soft deletion.
- Basic web security.

## 5.2 Out of Scope — MVP

- Online examinations.
- Question-paper management.
- Marks/results management.
- Fee management.
- Teacher management.
- Full examination-management lifecycle.
- Student login.
- Student-facing portal.
- SMS notifications.
- Email notifications.
- Authentication.
- Role-based access control.
- Audit logging.
- Payment.
- Mobile application.

---

# 6. Functional Requirements

Priority conventions:

- **MUST HAVE:** Required for MVP completion.
- **SHOULD HAVE:** Important but can be reduced if schedule becomes constrained.
- **COULD HAVE:** Useful enhancement if time permits.
- **WON'T HAVE:** Explicitly excluded from MVP.

---

## FR-01 Academic Year Management

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to create and manage academic years so that student, room, and allocation data remain organized and historical records are preserved.

### Requirements

- Create an academic year.
- Mark one academic year as active.
- Archive completed academic years.
- Associate students, rooms, and allocation events with an academic year.
- Prevent accidental mixing of records between academic years.

### Acceptance Criteria

- Given an active academic year, new student records are associated with it.
- A historical academic year remains accessible after archival.
- Historical allocations are not deleted when a new academic year starts.
- The system clearly identifies the active academic year.

---

## FR-02 Student Management

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to create, update, search, filter, and deactivate students so that the system has accurate academic data for allocation.

### Required Student Fields

- Internal student ID.
- Symbol number.
- Roll number.
- Name.
- Program/faculty.
- Semester/year.
- Section.
- Gender.
- Phone.
- Email.
- Status.
- Photo.
- Academic year.

### Acceptance Criteria

- Symbol number is unique within an academic year.
- A student cannot be duplicated by symbol number within the same academic year.
- Required fields are validated before saving.
- Deactivated students are not automatically included in new allocations.
- Historical allocations remain intact when a student is soft-deleted.

---

## FR-03 Student Import

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to import student data from CSV or XLSX files so that thousands of records can be loaded efficiently.

### Workflow

```text
Upload
  ↓
Parse
  ↓
Validate
  ↓
Preview
  ↓
Resolve errors
  ↓
Confirm
  ↓
Import
```

### Requirements

- Support CSV.
- Support XLSX.
- Validate required fields.
- Detect duplicate symbol numbers.
- Detect duplicate student records.
- Detect invalid academic references.
- Show row-level errors.
- Existing matching students are updated.
- Valid rows may be imported even when other rows fail.

### Acceptance Criteria

- Invalid rows are clearly identified.
- The administrator can review errors before confirmation.
- A duplicate existing student is updated according to the approved matching rule.
- A failed row does not corrupt successful imports.
- Import results show counts for successful, updated, skipped, and failed records.

---

## FR-04 Symbol Number Management

**Priority:** MUST HAVE

### User Story

> As an administrator, I want the system to generate systematic symbol numbers so that every student has a consistent examination identifier throughout the academic year.

### Requirements

- Automatically generate missing symbol numbers.
- Allow administrator override.
- Enforce uniqueness within an academic year.
- Preserve historical symbol numbers in historical allocations.
- Symbol numbering may restart in a new academic year.

### Acceptance Criteria

- No two active students in the same academic year have the same symbol number.
- An administrator can edit a generated symbol number before final confirmation.
- A symbol number from a historical year remains unchanged in historical reports.

---

## FR-05 Room Management

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to configure examination rooms so that the allocation engine knows the available physical seating capacity.

### Room Fields

- Room ID.
- Room number/name.
- Building.
- Floor.
- Layout metadata.
- Academic year.
- Active/inactive state.

### Requirements

- Create rooms manually.
- Edit rooms.
- Soft-delete/deactivate rooms.
- Configure furniture.
- Configure number of seats per furniture item.
- Support one-seat and multi-seat furniture.
- Automatically calculate capacity.

### Acceptance Criteria

- Room capacity equals the total number of active seats.
- A room cannot contain duplicate physical seat identifiers.
- Soft-deleting a room does not delete historical allocations.

---

## FR-06 Physical Room Layout

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to model the physical arrangement of desks and seats so that generated seating plans reflect the real examination room.

### Requirements

- Support benches and individual desks.
- Support different seat counts per furniture item.
- Support irregular layouts.
- Assign internal identifiers to furniture and seats.
- Preserve physical ordering for allocation and reports.

### Acceptance Criteria

- An irregular layout can be represented.
- Every active physical seat has a unique identifier within its room.
- Calculated room capacity updates when seats are added or removed.

---

## FR-07 Allocation Event

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to create an allocation event so that the system knows which students require seats at a particular examination session.

### Minimum Event Data

- Event ID.
- Academic year.
- Date.
- Start time.
- End time.
- Participating students.
- Associated subject/paper information.
- Status.

### Acceptance Criteria

- An allocation event can include students from multiple programs.
- An allocation event can include multiple semesters.
- An allocation event can include multiple subjects/papers.
- Only eligible active students can be allocated.

---

## FR-08 Automatic Seat Allocation

**Priority:** MUST HAVE

### User Story

> As an administrator, I want the system to automatically assign students to available seats so that I do not have to manually create the entire seating plan.

### Requirements

- Determine available capacity.
- Randomize eligible students.
- Apply active seating rules.
- Assign students to available physical seats.
- Avoid duplicate student assignments.
- Avoid duplicate seat assignments.
- Support multiple rooms.
- Support multiple programs and semesters.
- Produce an allocation result or draft.

### Acceptance Criteria

- Every successfully allocated student has exactly one seat.
- No seat contains more students than its configured capacity.
- A successfully generated allocation contains no duplicate student-seat assignment.
- Students are distributed across available rooms according to the configured allocation strategy.
- Randomized generation can produce a different arrangement on subsequent generation attempts.

---

## FR-09 Seating Rules

**Priority:** MUST HAVE

### User Story

> As an administrator, I want seating rules to influence automatic allocation so that students can be separated according to institutional examination policies.

### Current Specification Status

**The exact separation logic is intentionally not finalized.**

The allocation engine must be designed so rules can be changed without rewriting the core student, room, or allocation data model.

Potential future rule dimensions include:

- Program.
- Semester.
- Program + semester.
- Section.
- Subject.

### Acceptance Criteria

- Allocation rules are applied during automatic generation.
- Rule evaluation does not affect manual movement.
- The architecture allows additional rules to be added later.
- The exact institutional rule set must be finalized before production deployment.

---

## FR-10 Rule Relaxation

**Priority:** MUST HAVE

### User Story

> As an administrator, I want the system to relax allocation constraints when strict rules make allocation impossible so that students can still be seated when a perfect arrangement cannot be produced.

### Requirements

- Attempt strict allocation first.
- Detect unsatisfied constraints.
- Relax lower-priority rules according to configured order.
- Retry allocation.
- If still impossible, create a draft/partial allocation.
- Explain the failure.

### Acceptance Criteria

- The engine does not silently discard students.
- Unallocated students are explicitly listed.
- The administrator is told why allocation could not be completed.
- The administrator receives suggested corrective actions.

---

## FR-11 Manual Allocation Editing

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to manually move students between seats so that I can make final adjustments after automatic allocation.

### Requirements

- Move a student to another available seat.
- Swap students where appropriate.
- Preserve allocation version.
- Do not block manual changes because of automatic seating-rule violations.

### Acceptance Criteria

- A student cannot occupy two seats simultaneously.
- A physical seat cannot contain an invalid number of students.
- Manual changes are persisted.
- The system does not reject a manual movement solely because it violates an automatic separation rule.

---

## FR-12 Allocation Regeneration

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to regenerate an allocation so that I can obtain a different randomized seating arrangement.

### Acceptance Criteria

- Each regeneration creates a new allocation version.
- The previous version is retained.
- The administrator can identify the active version.
- Regeneration does not modify historical completed allocation events.

---

## FR-13 Allocation Versioning

**Priority:** SHOULD HAVE

### User Story

> As an administrator, I want previous generated versions preserved so that I can compare or restore an earlier arrangement.

### Requirements

- Version number.
- Creation timestamp.
- Allocation status.
- Active version marker.

### Acceptance Criteria

- Regeneration creates a new version.
- Previous versions remain readable.
- One version is designated as active for final reporting.
- Historical versions cannot be accidentally overwritten.

---

## FR-14 Allocation Failure and Draft Handling

**Priority:** MUST HAVE

### User Story

> As an administrator, I want incomplete allocations saved as drafts so that I can fix the underlying problem rather than losing my work.

### Failure Information

The system should show:

- Total students.
- Available seats.
- Allocated students.
- Unallocated students.
- Relevant constraints.
- Likely causes.
- Suggested corrective actions.

### Acceptance Criteria

- A failed allocation does not corrupt the database.
- Partial results are clearly marked as draft.
- Unallocated students are identifiable.
- The administrator can return to the draft after correcting rooms/data/rules.

---

## FR-15 Search and Filtering

**Priority:** MUST HAVE

### User Story

> As an administrator, I want to search and filter records so that I can quickly locate students, rooms, events, and allocations.

### Required Areas

- Students.
- Rooms.
- Allocation events.
- Allocations.
- Reports.

### Example Filters

- Program.
- Semester.
- Section.
- Academic year.
- Symbol number.
- Roll number.
- Allocation event.
- Room.

### Acceptance Criteria

- Search results update correctly.
- Filters can be combined where applicable.
- Clearing filters restores the full permitted dataset.

---

## FR-16 Dashboard

**Priority:** MUST HAVE

### User Story

> As an administrator, I want a dashboard showing important allocation information so that I can understand the current state of the system quickly.

### Required Metrics

- Total active students.
- Total active rooms.
- Total available seats.
- Upcoming allocation events.
- Allocated students.
- Unallocated students.

### Recommended Charts

- Students by program.
- Room utilization.
- Allocation status.
- Upcoming allocation events.

---

## FR-17 Seat Plan

**Priority:** MUST HAVE

### User Story

> As an administrator, I want a room-wise visual seat plan so that students can be physically located within the examination room.

### Requirements

- Room information.
- Event information.
- Date/time.
- Physical room layout.
- Student symbol number.
- Student name where appropriate.
- Program/semester where appropriate.

### Acceptance Criteria

- Seat placement visually corresponds to the configured room layout.
- The output is A4 printable.
- Every allocated student appears exactly once in the relevant plan.

---

## FR-18 Door Chart

**Priority:** MUST HAVE

### User Story

> As an administrator, I want a door chart so that students can identify the correct room from their symbol number.

### Requirements

- Room number/name.
- Examination event.
- Date/time.
- Symbol-number ranges.
- Program + semester grouping.

### Acceptance Criteria

- Symbol numbers are grouped clearly.
- Program and semester boundaries are distinguishable.
- The output is suitable for printing and posting outside a room.

---

## FR-19 Room Attendance Sheet

**Priority:** MUST HAVE

### User Story

> As an administrator, I want an individual attendance sheet for each examination room so that invigilators can record attendance.

### Minimum Fields

- Serial number.
- Symbol number.
- Student name.
- Program.
- Semester.
- Signature.
- Remarks.

### Acceptance Criteria

- One attendance sheet can be generated for each occupied room.
- Only students allocated to that room appear on its sheet.
- The output is A4 printable.

---

## FR-20 Allocation Summary

**Priority:** MUST HAVE

### User Story

> As an administrator, I want a summary of an allocation so that I can verify capacity and allocation status before printing.

### Required Information

- Event.
- Date/time.
- Total students.
- Allocated students.
- Unallocated students.
- Rooms used.
- Total available seats.
- Occupied seats.
- Utilization percentage.
- Room-wise counts.

---

## FR-21 Print/PDF Output

**Priority:** MUST HAVE

### User Story

> As an administrator, I want print-ready reports so that I can save them as PDF and print them for institutional use.

### Architecture

Reports should use:

```text
PHP → HTML → Print CSS → Browser Print → PDF
```

### Requirements

- A4 page size.
- Print-friendly layouts.
- Appropriate margins.
- Page-break handling.
- No unnecessary UI elements in print output.

---

## FR-22 Excel Export

**Priority:** MUST HAVE

### User Story

> As an administrator, I want allocation and student data exportable to Excel so that I can archive or process data outside the application.

### Recommendation

Use the open-source **PhpSpreadsheet** library.

### Acceptance Criteria

- Exported workbook opens successfully in standard spreadsheet software.
- Column headers are clear.
- Exported values match the active dataset.

---

## FR-23 Soft Deletion

**Priority:** MUST HAVE

### User Story

> As an administrator, I want records deactivated instead of permanently deleted so that historical allocations remain intact.

### Requirements

- Soft-delete/deactivate students.
- Soft-delete/deactivate rooms.
- Preserve allocation history.
- Exclude deleted records from future active workflows.

---

# 7. Non-Functional Requirements

## 7.1 Performance

### MVP Capacity Target

| Metric | Target |
|---|---:|
| Students | 5,000 |
| Rooms | 100 |
| Students per allocation | 5,000 |
| Physical seats | 10,000 |
| Concurrent administrators | 10 |
| Historical years | 5+ |

### Response Targets

- Normal CRUD/search requests: target ≤ 2 seconds under normal load.
- Dashboard loading: target ≤ 3 seconds.
- Student import validation for 5,000 records: target ≤ 10 seconds on a reasonable deployment environment.
- Report generation: target ≤ 5 seconds for normal institutional-sized reports.
- Allocation generation: target ≤ 15 seconds for 5,000 students under normal room/rule complexity.

These are engineering targets, not guaranteed SLAs.

## 7.2 Reliability

- Database transactions must protect allocation generation from partial writes.
- Failed allocations must not corrupt existing active versions.
- Historical allocation data must remain intact.
- Critical database operations must fail safely.

## 7.3 Security

Even without authentication in MVP, the application must protect against common web vulnerabilities.

Required controls:

- Prepared SQL statements.
- Input validation.
- Output escaping.
- Server-side validation.
- CSRF protection for state-changing requests where applicable.
- File-upload validation.
- File type and size restrictions.
- No direct exposure of database credentials.
- No unnecessary public APIs exposing student data.
- Safe error messages that do not expose SQL/database internals.

## 7.4 Accessibility

**Target:** WCAG 2.1 Level AA principles where practical for the MVP.

Requirements include:

- Keyboard-accessible controls.
- Visible focus states.
- Labels for form controls.
- Sufficient text/background contrast.
- Meaningful button labels.
- Error messages associated with relevant fields.
- Avoiding color as the sole mechanism for conveying status.

## 7.5 Browser Support

| Browser | Support |
|---|---|
| Chrome | Current + previous major |
| Microsoft Edge | Current + previous major |
| Firefox | Current + previous major |
| Mobile browsers | Responsive/basic support |

## 7.6 Device Support

- Desktop: primary.
- Laptop: primary.
- Tablet: supported.
- Mobile: responsive but not the primary administrative workflow.

---

# 8. Technical Architecture & Constraints

## 8.1 Technology Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5 |
| Styling | CSS3 + Bootstrap 5 |
| Client-side logic | Vanilla JavaScript |
| Backend | PHP 8+ |
| Backend style | Procedural PHP |
| Database | MySQL |
| Local development | XAMPP |
| Spreadsheet export | PhpSpreadsheet |
| Deployment | Web server capable of PHP + MySQL |

## 8.2 Architecture Recommendation

A separate REST API is **not required for MVP**.

Recommended structure:

```text
Browser
   │
   ├── HTML/CSS
   ├── Bootstrap
   └── Vanilla JavaScript
           │
           ↓
      PHP Application
           │
           ↓
         MySQL
```

AJAX/fetch may be used for dynamic interactions where beneficial, but the system does not need to become a full REST-based application.

## 8.3 Data Flow

```text
Student Data ───────┐
                    │
Room/Layout Data ───┤
                    ↓
             Allocation Event
                    │
                    ↓
             Allocation Engine
                    │
        ┌───────────┼───────────┐
        ↓           ↓           ↓
     Rules      Randomizer    Capacity
        └───────────┼───────────┘
                    ↓
             Allocation Draft
                    │
             Admin Review/Edit
                    │
                    ↓
             Active Allocation
                    │
       ┌────────────┼─────────────┐
       ↓            ↓             ↓
   Seat Plan     Door Chart   Attendance
                                  Sheet
```

## 8.4 Proposed Database Model

The final implementation may refine names, but the conceptual model should contain:

### Academic Years

- id
- name
- status
- created_at
- updated_at

### Students

- id
- academic_year_id
- symbol_no
- roll_no
- name
- program_id
- semester
- section
- gender
- phone
- email
- photo
- status
- is_deleted
- created_at
- updated_at

### Programs

- id
- name
- code
- status

### Rooms

- id
- academic_year_id
- room_name
- building
- floor
- status
- is_deleted
- created_at
- updated_at

### Furniture

- id
- room_id
- type
- label
- position/layout metadata
- seat_count
- status

### Seats

- id
- furniture_id
- seat_identifier
- position/layout metadata
- status

### Allocation Events

- id
- academic_year_id
- event_name
- date
- start_time
- end_time
- status
- created_at
- updated_at

### Allocation Event Participants

Maps an allocation event to relevant students and paper/program/semester information.

### Allocation Versions

- id
- allocation_event_id
- version_number
- status
- generation_seed where applicable
- created_at

### Allocations

Maps:

- allocation_version_id
- student_id
- room_id
- seat_id
- allocation status

### Seating Rules

The exact schema is intentionally extensible because the institutional separation rules have not yet been finalized.

---

# 9. User Experience & Design Requirements

## 9.1 Design Direction

**Requirement:** Professional academic interface.

**Future input:** The developer will provide visual design inspirations during UI implementation.

## 9.2 Layout

Desktop-first dashboard with responsive behavior.

Suggested global structure:

```text
┌─────────────────────────────────────────────┐
│ Header / Academic Year / Profile Area       │
├────────────┬────────────────────────────────┤
│ Sidebar    │ Main Content                    │
│            │                                │
│ Dashboard  │ Page-specific content          │
│ Students   │                                │
│ Rooms      │                                │
│ Allocation │                                │
│ Reports    │                                │
│ Settings   │                                │
└────────────┴────────────────────────────────┘
```

## 9.3 Core User Journey

```text
New Academic Year
       ↓
Load Students
       ↓
Generate/Confirm Symbols
       ↓
Configure Rooms
       ↓
Create Allocation Event
       ↓
Select Students/Papers
       ↓
Generate Allocation
       ↓
Review Result
       ↓
Resolve Problems if Needed
       ↓
Manual Adjustments
       ↓
Activate Version
       ↓
Generate Reports
       ↓
Print / Save PDF
```

## 9.4 Interaction Patterns

- Toast notifications for normal operation feedback.
- Confirmation dialog for destructive actions.
- Inline form validation.
- Import preview before commit.
- Clear empty states.
- Loading indicators for long-running allocation operations.
- Explicit draft/active/final states.
- Clear error messages with corrective actions.

## 9.5 Dark Mode

**Priority:** COULD HAVE.

Dark mode must not delay core allocation functionality.

---

# 10. Data Requirements

## 10.1 Data Ownership

The administrator is responsible for maintaining:

- Student data.
- Academic-year data.
- Room data.
- Allocation-event data.

## 10.2 Data Retention

Historical academic years should remain stored.

Recommended baseline:

- Minimum supported historical retention: 5 academic years.
- No automatic deletion of historical allocations in MVP.
- Data may be archived later as an administrative feature.

## 10.3 Historical Integrity

Once an allocation version becomes historical, subsequent changes to student master data must not change the historical allocation document.

Historical allocations should retain enough information to reproduce the historical record.

This may require storing allocation-time snapshots of key display fields such as:

- Symbol number.
- Student name.
- Program.
- Semester.

## 10.4 Privacy

Although the project does not classify student data as highly sensitive, personal information must not be unnecessarily exposed.

The system should:

- Restrict data to administrative workflows.
- Avoid public student endpoints.
- Validate all uploaded data.
- Avoid unnecessary collection.
- Avoid exposing internal database identifiers.

## 10.5 GDPR

Full GDPR compliance is **not a formal MVP requirement** because the target is a generic academic project rather than a confirmed EU deployment.

However, the architecture should follow reasonable privacy principles:

- Data minimization.
- Purpose limitation.
- Controlled access.
- Retention awareness.
- Safe deletion/deactivation.

## 10.6 Analytics

No external analytics platform is required for MVP.

Operational metrics such as allocation counts can be derived from application data.

---

# 11. Error Handling

## 11.1 General Principle

Errors must be:

1. Detectable.
2. Understandable.
3. Recoverable where possible.
4. Non-destructive.

## 11.2 Common Cases

### Insufficient Seats

Display:

- Required seats.
- Available seats.
- Shortfall.
- Rooms currently selected.

Suggested actions:

- Add rooms.
- Increase available seats.
- Remove inactive/invalid room configuration.

### Seating Rules Impossible

Display:

- Students involved.
- Available seats.
- Rule conflict.
- Relaxation attempt.
- Remaining unallocated students.

### Duplicate Student

During import:

- Identify row.
- Show existing record.
- Update according to approved import behavior.

### Invalid Import Row

- Reject invalid row.
- Display field and reason.
- Continue processing valid rows.

### Database Failure

- Roll back the current transaction.
- Preserve previous active allocation.
- Display a generic user-friendly message.
- Log technical details server-side where feasible.

### Invalid Manual Assignment

- Prevent duplicate occupancy.
- Prevent assigning one student to multiple seats.
- Show a clear error.

---

# 12. Release & Rollout Plan

## 12.1 Phase 1 — Foundation

**Target:** Week 1

Deliver:

- Database.
- Academic years.
- Students.
- Programs.
- Student import.
- Symbol management.
- Rooms.
- Furniture/seats.
- Basic UI.

## 12.2 Phase 2 — Allocation Engine

**Target:** Week 2

Deliver:

- Allocation events.
- Student selection.
- Room selection.
- Randomization.
- Seating rules architecture.
- Allocation algorithm.
- Rule relaxation.
- Draft handling.
- Manual movement.
- Versioning.

## 12.3 Phase 3 — Reports & UX

**Target:** Week 3

Deliver:

- Dashboard.
- Seat plan.
- Door chart.
- Attendance sheet.
- Allocation summary.
- Print layouts.
- Excel export.
- Search/filtering.
- Responsive UI.
- Toasts.

## 12.4 Phase 4 — Testing & Deployment

**Target:** Week 4

Deliver:

- Synthetic-data tests.
- Edge-case testing.
- Performance testing.
- Bug fixing.
- Deployment.
- Documentation.
- Presentation preparation.

## 12.5 Rollback

For failed allocation generation:

- Never overwrite the existing active version.
- Generate into a new draft/version.
- Commit only after successful validation.
- If generation fails, retain the previous active version.

For deployment:

- Maintain a database backup before schema/data migrations.
- Keep a known working application version available for rollback.

---

# 13. Risks & Mitigation Strategies

| Risk | Impact | Probability | Mitigation |
|---|---|---|---|
| Seating rules become too complex | High | High | Make rule engine extensible |
| Insufficient room capacity | High | Medium | Capacity calculation + pre-allocation validation |
| Irregular room layouts complicate allocation | High | Medium | Separate physical layout from allocation logic |
| 5,000-student allocation is slow | High | Medium | Efficient queries, in-memory allocation algorithm, indexing |
| Import data is inconsistent | High | High | Preview + validation + row-level errors |
| Manual changes invalidate rules | Medium | High | Manual changes intentionally unrestricted; show status |
| One-month schedule is too short | High | High | Strict MVP scope and four-phase schedule |
| PDF layout differs across browsers | Medium | Medium | Standardize print CSS and test supported browsers |
| PHP code becomes difficult to maintain | Medium | Medium | Modular procedural structure and reusable functions |
| Database corruption during allocation | High | Low | Transactions and version-based allocation |
| Future commercial requirements conflict with MVP | Medium | Medium | Keep extensible data model without implementing future scope |
| No authentication creates deployment risk | High | Medium | Restrict deployment environment; design authentication extension point |

---

# 14. Timeline & Milestones

## Week 1 — Data Foundation

**Milestone:** Academic data and physical room data can be managed.

Dependencies:

- Database schema.
- CRUD.
- Import validation.
- Room layout model.

## Week 2 — Allocation Engine

**Milestone:** Students can be automatically allocated to seats.

Dependencies:

- Student data.
- Room data.
- Physical seats.
- Allocation event.
- Seating-rule interface.

## Week 3 — Reports & Interface

**Milestone:** Institution-ready documents can be generated.

Dependencies:

- Working allocation engine.
- Stable allocation version.

## Week 4 — Validation & Release

**Milestone:** Deployed demonstrable MVP.

Dependencies:

- All core features complete.
- Synthetic test data.
- Critical bugs fixed.

## Critical Path

```text
Database
   ↓
Student/Room Data
   ↓
Physical Seat Model
   ↓
Allocation Event
   ↓
Allocation Engine
   ↓
Allocation Version
   ↓
Reports
   ↓
Testing
   ↓
Deployment
```

The allocation engine is the primary technical critical path.

---

# 15. Testing Strategy

## 15.1 Test Dataset

A synthetic dataset should contain:

- Up to 5,000 students.
- Multiple programs.
- Multiple semesters.
- Multiple sections.
- Multiple subjects.
- Up to 100 rooms.
- Single-seat desks.
- Multi-seat benches.
- Irregular layouts.

## 15.2 Functional Test Categories

### Student Tests

- Create.
- Update.
- Search.
- Filter.
- Import.
- Duplicate.
- Soft delete.

### Room Tests

- Create.
- Modify.
- Add/remove furniture.
- Add/remove seats.
- Irregular layout.
- Capacity calculation.
- Soft delete.

### Allocation Tests

- Normal allocation.
- Multi-program allocation.
- Multi-semester allocation.
- Multiple rooms.
- Randomized regeneration.
- Manual movement.
- Versioning.
- Insufficient capacity.
- Rule conflict.
- Partial/draft allocation.

### Report Tests

- Seat plan.
- Door chart.
- Attendance sheet.
- Summary.
- PDF print.
- Excel export.

## 15.3 Acceptance Testing

Primary acceptance tester:

**Project developer/student**

Optional stakeholder validation:

**Project coordinator / institutional representative**

---

# 16. Assumptions

1. The system is initially used by a trusted administrator.
2. Authentication is intentionally excluded from MVP.
3. Student data is loaded at the beginning of an academic year.
4. Symbol numbers remain consistent for a student throughout that academic year.
5. A new academic year creates a new data scope rather than overwriting history.
6. Rooms are manually configured.
7. Room capacity is calculated from physical seats.
8. All configured active seats are potentially usable unless excluded by future functionality.
9. The administrator handles real-world room availability.
10. The system can support multiple programs/faculties in one allocation event.
11. Multiple semesters may participate in one event.
12. Different subjects may occur simultaneously.
13. The exact separation algorithm is not yet finalized.
14. Automatic allocation is rule-aware.
15. Manual allocation is administrator-controlled and does not enforce automatic seating rules.
16. Allocation generation is randomized.
17. Previous generated versions are preserved.
18. Browser printing is sufficient for PDF generation in MVP.
19. The system will primarily be used on desktop/laptop computers.
20. The project is developed using free/open-source tooling wherever practical.
21. The first deployment does not require commercial-scale multi-tenant infrastructure.

---

# 17. Open Questions & Decision Log

## 17.1 Open Questions

### OQ-01 — Exact Seating Rules

**Status:** OPEN

The exact separation logic has not yet been finalized.

Examples of possible rule dimensions:

- Program.
- Semester.
- Program + semester.
- Section.
- Subject.

This must be finalized before the allocation engine can be considered institutionally complete.

### OQ-02 — Visible Seat Identifier Format

**Status:** OPEN

The internal seat identifier is required, but the final human-facing format has not been decided.

Possible formats:

- Seat 12.
- A12.
- R2-S12.
- Desk 4 / Seat 2.

### OQ-03 — Final UI Design

**Status:** OPEN

The product will use a professional academic visual style. Specific design inspirations will be provided during UI development.

## 17.2 Resolved Decisions

| Decision | Resolution |
|---|---|
| Product scope | Exam seating allocation only |
| Target institution | Generic academic institution |
| MVP audience | Administrator |
| Authentication | Excluded |
| Student login | Future |
| Academic-year separation | Required |
| Historical data | Preserved |
| Student import | CSV/XLSX + manual |
| Existing student on import | Update |
| Symbol number | Unique within academic year |
| Symbol generation | Automatic + administrator override |
| Roll number | Stored separately |
| Room capacity | Calculated from seats |
| Furniture | Configurable |
| Irregular layouts | Supported |
| Multiple programs | Supported |
| Multiple semesters | Supported |
| Multiple subjects | Supported within allocation event |
| Randomization | Required |
| Manual movement | Required |
| Manual movement rule validation | Not enforced |
| Regeneration | Required |
| Allocation versions | Preserved |
| Rule failure | Relax rules and/or produce draft |
| Historical allocations | Preserved |
| Deletion | Soft deletion |
| Attendance | One sheet per room |
| Door chart | Program + semester grouping |
| Reports | A4 print/PDF |
| Excel | Required |
| API | No separate REST API for MVP |
| Backend | Procedural PHP |
| Database | MySQL |
| Frontend | HTML/CSS/Bootstrap/Vanilla JS |
| Deployment | Web |
| Primary device | Desktop/laptop |
| Responsive | Required |
| Dark mode | Optional |
| Audit log | Excluded |
| Testing data | Synthetic |
| MVP timeline | Approximately 4 weeks |

---

# 18. Appendices

## Appendix A — Glossary

### Academic Year
A defined educational period such as 2026/27. Student and allocation data are scoped to the academic year.

### Allocation Event
A lightweight record describing a specific examination seating requirement, including date/time and participating students/papers. It is not a full examination-management entity.

### Allocation Version
A generated seating arrangement for an allocation event.

### Active Allocation
The version currently selected as the authoritative arrangement for reporting and printing.

### Draft Allocation
An incomplete or unresolved allocation that requires administrator action.

### Symbol Number
The student's examination identifier for the academic year.

### Roll Number
The student's normal academic/class identifier.

### Furniture
A physical desk or bench within a room.

### Seat
A physical student position associated with furniture.

### Seating Rule
A constraint used by the automatic allocation engine when assigning students.

### Rule Relaxation
The controlled reduction of seating constraints when a strict allocation is impossible.

### Soft Delete
Marking a record inactive/deleted without physically removing it from the database.

### Door Chart
A room-level document showing which symbol-number groups are assigned to that room.

### Seat Plan
A visual representation of the physical room and student seat assignments.

### Attendance Sheet
A room-specific list of allocated students used by invigilators to record attendance.

---

## Appendix B — Conceptual ER Diagram

Placeholder/reference for the final technical documentation:

```text
┌─────────────────┐
│ Academic Year   │
└────────┬────────┘
         │
    ┌────┴───────────────┐
    ↓                    ↓
┌──────────┐       ┌──────────┐
│ Students │       │  Rooms   │
└────┬─────┘       └────┬─────┘
     │                  │
     │             ┌────┴─────┐
     │             ↓          ↓
     │        Furniture      Seats
     │
     ↓
┌────────────────────┐
│ Allocation Events  │
└──────────┬─────────┘
           ↓
┌────────────────────┐
│ Allocation Versions│
└──────────┬─────────┘
           ↓
┌────────────────────┐
│    Allocations     │
└──────────┬─────────┘
           ↓
       Student + Room + Seat
```

**Diagram requirement:** The final implementation documentation should replace this conceptual diagram with a formal ER diagram showing primary keys, foreign keys, cardinality, and important constraints.

---

## Appendix C — Allocation Workflow Diagram

```text
Academic Data
     │
     ├─────────────┐
     ↓             ↓
 Students        Rooms
     │             │
     └──────┬──────┘
            ↓
    Create Allocation Event
            ↓
    Select Participants
            ↓
    Validate Capacity
            ↓
     Randomize Students
            ↓
     Apply Seating Rules
            ↓
       ┌────┴────┐
       ↓         ↓
    Success    Failure
       │         │
       │    Relax Rules
       │         ↓
       │     Retry
       │         ↓
       │     Draft if
       │     unresolved
       └────┬────┘
            ↓
     Review Allocation
            ↓
     Manual Adjustments
            ↓
      Activate Version
            ↓
     Generate Documents
```

---

## Appendix D — Report Outputs

### Seat Plan

Purpose: Show the physical seating arrangement of a room.

### Door Chart

Purpose: Help students identify the room associated with their symbol-number range.

### Attendance Sheet

Purpose: Allow room invigilators to record attendance.

### Allocation Summary

Purpose: Allow administrators to verify allocation totals and room utilization.

---

## Appendix E — Future Commercial Expansion

The following capabilities should remain outside MVP but influence extensibility:

1. Authentication.
2. Role-based access control.
3. Multi-institution tenancy.
4. Student portal.
5. Student room/seat lookup.
6. Invigilator accounts.
7. Full examination management.
8. Advanced seating-rule configuration.
9. Institution-specific allocation policies.
10. Notifications.
11. Audit logs.
12. Cloud deployment.
13. Advanced analytics.
14. Mobile application.
15. API-first integrations.

These are **future opportunities, not MVP requirements**.

---

# 19. Final Quality Gate

Before declaring the MVP PRD complete:

- [x] Product scope defined.
- [x] Primary user defined.
- [x] Core workflow defined.
- [x] Functional requirements prioritized.
- [x] User stories standardized.
- [x] Acceptance criteria included.
- [x] Error-handling behavior defined.
- [x] Performance targets defined.
- [x] Security baseline defined.
- [x] Accessibility target defined.
- [x] Browser/device support defined.
- [x] Technical stack defined.
- [x] Data model direction defined.
- [x] Historical-data strategy defined.
- [x] Import workflow defined.
- [x] Reporting outputs defined.
- [x] Release plan defined.
- [x] Testing strategy defined.
- [x] Risks and mitigations defined.
- [x] Assumptions documented.
- [x] Future scope separated from MVP.
- [x] Remaining stakeholder decisions explicitly identified.
- [ ] Exact seating/separation algorithm finalized.
- [ ] Final human-facing seat identifier format finalized.
- [ ] Final UI design finalized.

**Important:** The last three unchecked items are intentionally not silently invented. The system architecture is designed to accommodate them later without requiring a fundamental redesign.
