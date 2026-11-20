<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$sessions = db()->query('SELECT * FROM academic_sessions ORDER BY session_name DESC, semester')->fetchAll();
$sid = (int) ($_GET['session_id'] ?? 0);
if (!$sid) {
    $cur = current_session();
    $sid = $cur ? (int) $cur['id'] : (int) ($sessions[0]['id'] ?? 0);
}

$stmt = db()->prepare(
    'SELECT u.full_name, u.matric_no, u.level, c.course_code, c.title, c.credit_units, r.status, r.registered_at
     FROM registrations r
     JOIN users u ON u.id = r.student_id
     JOIN courses c ON c.id = r.course_id
     WHERE r.session_id = ?
     ORDER BY u.full_name, c.course_code'
);
$stmt->execute([$sid]);
$rows = $stmt->fetchAll();

$pageTitle = 'Registration records';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Student registration records</h1>
    <p class="page-sub">Every registration for the selected session and semester</p>
  </div>
  <form method="get" class="page-actions">
    <select class="form-select" name="session_id" onchange="this.form.submit()" aria-label="Session and semester">
      <?php foreach ($sessions as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === $sid ? 'selected' : '' ?>><?= e(session_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if (!$rows): ?>
  <?= empty_state('journal-x', 'No registrations', 'No student has registered a course for this session and semester.') ?>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead><tr><th>Student</th><th>Matric</th><th>Level</th><th>Course</th><th>Units</th><th>Status</th><th>Registered</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['full_name']) ?></td>
        <td><?= e($r['matric_no']) ?></td>
        <td><?= (int) $r['level'] ?></td>
        <td><strong><?= e($r['course_code']) ?></strong></td>
        <td><?= (int) $r['credit_units'] ?></td>
        <td><?= $r['status'] === 'active'
                ? '<span class="pill pill-success">Active</span>'
                : '<span class="pill pill-secondary">Dropped</span>' ?></td>
        <td><?= e(date('j M Y', strtotime($r['registered_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
