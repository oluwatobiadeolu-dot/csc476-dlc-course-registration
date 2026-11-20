<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');
$session = require_current_session();

$stmt = db()->prepare(
    'SELECT COUNT(*) AS n, COALESCE(SUM(c.credit_units), 0) AS units
     FROM registrations r JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = ? AND r.session_id = ? AND r.status = "active"'
);
$stmt->execute([$user['id'], $session['id']]);
$reg = $stmt->fetch();

$open = db()->prepare('SELECT COUNT(*) FROM discrepancy_reports WHERE student_id = ? AND status <> "resolved"');
$open->execute([$user['id']]);
$openReports = (int) $open->fetchColumn();

$limits = unit_limits();

$pageTitle = 'Student dashboard';
require __DIR__ . '/../includes/header.php';
$first = explode(' ', trim($user['full_name']))[0];
$maxUnits = (int) $limits['max'];
$pct = $maxUnits > 0 ? min(100, (int) round(((int) $reg['units']) / $maxUnits * 100)) : 0;
?>
<?= page_header('Welcome, ' . $first, (int) $user['level'] . ' Level student',
    '<a class="btn btn-primary" href="' . BASE_URL . '/student/courses.php">' . icon('journal-text') . 'Browse courses</a>') ?>

<?= deadline_banner($session) ?>

<div class="row g-3 mb-3">
  <div class="col-sm-4"><?= stat_card('Registered courses', (int) $reg['n'], 'journal-check') ?></div>
  <div class="col-sm-4"><?= stat_card('Credit units', (int) $reg['units'], 'collection') ?></div>
  <div class="col-sm-4"><?= stat_card('Open course reports', $openReports, 'flag') ?></div>
</div>

<?php if ($maxUnits > 0): ?>
  <div class="card mb-3"><div class="card-body">
    <div class="card-title-row"><h2>Unit load this semester</h2><span class="muted small"><?= (int) $reg['units'] ?> of <?= $maxUnits ?> units</span></div>
    <div class="progress unit-bar" role="progressbar" aria-label="Unit load"><div class="progress-bar" style="width: <?= $pct ?>%"></div></div>
    <div class="form-text"><?= e(limits_text($limits)) ?></div>
  </div></div>
<?php endif; ?>

<h2 class="section-title">Quick actions</h2>
<div class="row g-3">
  <div class="col-sm-6 col-lg-3"><a class="quick" href="<?= BASE_URL ?>/student/courses.php"><?= icon('search') ?><strong>Find courses</strong><span>Search this semester's courses</span></a></div>
  <div class="col-sm-6 col-lg-3"><a class="quick" href="<?= BASE_URL ?>/student/registrations.php"><?= icon('journal-check') ?><strong>My registrations</strong><span>Review or drop courses</span></a></div>
  <div class="col-sm-6 col-lg-3"><a class="quick" href="<?= BASE_URL ?>/student/slip.php"><?= icon('printer') ?><strong>Registration slip</strong><span>View and print your slip</span></a></div>
  <div class="col-sm-6 col-lg-3"><a class="quick" href="<?= BASE_URL ?>/student/report_new.php"><?= icon('flag') ?><strong>Report a course issue</strong><span>Tell the admin about wrong details</span></a></div>
</div>

<h2 class="section-title">How registration works</h2>
<div class="card"><div class="card-body">
  <ol class="steps">
    <li><strong>Pick courses</strong>Tick the courses you need, including any carryovers.</li>
    <li><strong>Check your units</strong>The total updates as you tick, against the configured limits.</li>
    <li><strong>Register</strong>Submit your cart before the registration deadline.</li>
    <li><strong>Print your slip</strong>Keep a copy of your registered courses.</li>
  </ol>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
