<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');

$stmt = db()->prepare(
    'SELECT r.*, c.course_code, c.title
     FROM discrepancy_reports r JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = ? ORDER BY r.created_at DESC'
);
$stmt->execute([$user['id']]);
$reports = $stmt->fetchAll();

$pageTitle = 'My reports';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('My course reports', 'Follow the status of issues you have reported',
    '<a class="btn btn-primary" href="' . BASE_URL . '/student/report_new.php">' . icon('plus-lg') . 'New report</a>') ?>

<?php if (!$reports): ?>
  <?= empty_state('flag', 'No reports yet', 'If a course title, code or unit count looks wrong, you can report it here.',
      '<a class="btn btn-primary" href="' . BASE_URL . '/student/report_new.php">Report an issue</a>') ?>
<?php else: ?>
  <?php foreach ($reports as $r): ?>
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2">
          <strong><?= e($r['course_code']) ?> <span class="muted fw-normal"><?= e($r['title']) ?></span></strong>
          <?= report_badge($r['status']) ?>
        </div>
        <p class="mt-2 mb-1"><?= nl2br(e($r['message'])) ?></p>
        <small class="muted">Submitted <?= e(date('j M Y, g:i A', strtotime($r['created_at']))) ?>
          <?php if ($r['updated_at'] !== $r['created_at']): ?> &middot; Updated <?= e(date('j M Y, g:i A', strtotime($r['updated_at']))) ?><?php endif; ?>
        </small>
        <?php if ($r['admin_response']): ?>
          <div class="alert alert-info mt-3 mb-0" role="note"><?= icon('chat-left-text') ?><div><strong>Administrator response</strong><br><?= nl2br(e($r['admin_response'])) ?></div></div>
        <?php elseif ($r['status'] !== 'open'): ?>
          <div class="alert alert-info mt-3 mb-0" role="note"><?= icon('info-circle-fill') ?><div>The administrator marked this report as <?= e(str_replace('_', ' ', $r['status'])) ?>.</div></div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
