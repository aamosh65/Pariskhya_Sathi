<?php
// Parikshya Sathi - Header Layout (Apple Human Interface)
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$activeYear = get_active_academic_year();
$flash = get_flash();
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Parikshya Sathi</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Apple Design System CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/design-system.css') ?>">
</head>
<body>

<!-- 1. Global Navigation Bar (Black 44px) -->
<nav class="global-nav">
    <div class="container-fluid d-flex justify-content-between align-items-center px-lg-4">
        <a href="<?= base_url('index.php') ?>" class="brand-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
            <span>Parikshya Sathi</span>
        </a>

        <div class="d-none d-md-flex align-items-center gap-1">
            <a href="<?= base_url('index.php') ?>" class="nav-link-item <?= str_ends_with($currentScript, 'index.php') && !str_contains($currentScript, 'pages') ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= base_url('pages/students/index.php') ?>" class="nav-link-item <?= str_contains($currentScript, 'students') ? 'active' : '' ?>">Students</a>
            <a href="<?= base_url('pages/rooms/index.php') ?>" class="nav-link-item <?= str_contains($currentScript, 'rooms') ? 'active' : '' ?>">Rooms & Layouts</a>
            <a href="<?= base_url('pages/allocations/index.php') ?>" class="nav-link-item <?= str_contains($currentScript, 'allocations') ? 'active' : '' ?>">Allocations</a>
            <a href="<?= base_url('pages/reports/summary.php') ?>" class="nav-link-item <?= str_contains($currentScript, 'reports') ? 'active' : '' ?>">Print & Reports</a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('pages/academic-years/index.php') ?>" class="btn-apple-dark" title="Switch Academic Year">
                <i class="bi bi-calendar3"></i>
                <span>AY: <?= htmlspecialchars($activeYear['name']) ?></span>
            </a>
        </div>
    </div>
</nav>

<!-- 2. Frosted Sub-Navigation Bar (54px) -->
<header class="sub-nav-frosted">
    <div class="container-fluid d-flex justify-content-between align-items-center px-lg-4">
        <div class="d-flex align-items-center gap-3">
            <h1 class="category-title"><?= isset($pageHeading) ? htmlspecialchars($pageHeading) : 'Exam Seating Allocation' ?></h1>
            <?php if (isset($pageBadge)): ?>
                <span class="badge-apple badge-apple-primary"><?= htmlspecialchars($pageBadge) ?></span>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?php if (isset($headerActionHtml)): ?>
                <?= $headerActionHtml ?>
            <?php else: ?>
                <a href="<?= base_url('pages/allocations/create.php') ?>" class="btn-apple-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Allocation</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Main Application Content Container -->
<main class="container-fluid px-lg-4 py-4" style="max-width: 1440px;">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : htmlspecialchars($flash['type']) ?> alert-dismissible fade show apple-card p-3 mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-info-circle-fill fs-5 text-<?= htmlspecialchars($flash['type']) ?>"></i>
            <div class="flex-grow-1 fine-print fw-medium text-dark"><?= htmlspecialchars($flash['message']) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
