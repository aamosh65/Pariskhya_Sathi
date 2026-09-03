<?php
// Parikshya Sathi - Core Procedural Helper Functions
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Clean & sanitize text input.
 */
function sanitize(?string $str): string {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

/**
 * Send a standardized JSON response and exit.
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Handle CORS preflight options request.
 */
function handle_cors(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        exit;
    }
}

/**
 * Retrieve JSON input payload or fallback to $_POST.
 */
function get_json_input(): array {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST ?? [];
}

/**
 * Set flash toast message.
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash toast message.
 */
function get_flash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Helper to generate application relative URLs.
 */
function base_url(string $path = ''): string {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', $scriptDir);

    $root = '';
    $parts = explode('/', trim($scriptDir, '/'));
    $matched = [];
    foreach ($parts as $p) {
        if ($p === '') continue;
        $matched[] = $p;
        if (strtolower($p) === 'parikshya-sathi') {
            $root = '/' . implode('/', $matched);
            break;
        }
    }
    
    $cleanPath = ltrim($path, '/');
    return ($root ? $root : '') . '/' . $cleanPath;
}

/**
 * Format standard readable dates.
 */
function format_date(?string $dateStr): string {
    if (empty($dateStr)) return '—';
    $timestamp = strtotime($dateStr);
    return $timestamp ? date('M d, Y', $timestamp) : $dateStr;
}

/**
 * Format standard readable time.
 */
function format_time(?string $timeStr): string {
    if (empty($timeStr)) return '—';
    return $timeStr;
}

/**
 * Compute System Dashboard Statistics.
 */
function get_dashboard_stats(int $academicYearId): array {
    $db = get_db();
    
    // Total Students
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE academic_year_id = ? AND is_deleted = 0");
    $stmt->execute([$academicYearId]);
    $totalStudents = (int)$stmt->fetchColumn();

    // Active Rooms
    $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE academic_year_id = ? AND is_deleted = 0 AND status = 'active'");
    $stmt->execute([$academicYearId]);
    $totalRooms = (int)$stmt->fetchColumn();

    // Total Physical Seats
    $stmt = $db->prepare("SELECT COUNT(*) FROM seats s JOIN rooms r ON s.room_id = r.id WHERE r.academic_year_id = ? AND r.is_deleted = 0 AND r.status = 'active' AND s.status = 'active'");
    $stmt->execute([$academicYearId]);
    $totalSeats = (int)$stmt->fetchColumn();

    // Total Allocation Events
    $stmt = $db->prepare("SELECT COUNT(*) FROM allocation_events WHERE academic_year_id = ?");
    $stmt->execute([$academicYearId]);
    $totalEvents = (int)$stmt->fetchColumn();

    // Program Breakdown
    $stmt = $db->prepare("SELECT p.name, p.code, COUNT(s.id) as student_count 
        FROM programs p 
        LEFT JOIN students s ON p.id = s.program_id AND s.academic_year_id = ? AND s.is_deleted = 0 
        GROUP BY p.id, p.name, p.code 
        ORDER BY student_count DESC");
    $stmt->execute([$academicYearId]);
    $programStats = $stmt->fetchAll();

    // Room Capacity Breakdown
    $stmt = $db->prepare("SELECT r.id, r.room_name, r.building, r.floor, r.capacity,
        (SELECT COUNT(*) FROM seats WHERE room_id = r.id AND status = 'active') as active_seats
        FROM rooms r 
        WHERE r.academic_year_id = ? AND r.is_deleted = 0 
        ORDER BY r.capacity DESC");
    $stmt->execute([$academicYearId]);
    $roomStats = $stmt->fetchAll();

    // Recent Allocation Events
    $stmt = $db->prepare("SELECT e.*, v.version_number, v.allocated_count, v.total_students as version_students 
        FROM allocation_events e 
        LEFT JOIN allocation_versions v ON e.active_version_id = v.id 
        WHERE e.academic_year_id = ? 
        ORDER BY e.event_date DESC, e.id DESC 
        LIMIT 5");
    $stmt->execute([$academicYearId]);
    $recentEvents = $stmt->fetchAll();

    return [
        'total_students' => $totalStudents,
        'total_rooms' => $totalRooms,
        'total_seats' => $totalSeats,
        'total_events' => $totalEvents,
        'program_stats' => $programStats,
        'room_stats' => $roomStats,
        'recent_events' => $recentEvents,
    ];
}

/**
 * Fetch all programs.
 */
function get_all_programs(): array {
    $db = get_db();
    return $db->query("SELECT * FROM programs WHERE status = 'active' ORDER BY code ASC")->fetchAll();
}

/**
 * Fetch all rooms for active academic year.
 */
function get_all_rooms(int $academicYearId): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM rooms WHERE academic_year_id = ? AND is_deleted = 0 ORDER BY room_name ASC");
    $stmt->execute([$academicYearId]);
    return $stmt->fetchAll();
}
