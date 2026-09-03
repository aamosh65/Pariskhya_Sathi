// Parikshya Sathi - Client Side Application Logic
// Supports interactive room layouts, drag-and-drop / click-to-swap seat editor, CSV parsing & filtering

document.addEventListener('DOMContentLoaded', () => {
    initTableFilters();
    initSeatEditor();
    initRoomBuilder();
    initCsvImporter();
});

/**
 * Filter tables on-the-fly with search input.
 */
function initTableFilters() {
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const tableSelector = input.getAttribute('data-table-search');
        const table = document.querySelector(tableSelector);
        if (!table) return;

        input.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });
}

/**
 * Interactive Seat Editor (Click-to-Swap / Manual Adjustment).
 */
let selectedSeatElement = null;

function initSeatEditor() {
    const editorCanvas = document.querySelector('[data-seat-editor]');
    if (!editorCanvas) return;

    const seatSlots = editorCanvas.querySelectorAll('.seat-slot');
    const swapStatusBadge = document.getElementById('swap-status-badge');
    const swapInstructions = document.getElementById('swap-instructions');

    seatSlots.forEach(slot => {
        slot.addEventListener('click', () => {
            const seatId = slot.getAttribute('data-seat-id');
            const studentId = slot.getAttribute('data-student-id');
            const allocId = slot.getAttribute('data-alloc-id');

            // If no seat is currently selected, select this one
            if (!selectedSeatElement) {
                if (!studentId && !allocId) {
                    showToast('info', 'Select an occupied seat first to move or swap.');
                    return;
                }
                selectedSeatElement = slot;
                slot.classList.add('seat-selected');
                if (swapStatusBadge) swapStatusBadge.classList.remove('d-none');
                if (swapInstructions) swapInstructions.innerText = 'Now click any other seat (empty or occupied) to swap/move.';
                return;
            }

            // If the same seat is clicked, cancel selection
            if (selectedSeatElement === slot) {
                selectedSeatElement.classList.remove('seat-selected');
                selectedSeatElement = null;
                if (swapStatusBadge) swapStatusBadge.classList.add('d-none');
                if (swapInstructions) swapInstructions.innerText = 'Click any occupied seat to begin moving/swapping.';
                return;
            }

            // Swap or move between selectedSeatElement and slot
            executeSeatSwap(selectedSeatElement, slot);
        });
    });
}

function executeSeatSwap(slot1, slot2) {
    const allocId1 = slot1.getAttribute('data-alloc-id');
    const seatId1 = slot1.getAttribute('data-seat-id');
    const studentId1 = slot1.getAttribute('data-student-id');

    const allocId2 = slot2.getAttribute('data-alloc-id');
    const seatId2 = slot2.getAttribute('data-seat-id');
    const studentId2 = slot2.getAttribute('data-student-id');

    // Visual Swap of HTML Content and attributes
    const content1 = slot1.innerHTML;
    const classes1 = [...slot1.classList].filter(c => c.startsWith('occupied'));

    const content2 = slot2.innerHTML;
    const classes2 = [...slot2.classList].filter(c => c.startsWith('occupied'));

    // Swap contents
    slot1.innerHTML = content2;
    slot2.innerHTML = content1;

    // Swap data attributes
    slot1.setAttribute('data-alloc-id', allocId2 || '');
    slot1.setAttribute('data-student-id', studentId2 || '');
    slot2.setAttribute('data-alloc-id', allocId1 || '');
    slot2.setAttribute('data-student-id', studentId1 || '');

    // Swap classes
    classes1.forEach(c => slot1.classList.remove(c));
    classes2.forEach(c => slot2.classList.remove(c));
    classes1.forEach(c => slot2.classList.add(c));
    classes2.forEach(c => slot1.classList.add(c));

    // Reset selection
    slot1.classList.remove('seat-selected');
    selectedSeatElement = null;

    const swapStatusBadge = document.getElementById('swap-status-badge');
    const swapInstructions = document.getElementById('swap-instructions');
    if (swapStatusBadge) swapStatusBadge.classList.add('d-none');
    if (swapInstructions) swapInstructions.innerText = 'Seats swapped successfully! Unsaved manual changes are reflected.';

    // Send AJAX update if versionId is present
    const versionId = document.querySelector('[data-version-id]')?.getAttribute('data-version-id');
    if (versionId) {
        fetch(window.location.origin + window.location.pathname.replace(/\/[^\/]+$/, '') + '/../../api/swap-seats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                version_id: versionId,
                seat1_id: seatId1,
                alloc1_id: allocId1,
                seat2_id: seatId2,
                alloc2_id: allocId2
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Seat assignment persisted to active version.');
            } else {
                showToast('warning', 'Local swap updated. Note: ' + (data.message || 'Updated'));
            }
        })
        .catch(err => {
            showToast('info', 'Local arrangement updated.');
        });
    } else {
        showToast('success', 'Seats adjusted in view.');
    }
}

