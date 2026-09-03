// Parikshya Sathi - Shared Layout & Navigation Architecture
// Injects Apple Design System Header, Frosted Sub-Nav, Toast system, and Footer

const AppLayout = (() => {
    // Determine dynamic relative root path to /api/ and /pages/
    const normalizedPath = window.location.pathname.replace(/\\/g, '/').toLowerCase();
    let rootPath = './';
    let pagesPath = 'pages/';
    let apiPath = 'api/';

    if (normalizedPath.includes('/pages/reports/')) {
        rootPath = '../../';
        pagesPath = '../';
        apiPath = '../../api/';
    } else if (normalizedPath.includes('/pages/')) {
        rootPath = '../';
        pagesPath = './';
        apiPath = '../api/';
    }

    /**
     * Centralized async API request wrapper.
     */
    async function api(endpoint, method = 'GET', body = null) {
        const url = apiPath + endpoint;
        const options = {
            method: method.toUpperCase(),
            headers: {
                'Accept': 'application/json'
            }
        };

        if (body && options.method !== 'GET') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        try {
            const res = await fetch(url, options);
            const data = await res.json();
            if (!res.ok || data.success === false) {
                const msg = data.error || data.message || `API Error (${res.status})`;
                if (window.showToast) window.showToast('danger', msg);
                throw new Error(msg);
            }
            return data;
        } catch (err) {
            console.error('API Call Failed:', url, err);
            throw err;
        }
    }

    /**
     * Initializes global layout across HTML pages.
     */
    async function init(config = {}) {
        const pageTitle = config.title || 'Parikshya Sathi';
        const pageHeading = config.heading || 'Overview';
        const pageBadge = config.badge || '';
        const actionHtml = config.actionHtml || '';
        const activeNav = config.activeNav || detectActiveNav();

        // Update document title
        document.title = `${pageHeading} — Parikshya Sathi`;

        // Render Header
        const headerContainer = document.getElementById('app-header');
        if (headerContainer) {
            headerContainer.innerHTML = `
            <nav class="global-nav">
                <div class="container-fluid d-flex justify-content-between align-items-center px-lg-4">
                    <a href="${rootPath}index.html" class="brand-title text-decoration-none">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                        <span>Parikshya Sathi</span>
                    </a>

                    <div class="d-none d-md-flex align-items-center gap-1">
                        <a href="${rootPath}index.html" class="nav-link-item ${activeNav === 'dashboard' ? 'active' : ''}">Dashboard</a>
                        <a href="${pagesPath}students.html" class="nav-link-item ${activeNav === 'students' ? 'active' : ''}">Students</a>
                        <a href="${pagesPath}rooms.html" class="nav-link-item ${activeNav === 'rooms' ? 'active' : ''}">Rooms & Layouts</a>
                        <a href="${pagesPath}allocations.html" class="nav-link-item ${activeNav === 'allocations' ? 'active' : ''}">Allocations</a>
                        <a href="${pagesPath}reports.html" class="nav-link-item ${activeNav === 'reports' ? 'active' : ''}">Print & Reports</a>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="${pagesPath}academic-years.html" class="btn-apple-dark text-decoration-none" title="Switch Academic Year">
                            <i class="bi bi-calendar3"></i>
                            <span id="nav-active-year-label">AY: Loading...</span>
                        </a>
                    </div>
                </div>
            </nav>

            <header class="sub-nav-frosted">
                <div class="container-fluid d-flex justify-content-between align-items-center px-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <h1 class="category-title mb-0">${pageHeading}</h1>
                        ${pageBadge ? `<span id="page-subnav-badge" class="badge-apple badge-apple-primary">${pageBadge}</span>` : ''}
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        ${actionHtml}
                    </div>
                </div>
            </header>
            `;
        }

        // Render Footer
        const footerContainer = document.getElementById('app-footer');
        if (footerContainer) {
            footerContainer.innerHTML = `
            <footer class="footer mt-5 py-5 no-print" style="background-color: var(--canvas-parchment); border-top: 1px solid var(--hairline);">
                <div class="container-fluid px-lg-4" style="max-width: 1440px;">
                    <div class="row gy-4 mb-4">
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                                <span class="body-strong">Parikshya Sathi</span>
                            </div>
                            <p class="fine-print text-muted mb-0">
                                Standard web-based examination seating allocation system. Intelligent randomized seat planning, multi-hall capacity interleaving, and institutional A4 print documentation.
                            </p>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="caption-strong mb-2">Academic Data</div>
                            <ul class="list-unstyled fine-print d-flex flex-column gap-1">
                                <li><a href="${pagesPath}students.html" class="text-secondary text-decoration-none">Students Directory</a></li>
                                <li><a href="${pagesPath}students.html?action=import" class="text-secondary text-decoration-none">Bulk CSV Import</a></li>
                                <li><a href="${pagesPath}students.html?action=symbols" class="text-secondary text-decoration-none">Symbol Numbers</a></li>
                                <li><a href="${pagesPath}academic-years.html" class="text-secondary text-decoration-none">Academic Years</a></li>
                            </ul>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="caption-strong mb-2">Halls & Layouts</div>
                            <ul class="list-unstyled fine-print d-flex flex-column gap-1">
                                <li><a href="${pagesPath}rooms.html" class="text-secondary text-decoration-none">Examination Rooms</a></li>
                                <li><a href="${pagesPath}rooms.html?action=new" class="text-secondary text-decoration-none">Add New Hall</a></li>
                            </ul>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="caption-strong mb-2">Allocations</div>
                            <ul class="list-unstyled fine-print d-flex flex-column gap-1">
                                <li><a href="${pagesPath}allocations.html" class="text-secondary text-decoration-none">Scheduled Events</a></li>
                                <li><a href="${pagesPath}allocations.html?action=new" class="text-secondary text-decoration-none">New Allocation Wizard</a></li>
                            </ul>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="caption-strong mb-2">Print & Reports</div>
                            <ul class="list-unstyled fine-print d-flex flex-column gap-1">
                                <li><a href="${pagesPath}reports.html" class="text-secondary text-decoration-none">Reports Hub</a></li>
                                <li><a href="${pagesPath}reports/door-chart.html" class="text-secondary text-decoration-none">Door Charts</a></li>
                                <li><a href="${pagesPath}reports/seat-plan.html" class="text-secondary text-decoration-none">Room Seat Plans</a></li>
                                <li><a href="${pagesPath}reports/attendance.html" class="text-secondary text-decoration-none">Attendance Sheets</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pt-3 border-top border-secondary-subtle fine-print text-muted">
                        <div>&copy; 2026 Parikshya Sathi — Examination Seating Allocation System. Standard HTML5/CSS3 + PHP REST API.</div>
                        <div class="mt-2 mt-md-0">Apple Human Interface &bull; Responsive &bull; A4 Ready</div>
                    </div>
                </div>
            </footer>
            `;
        }

        // Fetch & set active academic year in nav
        loadActiveYear();
    }

    async function loadActiveYear() {
        try {
            const res = await api('academic-years.php');
            if (res.data?.active_year) {
                const yearLabel = document.getElementById('nav-active-year-label');
                if (yearLabel) {
                    yearLabel.innerText = `AY: ${res.data.active_year.name}`;
                }
            }
        } catch (e) {
            console.warn('Could not load active academic year badge', e);
        }
    }

    function detectActiveNav() {
        const p = window.location.pathname.toLowerCase();
        if (p.includes('student')) return 'students';
        if (p.includes('room')) return 'rooms';
        if (p.includes('allocation')) return 'allocations';
        if (p.includes('report')) return 'reports';
        if (p.includes('academic-year')) return 'academic-years';
        return 'dashboard';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    return {
        api,
        init,
        formatDate,
        rootPath,
        pagesPath,
        apiPath
    };
})();

window.AppLayout = AppLayout;
