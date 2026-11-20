<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('admin');

$sessions = db()->query('SELECT * FROM academic_sessions ORDER BY session_name DESC, semester')->fetchAll();
$sid = (int) ($_GET['session_id'] ?? 0);
if (!$sid) {
    $cur = current_session();
    $sid = $cur ? (int) $cur['id'] : (int) ($sessions[0]['id'] ?? 0);
}

$students  = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
$published = (int) db()->query('SELECT COUNT(*) FROM courses WHERE status = "published"')->fetchColumn();
$drafts    = (int) db()->query('SELECT COUNT(*) FROM courses WHERE status = "draft"')->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM registrations WHERE session_id = ? AND status = "active"');
$stmt->execute([$sid]);
$activeRegs = (int) $stmt->fetchColumn();

// registrations per course
$stmt = db()->prepare(
    'SELECT c.course_code, COUNT(r.id) AS total
     FROM registrations r JOIN courses c ON c.id = r.course_id
     WHERE r.session_id = ? AND r.status = "active"
     GROUP BY c.id, c.course_code
     ORDER BY c.course_code'
);
$stmt->execute([$sid]);
$byCourse = $stmt->fetchAll();

// students per level, with and without registrations
$stmt = db()->prepare(
    'SELECT u.level,
            COUNT(*) AS total,
            SUM(CASE WHEN EXISTS (
                  SELECT 1 FROM registrations r
                  WHERE r.student_id = u.id AND r.session_id = ? AND r.status = "active"
                ) THEN 1 ELSE 0 END) AS registered
     FROM users u
     WHERE u.role = "student"
     GROUP BY u.level
     ORDER BY u.level'
);
$stmt->execute([$sid]);
$byLevel = $stmt->fetchAll();

// students with no active registration
$stmt = db()->prepare(
    'SELECT u.full_name, u.matric_no, u.level, d.name AS department
     FROM users u LEFT JOIN departments d ON d.id = u.department_id
     WHERE u.role = "student"
       AND NOT EXISTS (
         SELECT 1 FROM registrations r
         WHERE r.student_id = u.id AND r.session_id = ? AND r.status = "active"
       )
     ORDER BY u.level, u.full_name'
);
$stmt->execute([$sid]);
$noRegs = $stmt->fetchAll();

$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$courseLabels = json_encode(array_column($byCourse, 'course_code'), $flags);
$courseData   = json_encode(array_map('intval', array_column($byCourse, 'total')), $flags);
$levelLabels  = json_encode(array_map(fn($r) => $r['level'] . ' Level', $byLevel), $flags);
$levelReg     = json_encode(array_map(fn($r) => (int) $r['registered'], $byLevel), $flags);
$levelNot     = json_encode(array_map(fn($r) => (int) $r['total'] - (int) $r['registered'], $byLevel), $flags);

ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const courseCanvas = document.getElementById('courseChart');
if (courseCanvas) {
  new Chart(courseCanvas, {
    type: 'bar',
    data: {
      labels: <?= $courseLabels ?>,
      datasets: [{ label: 'Active registrations', data: <?= $courseData ?>, backgroundColor: '#17496f', maxBarThickness: 56 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      scales: { y: { beginAtZero: true, grace: '10%', ticks: { precision: 0 } } }
    }
  });
}
const levelCanvas = document.getElementById('levelChart');
if (levelCanvas) {
  new Chart(levelCanvas, {
    type: 'bar',
    data: {
      labels: <?= $levelLabels ?>,
      datasets: [
        { label: 'With registrations', data: <?= $levelReg ?>, backgroundColor: '#17496f' },
        { label: 'No registrations', data: <?= $levelNot ?>, backgroundColor: '#d9a441' }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, grace: '10%', ticks: { precision: 0 } } }
    }
  });
}
</script>
<?php
$extraScripts = ob_get_clean();

$pageTitle = 'Admin dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Administrator dashboard</h1>
    <p class="page-sub">Registration activity for the selected session</p>
  </div>
  <form method="get" class="page-actions">
    <select class="form-select" name="session_id" onchange="this.form.submit()" aria-label="Session and semester">
      <?php foreach ($sessions as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === $sid ? 'selected' : '' ?>>
          <?= e(session_label($s)) ?><?= $s['is_current'] ? ' (current)' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><?= stat_card('Students', $students, 'people') ?></div>
  <div class="col-6 col-lg-3"><?= stat_card('Active registrations', $activeRegs, 'journal-check') ?></div>
  <div class="col-6 col-lg-3"><?= stat_card('Published courses', $published, 'journal-text') ?></div>
  <div class="col-6 col-lg-3"><?= stat_card('Drafts awaiting review', $drafts, 'hourglass-split') ?></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="card-title-row"><h2>Registrations by course</h2></div>
      <?php if ($byCourse): ?>
        <div class="chart-box"><canvas id="courseChart" aria-label="Registrations by course" role="img"></canvas></div>
      <?php else: ?>
        <p class="muted mb-0">No active registrations for this session and semester.</p>
      <?php endif; ?>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="card-title-row"><h2>Students by level</h2></div>
      <?php if ($byLevel): ?>
        <div class="chart-box"><canvas id="levelChart" aria-label="Students by level" role="img"></canvas></div>
      <?php else: ?>
        <p class="muted mb-0">No students yet.</p>
      <?php endif; ?>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="card-title-row"><h2>Students with no registrations (<?= count($noRegs) ?>)</h2></div>
    <?php if (!$noRegs): ?>
      <p class="muted mb-0">Every student has at least one active registration for this selection.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Name</th><th>Matric</th><th>Level</th><th>Department</th></tr></thead>
          <tbody>
          <?php foreach ($noRegs as $s): ?>
            <tr><td><?= e($s['full_name']) ?></td><td><?= e($s['matric_no']) ?></td><td><?= (int) $s['level'] ?></td><td><?= e($s['department'] ?? '-') ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
