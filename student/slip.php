<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');
$session = require_current_session();

$info = db()->prepare(
    'SELECT u.matric_no, d.name AS department
     FROM users u LEFT JOIN departments d ON d.id = u.department_id WHERE u.id = ?'
);
$info->execute([$user['id']]);
$student = $info->fetch();

$stmt = db()->prepare(
    'SELECT c.course_code, c.title, c.credit_units, c.course_type, c.level
     FROM registrations r JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = ? AND r.session_id = ? AND r.status = "active"
     ORDER BY c.course_code'
);
$stmt->execute([$user['id'], $session['id']]);
$courses = $stmt->fetchAll();
$total = array_sum(array_column($courses, 'credit_units'));

$pageTitle = 'Registration slip';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-print-none">
  <?= page_header('Registration slip', 'Print this page or save it as a PDF',
      '<button class="btn btn-primary" type="button" onclick="window.print()">' . icon('printer') . 'Print slip</button>'
      . '<a class="btn btn-outline-secondary" href="' . BASE_URL . '/student/registrations.php">Back</a>') ?>
</div>

<div class="slip">
  <div class="slip-head">
    <div class="brand-mark">DLC</div>
    <h1>University of Ibadan</h1>
    <p>Distance Learning Centre &middot; Course Registration Slip</p>
  </div>

  <div class="slip-info">
    <div><span>Name</span><strong><?= e($user['full_name']) ?></strong></div>
    <div><span>Matric number</span><strong><?= e($student['matric_no'] ?? '') ?></strong></div>
    <div><span>Department</span><strong><?= e($student['department'] ?? '-') ?></strong></div>
    <div><span>Level</span><strong><?= (int) $user['level'] ?></strong></div>
    <div><span>Session</span><strong><?= e(session_label($session)) ?></strong></div>
    <div><span>Date printed</span><strong><?= e(date('j F Y, g:i A')) ?></strong></div>
  </div>

  <?php if (!$courses): ?>
    <div class="alert alert-secondary">No active registrations for this semester.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>Code</th><th>Title</th><th>Level</th><th>Type</th><th>Units</th></tr></thead>
        <tbody>
        <?php foreach ($courses as $i => $c): ?>
          <tr><td><?= $i + 1 ?></td><td><?= e($c['course_code']) ?></td><td><?= e($c['title']) ?></td><td><?= (int) $c['level'] ?></td><td><?= e(ucfirst($c['course_type'])) ?></td><td><?= (int) $c['credit_units'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><th colspan="5" class="text-end">Total units</th><th><?= (int) $total ?></th></tr></tfoot>
      </table>
    </div>
  <?php endif; ?>

  <div class="slip-sign">
    <div>Student's signature</div>
    <div>Course adviser</div>
    <div>Date</div>
  </div>
  <p class="slip-note">Prototype slip generated from made-up data. It is not an official University document.</p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
