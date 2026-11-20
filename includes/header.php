<?php
require_once __DIR__ . '/functions.php';
$user = current_user();
$pageTitle = $pageTitle ?? 'Course Registration';
$authLayout = $authLayout ?? false;

$links = [];
$cartCount = 0;
if ($user && $user['role'] === 'student') {
    $cartCount = count(cart_ids());
    $links = [
        '/student/dashboard.php'     => ['Dashboard', 'grid'],
        '/student/courses.php'       => ['Courses', 'journal-text'],
        '/student/cart.php'          => ['Cart', 'cart3'],
        '/student/registrations.php' => ['My registrations', 'journal-check'],
        '/student/reports.php'       => ['My reports', 'flag'],
    ];
} elseif ($user && $user['role'] === 'admin') {
    $links = [
        '/admin/dashboard.php'     => ['Dashboard', 'grid'],
        '/admin/courses.php'       => ['Courses', 'journal-text'],
        '/admin/deadlines.php'     => ['Deadlines', 'calendar-event'],
        '/admin/settings.php'      => ['Unit limits', 'sliders'],
        '/admin/reports.php'       => ['Reports', 'flag'],
        '/admin/registrations.php' => ['Registrations', 'people'],
    ];
}
$cssVersion = @filemtime(__DIR__ . '/../assets/css/style.css') ?: 1;
$initials = $user ? strtoupper(substr($user['full_name'], 0, 1) . (strpos($user['full_name'], ' ') !== false ? substr($user['full_name'], strpos($user['full_name'], ' ') + 1, 1) : '')) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | DLC Course Registration</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= $cssVersion ?>" rel="stylesheet">
</head>
<body class="<?= $authLayout ? 'auth-body' : '' ?>">
<a class="skip-link" href="#main">Skip to main content</a>
<?php if (!$authLayout): ?>
<div class="institution-bar d-print-none">
  <div class="container">
    <div>University of Ibadan &middot; Distance Learning Centre</div>
    <span class="d-none d-sm-inline">Course registration prototype</span>
  </div>
</div>
<nav class="navbar navbar-expand-lg app-nav d-print-none" aria-label="Main">
  <div class="container">
    <a class="brand" href="<?= BASE_URL ?>/">
      <span class="brand-mark">DLC</span>
      <span class="brand-text"><strong>Course Registration</strong><small>University of Ibadan</small></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto ms-lg-3">
        <?php foreach ($links as $path => [$label, $ico]): $current = is_current_page($path); ?>
          <li class="nav-item">
            <a class="nav-link <?= $current ? 'active' : '' ?>" href="<?= BASE_URL . $path ?>" <?= $current ? 'aria-current="page"' : '' ?>>
              <?= icon($ico) ?><?= e($label) ?>
              <?php if ($path === '/student/cart.php' && $cartCount > 0): ?><span class="nav-count"><?= $cartCount ?></span><?php endif; ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($user): ?>
        <div class="dropdown">
          <button class="user-chip" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar"><?= e($initials) ?></span>
            <span class="user-meta"><strong><?= e($user['full_name']) ?></strong><small><?= e($user['role']) ?></small></span>
            <i class="bi bi-chevron-down small"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-secondary me-2" href="<?= BASE_URL ?>/login.php">Log in</a>
        <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/register.php">Create account</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php endif; ?>
<main id="main" class="<?= $authLayout ? '' : 'container app-main' ?>">
<?php if (!$authLayout) { echo render_flashes(); } ?>
