<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT c.*, d.name AS department
     FROM courses c LEFT JOIN departments d ON d.id = c.department_id
     WHERE c.id = ?'
);
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    exit('Course not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($course['status'] !== 'draft') {
        flash('info', 'This course is already published.');
        redirect('/admin/courses.php');
    }
    $pub = db()->prepare('UPDATE courses SET status = "published", published_at = NOW() WHERE id = ? AND status = "draft"');
    $pub->execute([$id]);
    flash('success', $course['course_code'] . ' is now published and visible to eligible students.');
    redirect('/admin/courses.php');
}

$pageTitle = 'Review course';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Review before publishing', 'Students will see exactly this information once the course is published') ?>
<div class="card form-card"><div class="card-body">
  <dl class="row mb-4">
    <dt class="col-sm-4">Course code</dt><dd class="col-sm-8"><?= e($course['course_code']) ?></dd>
    <dt class="col-sm-4">Title</dt><dd class="col-sm-8"><?= e($course['title']) ?></dd>
    <dt class="col-sm-4">Type</dt><dd class="col-sm-8"><?= course_type_badge($course['course_type']) ?></dd>
    <dt class="col-sm-4">Credit units</dt><dd class="col-sm-8"><?= (int) $course['credit_units'] ?></dd>
    <dt class="col-sm-4">Level</dt><dd class="col-sm-8"><?= (int) $course['level'] ?></dd>
    <dt class="col-sm-4">Semester</dt><dd class="col-sm-8"><?= e($course['semester']) ?></dd>
    <dt class="col-sm-4">Department</dt><dd class="col-sm-8"><?= e($course['department'] ?? '-') ?></dd>
    <dt class="col-sm-4">Status</dt>
    <dd class="col-sm-8">
      <?= $course['status'] === 'published'
          ? '<span class="pill pill-success">Published</span>'
          : '<span class="pill pill-warning">Draft</span>' ?>
    </dd>
  </dl>

  <?php if ($course['status'] === 'draft'): ?>
    <form method="post" class="d-flex flex-wrap gap-2">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
      <button class="btn btn-primary" type="submit"><?= icon('check2-circle') ?>Publish course</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/course_form.php?id=<?= (int) $course['id'] ?>">Edit details</a>
      <a class="btn btn-link" href="<?= BASE_URL ?>/admin/courses.php">Back</a>
    </form>
  <?php else: ?>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/courses.php">Back to courses</a>
  <?php endif; ?>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
