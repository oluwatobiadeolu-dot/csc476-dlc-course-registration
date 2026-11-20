<?php
// Setup check. Upload, open it in the browser, then DELETE it from the server.
header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$rows = [];
function row(string $label, bool $ok, string $detail = '', bool $note = false): void
{
    global $rows;
    $rows[] = [$label, $ok, $detail, $note];
}

// 1. PHP
row('PHP version', version_compare(PHP_VERSION, '7.4', '>='), PHP_VERSION . ' (7.4 or newer is needed)');
foreach (['pdo', 'pdo_mysql', 'ctype', 'session'] as $ext) {
    row('PHP extension: ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'loaded' : 'MISSING');
}

// 2. Files
$files = ['config/config.php', 'config/db.php', 'includes/functions.php', 'includes/header.php',
          'includes/footer.php', 'includes/auth_aside.php', 'assets/css/style.css', 'assets/js/app.js',
          'login.php', 'student/dashboard.php', 'admin/dashboard.php'];
foreach ($files as $f) {
    row('File: ' . $f, is_file(__DIR__ . '/' . $f), is_file(__DIR__ . '/' . $f) ? 'found' : 'NOT FOUND, upload it');
}

// 2b. Are the design files the new versions?
$css = @file_get_contents(__DIR__ . '/assets/css/style.css');
$cssNew = $css !== false && strpos($css, 'skip-link') !== false && strpos($css, 'keydates') !== false;
row('Stylesheet is the new version', $cssNew, $cssNew ? 'yes' : 'OLD or missing: upload assets/css/style.css from the new zip and overwrite the old one');
$js = @file_get_contents(__DIR__ . '/assets/js/app.js');
row('assets/js/app.js is the new version', $js !== false && strpos($js, 'data-toggle-password') !== false, $js === false ? 'NOT FOUND, upload it' : 'checked');

// 3. Config
$configOk = false;
try {
    require_once __DIR__ . '/config/config.php';
    $configOk = true;
    row('BASE_URL', true, "'" . BASE_URL . "' (empty if the site is at the root, otherwise the folder name)");
    // on free hosting these must come from the hosting control panel; XAMPP defaults are fine on a local machine
    $hostNote = DB_HOST === 'localhost' ? '  (fine on XAMPP. On InfinityFree use the MySQL hostname from the control panel)' : '';
    $userNote = DB_USER === 'root' ? '  (fine on XAMPP. On InfinityFree use the database user from the control panel)' : '';
    $passNote = DB_PASS === '' ? 'EMPTY  (fine on XAMPP. On InfinityFree use the database password from the control panel)' : strlen(DB_PASS) . ' characters';
    row('DB_HOST', true, DB_HOST . $hostNote, $hostNote !== '');
    row('DB_NAME', true, DB_NAME);
    row('DB_USER', true, DB_USER . $userNote, $userNote !== '');
    row('DB_PASS', true, $passNote, DB_PASS === '');
} catch (Throwable $t) {
    row('config/config.php', false, $t->getMessage());
}

// 4. Database
$pdo = null;
if ($configOk) {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
        row('Database connection', true, 'connected');
    } catch (Throwable $t) {
        $msg = $t->getMessage();
        $hint = '';
        if (strpos($msg, '1045') !== false) {
            $hint = 'Wrong database username or password.';
        } elseif (strpos($msg, '1044') !== false || strpos($msg, '1049') !== false) {
            $hint = 'Wrong database name, or this user cannot use it.';
        } elseif (strpos($msg, '2002') !== false || strpos($msg, 'getaddrinfo') !== false || strpos($msg, 'php_network') !== false) {
            $hint = 'Wrong database host. Copy the MySQL hostname exactly from the control panel.';
        }
        row('Database connection', false, $msg . ($hint ? '  >>> ' . $hint : ''));
    }
}

