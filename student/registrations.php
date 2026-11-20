<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');
$session = require_current_session();

$stmt = db()->prepare(
    'SELECT r.id AS reg_id, r.status, r.registered_at, r.dropped_at, c.course_code, c.title, c.credit_units, c.course_type, c.level
     FROM registrations r JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = ? AND r.session_id = ?
     ORDER BY c.course_code'
);
$stmt->execute([$user['id'], $session['id']]);
$all = $stmt->fetchAll();
$active  = array_filter($all, fn($r) => $r['status'] === 'active');
$dropped = array_filter($all, fn($r) => $r['status'] === 'dropped');
$total = array_sum(array_column($active, 'credit_units'));

$pageTitle = 'My registrations';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('My registrations', session_label($session),
    '<a class="btn btn-outline-secondary" href="' . BASE_URL . '/student/slip.php">' . icon('printer') . 'Registration slip</a>') ?>

<?= deadline_banner($session) ?>

<?php if (!$active): ?>
  <?= empty_state('journal-x', 'No active registrations', 'You have not registered any courses for this semester yet.',
      '<a class="btn btn-primary" href="' . BASE_URL . '/student/courses.php">Browse courses</a>') ?>
<?php else: ?>
<div class="table-responsive">
  <table class="table align-middle">
    <thead><tr><th>Code</th><th>Title</th><th>Level</th><th>Type</th><th>Units</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($active as $r): ?>
      <tr>
        <td><strong><?= e($r['course_code']) ?></strong></td>
        <td><?= e($r['title']) ?></td>
        <td><?= (int) $r['level'] ?></td>
        <td><?= course_type_badge($r['course_type']) ?></td>
        <td><?= (int) $r['credit_units'] ?></td>
        <td><span class="pill pill-success">Registered</span></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/student/drop.php?id=<?= (int) $r['reg_id'] ?>">Drop</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th colspan="4">Total units</th><th><?= (int) $total ?></th><th colspan="2"></th></tr></tfoot>
  </table>
</div>
<?php endif; ?>

<?php if ($dropped): ?>
  <h2 class="section-title">Dropped courses</h2>
  <div class="card">
    <ul class="list-group list-group-flush">
      <?php foreach ($dropped as $r): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><strong><?= e($r['course_code']) ?></strong> <span class="muted"><?= e($r['title']) ?></span></span>
          <span class="pill pill-secondary">Dropped <?= e(date('j M Y', strtotime($r['dropped_at']))) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