/**
 * Dynamic Room & Physical Layout Builder live preview.
 */
function initRoomBuilder() {
    const form = document.getElementById('room-layout-form');
    if (!form) return;

    const rowsInput = form.querySelector('[name="rows_count"]');
    const colsInput = form.querySelector('[name="cols_count"]');
    const seatsInput = form.querySelector('[name="default_seats_per_bench"]');
    const previewContainer = document.getElementById('room-layout-preview');
    const capacityBadge = document.getElementById('calculated-capacity-badge');

    function updatePreview() {
        const rows = parseInt(rowsInput?.value || 5, 10);
        const cols = parseInt(colsInput?.value || 4, 10);
        const seatsPerBench = parseInt(seatsInput?.value || 2, 10);
        const totalCapacity = rows * cols * seatsPerBench;

        if (capacityBadge) {
            capacityBadge.innerText = totalCapacity + ' Seats';
        }

        if (!previewContainer) return;

        let html = '<div class="teacher-podium"><i class="bi bi-person-workspace me-1"></i> Invigilator Desk / Blackboard (Front)</div>';
        html += '<div class="d-flex flex-column gap-3">';

        for (let r = 1; r <= rows; r++) {
            html += `<div class="d-flex gap-3 justify-content-center align-items-center">
                <span class="badge bg-secondary rounded-pill" style="font-size:11px;width:55px;">Row ${r}</span>
                <div class="row g-2 flex-grow-1">`;

            for (let c = 1; c <= cols; c++) {
                html += `<div class="col">
                    <div class="furniture-bench p-2">
                        <div class="fine-print fw-bold text-center mb-1 text-muted">Desk ${r}-${c}</div>
                        <div class="d-flex gap-1 justify-content-center">`;

                for (let s = 1; s <= seatsPerBench; s++) {
                    html += `<div class="seat-slot p-1 text-center flex-fill" style="min-height:38px;font-size:11px;">
                        <span class="text-secondary fw-semibold">S${s}</span>
                    </div>`;
                }

                html += `</div></div></div>`;
            }

            html += `</div></div>`;
        }

        html += '</div>';
        previewContainer.innerHTML = html;
    }

    if (rowsInput) rowsInput.addEventListener('input', updatePreview);
    if (colsInput) colsInput.addEventListener('input', updatePreview);
    if (seatsInput) seatsInput.addEventListener('change', updatePreview);

    updatePreview();
}

/**
 * CSV Import Parser & Live Validation Table.
 */
