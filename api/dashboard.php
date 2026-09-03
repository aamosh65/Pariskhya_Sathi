<?php
// Parikshya Sathi - Dashboard API Endpoint
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

handle_cors();

try {
    $activeYear = get_active_academic_year();
    $stats = get_dashboard_stats((int)$activeYear['id']);

    json_response([
        'success' => true,
        'data' => [
            'active_year' => $activeYear,
            'stats' => $stats
        ]
    ]);
} catch (Throwable $e) {
    json_response([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
