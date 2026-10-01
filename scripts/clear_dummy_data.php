<?php
// Parikshya Sathi - Database Dummy Data Cleanup Script
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

echo "Purging all synthetic/dummy data from Parikshya Sathi...\n\n";

$pdo = get_db();

try {
    $pdo->beginTransaction();

    // 1. Delete transactional records in correct dependency order
    $tablesToPurge = [
        'allocations',
        'allocation_versions',
        'allocation_event_rooms',
        'allocation_event_participants',
        'allocation_events',
        'seats',
        'furniture',
        'rooms',
        'students',
        'programs'
    ];

    foreach ($tablesToPurge as $table) {
        $count = $pdo->exec("DELETE FROM {$table}");
        echo "Cleared {$table} (deleted {$count} rows)\n";
    }

    // 2. Clear dummy archived academic year (keep active 2026/27)
    $pdo->exec("DELETE FROM academic_years WHERE id != 1");
    $pdo->exec("UPDATE academic_years SET is_active = 1, status = 'active' WHERE id = 1");
    echo "Reset academic_years (kept active 2026/27)\n";

    // 3. Reset auto-increment counters in sqlite_sequence
    $seqTables = [
        'programs',
        'students',
        'rooms',
        'furniture',
        'seats',
        'allocation_events',
        'allocation_event_participants',
        'allocation_event_rooms',
        'allocation_versions',
        'allocations'
    ];
    foreach ($seqTables as $seqTable) {
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name = '{$seqTable}'");
    }
    echo "Reset auto-increment sequences for wiped tables.\n";

    $pdo->commit();
    echo "Purge transaction committed successfully.\n\n";

    // 4. Vacuum database to reclaim space
    $pdo->exec("VACUUM;");
    echo "SQLite database vacuumed successfully.\n\n";

    // 5. Verify current row counts
    $allTables = [
        'academic_years',
        'programs',
        'students',
        'rooms',
        'furniture',
        'seats',
        'allocation_events',
        'allocation_event_participants',
        'allocation_event_rooms',
        'allocation_versions',
        'allocations'
    ];

    echo "=== Current Database Status ===\n";
    foreach ($allTables as $t) {
        $cnt = $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
        echo str_pad($t, 32) . ": " . $cnt . "\n";
    }

    echo "\nAll dummy data successfully cleared!\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR clearing dummy data: " . $e->getMessage() . "\n";
    exit(1);
}