function initCsvImporter() {
    const fileInput = document.getElementById('student-csv-file');
    const previewContainer = document.getElementById('csv-preview-container');
    const rawDataInput = document.getElementById('parsed-csv-json');
    const commitBtn = document.getElementById('commit-import-btn');

    if (!fileInput) return;

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            const text = event.target.result;
            parseAndPreviewCsv(text);
        };
        reader.readAsText(file);
    });

    function parseAndPreviewCsv(csvText) {
        const lines = csvText.split(/\r\n|\n/).filter(line => line.trim() !== '');
        if (lines.length <= 1) {
            showToast('danger', 'CSV file is empty or missing headers.');
            return;
        }

        const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
        const parsedRows = [];
        const symbolSet = new Set();
        let errorCount = 0;

        let tableHtml = `<div class="table-responsive"><table class="table-apple">
            <thead><tr>
                <th>Status</th>
                <th>Symbol No</th>
                <th>Roll No</th>
                <th>Student Name</th>
                <th>Program Code</th>
                <th>Semester</th>
                <th>Section</th>
                <th>Gender</th>
            </tr></thead><tbody>`;

        for (let i = 1; i < lines.length; i++) {
            const cols = lines[i].split(',').map(c => c.trim());
            if (cols.length < 4) continue;

            const symbolNo = cols[0] || '';
            const rollNo = cols[1] || '';
            const name = cols[2] || '';
            const programCode = cols[3] || 'BCA';
            const semester = cols[4] || '1';
            const section = cols[5] || 'A';
            const gender = cols[6] || 'Male';

            let rowError = null;
            if (!symbolNo || !name) {
                rowError = 'Symbol No & Name are required';
            } else if (symbolSet.has(symbolNo)) {
                rowError = 'Duplicate Symbol No in file';
            }

            symbolSet.add(symbolNo);
            if (rowError) errorCount++;

            parsedRows.push({
                symbol_no: symbolNo,
                roll_no: rollNo,
                name: name,
                program_code: programCode,
                semester: semester,
                section: section,
                gender: gender,
                error: rowError
            });

            tableHtml += `<tr class="${rowError ? 'table-danger' : ''}">
                <td>${rowError ? `<span class="badge-apple badge-apple-danger">${rowError}</span>` : `<span class="badge-apple badge-apple-success">Valid</span>`}</td>
                <td class="fw-semibold">${symbolNo}</td>
                <td>${rollNo}</td>
                <td class="fw-semibold">${name}</td>
                <td><span class="badge-apple badge-apple-primary">${programCode}</span></td>
                <td>Sem ${semester}</td>
                <td>${section}</td>
                <td>${gender}</td>
            </tr>`;
        }

        tableHtml += `</tbody></table></div>`;

        if (previewContainer) {
            previewContainer.innerHTML = tableHtml;
            previewContainer.classList.remove('d-none');
        }

        if (rawDataInput) {
            rawDataInput.value = JSON.stringify(parsedRows);
        }

        if (commitBtn) {
            commitBtn.disabled = false;
            commitBtn.innerHTML = `<i class="bi bi-cloud-arrow-up me-1"></i> Confirm & Import ${parsedRows.length - errorCount} Students`;
        }

        showToast('info', `Parsed ${parsedRows.length} rows (${errorCount} invalid rows detected).`);
    }
}

/**
 * Toast Notification Helper
 */
function showToast(type, message) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '24px';
        container.style.right = '24px';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'apple-card p-3 shadow-lg d-flex align-items-center gap-2';
    toast.style.margin = '0';
    toast.style.minWidth = '280px';
    toast.style.maxWidth = '420px';
    toast.style.borderRadius = '14px';
    toast.style.animation = 'fadeIn 0.2s ease-out';

    let icon = 'info-circle-fill text-primary';
    if (type === 'success') icon = 'check-circle-fill text-success';
    if (type === 'danger') icon = 'exclamation-triangle-fill text-danger';
    if (type === 'warning') icon = 'exclamation-circle-fill text-warning';

    toast.innerHTML = `
        <i class="bi bi-${icon} fs-5"></i>
        <div class="flex-grow-1 fine-print text-dark fw-medium">${message}</div>
        <button type="button" class="btn-close btn-close-sm ms-2" onclick="this.parentElement.remove()"></button>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
