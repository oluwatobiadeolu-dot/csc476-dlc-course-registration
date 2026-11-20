<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');

$regId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT r.id, r.session_id, c.course_code, c.title, c.credit_units
     FROM registrations r JOIN courses c ON c.id = r.course_id
     WHERE r.id = ? AND r.student_id = ? AND r.status = "active"'
);
$stmt->execute([$regId, $user['id']]);
$reg = $stmt->fetch();

if (!$reg) {
    flash('warning', 'That registration was not found or is already dropped.');
    redirect('/student/registrations.php');
}

$deadlines = get_deadlines((int) $reg['session_id']);
$dropDeadline = $deadlines['drop_deadline'] ?? null;
$dropOpen = deadline_open($dropDeadline);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // deadline is checked here, on the server
    if (!$dropOpen) {
        flash('danger', 'Drop rejected: the course drop deadline has passed.');
        redirect('/student/registrations.php');
    }
    $upd = db()->prepare('UPDATE registrations SET status = "dropped", dropped_at = NOW() WHERE id = ? AND student_id = ? AND status = "active"');
    $upd->execute([$regId, $user['id']]);
    flash('success', $reg['course_code'] . ' has been dropped.');
    redirect('/student/registrations.php');
}

$pageTitle = 'Confirm course drop';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Drop this course?', 'Please confirm before anything changes') ?>
<div class="card form-card"><div class="card-body">
  <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
    <div>
      <div class="fw-semibold"><?= e($reg['course_code']) ?></div>
      <div class="muted"><?= e($reg['title']) ?></div>
    </div>
    <span class="pill pill-secondary"><?= (int) $reg['credit_units'] ?> units</span>
  </div>

  <?php if ($dropOpen): ?>
    <div class="alert alert-warning" role="alert"><?= icon('exclamation-triangle-fill') ?><div>
      If you confirm, this course is removed from your active registrations and its units no longer count towards your total.
      You can drop until <strong><?= e(deadline_text($dropDeadline)) ?></strong>.
    </div></div>
    <form method="post" class="d-flex gap-2">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $reg['id'] ?>">
      <button class="btn btn-danger" type="submit">Confirm drop</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/student/registrations.php">Cancel</a>
    </form>
  <?php else: ?>
    <div class="alert alert-danger" role="alert"><?= icon('exclamation-octagon-fill') ?><div>
      The course drop deadline has passed (<?= e(deadline_text($dropDeadline)) ?>). This course can no longer be dropped.
    </div></div>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/student/registrations.php">Back</a>
  <?php endif; ?>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