// 5. Tables and columns
if ($pdo) {
    $tables = ['departments', 'users', 'academic_sessions', 'courses', 'registrations', 'deadlines', 'discrepancy_reports', 'settings'];
    foreach ($tables as $tbl) {
        try {
            $n = $pdo->query('SELECT COUNT(*) FROM `' . $tbl . '`')->fetchColumn();
            row('Table: ' . $tbl, true, $n . ' rows');
        } catch (Throwable $t) {
            row('Table: ' . $tbl, false, 'MISSING. Import schema.sql into this database (the settings table is in the newer schema.sql).');
        }
    }
    try {
        $pdo->query('SELECT course_type FROM courses LIMIT 1');
        row('Column: courses.course_type', true, 'found');
    } catch (Throwable $t) {
        row('Column: courses.course_type', false, 'MISSING. Run migration_optional_features.sql once, or re-import the newer schema.sql.');
    }
    try {
        $admin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        row('Admin account', (int) $admin > 0, $admin . ' found');
        $cur = $pdo->query('SELECT COUNT(*) FROM academic_sessions WHERE is_current = 1')->fetchColumn();
        row('Current academic session', (int) $cur === 1, $cur . ' marked current (should be 1)');
    } catch (Throwable $t) {
        row('Basic data', false, $t->getMessage());
    }
}

// 6. Sessions
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['check'] = 'ok';
    row('Sessions (needed for login)', ($_SESSION['check'] ?? '') === 'ok', 'working');
} catch (Throwable $t) {
    row('Sessions (needed for login)', false, $t->getMessage());
}

$failed = count(array_filter($rows, function ($r) { return !$r[1]; }));
$noted = count(array_filter($rows, function ($r) { return $r[3]; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Setup check</title>
<style>
  body { font-family: system-ui, Arial, sans-serif; background: #f3f5f8; color: #1c2733; margin: 0; padding: 1.5rem; }
  .box { max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #d9e0e8; border-radius: 8px; padding: 1.5rem; }
  h1 { font-size: 1.25rem; margin: 0 0 .25rem; }
  .verdict { padding: .75rem 1rem; border-radius: 6px; margin: 1rem 0; font-weight: 600; }
  .good { background: #e8f4ee; color: #14543a; }
  .bad { background: #fdecea; color: #8f1d14; }
  table { width: 100%; border-collapse: collapse; font-size: .92rem; }
  td { padding: .5rem .6rem; border-top: 1px solid #edf0f4; vertical-align: top; word-break: break-word; }
  td:first-child { width: 38%; font-weight: 500; }
  .ok { color: #1c7c54; font-weight: 700; }
  .no { color: #b42318; font-weight: 700; }
  .nt { color: #9a6a00; font-weight: 700; }
  .warn { margin-top: 1rem; font-size: .85rem; color: #5d6b7a; }
</style>
</head>
<body>
<div class="box">
  <h1>Setup check</h1>
  <div>Shows what is wrong when the site gives a blank error page.</div>
  <?php if ($failed === 0): ?>
    <div class="verdict good">Everything checked here looks fine<?= $noted ? ' (read the NOTE lines if this is a hosted site)' : '' ?>. If login still fails, create an empty file named debug.on in the project folder, try again, and read the error shown.</div>
  <?php else: ?>
    <div class="verdict bad"><?= (int) $failed ?> problem<?= $failed === 1 ? '' : 's' ?> found. Fix the lines marked FAIL, then refresh this page.</div>
  <?php endif; ?>
  <table>
    <?php foreach ($rows as [$label, $ok, $detail, $note]): ?>
      <tr>
        <td><?= htmlspecialchars($label) ?></td>
        <td><span class="<?= !$ok ? 'no' : ($note ? 'nt' : 'ok') ?>"><?= !$ok ? 'FAIL' : ($note ? 'NOTE' : 'OK') ?></span> <?= htmlspecialchars($detail) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <p class="warn">Delete check.php from the server when you are done. It shows setup details.</p>
</div>
</body>
</html>
