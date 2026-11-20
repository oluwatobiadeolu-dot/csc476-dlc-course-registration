<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');

$courses = db()->query('SELECT id, course_code, title FROM courses WHERE status = "published" ORDER BY course_code')->fetchAll();
$courseId = (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $message = trim($_POST['message'] ?? '');

    $ids = array_map('intval', array_column($courses, 'id'));
    if (!in_array($courseId, $ids, true)) {
        $errors[] = 'Select the course your report is about.';
    }
    if (strlen($message) < 10 || strlen($message) > 1000) {
        $errors[] = 'Describe the issue in 10 to 1000 characters. Do not include personal information.';
    }

    if (!$errors) {
        $ins = db()->prepare('INSERT INTO discrepancy_reports (student_id, course_id, message) VALUES (?, ?, ?)');
        $ins->execute([$user['id'], $courseId, $message]);
        flash('success', 'Your report was submitted. You can follow its status under My reports.');
        redirect('/student/reports.php');
    }
}

$pageTitle = 'Report a course issue';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Report a course issue', 'Tell the administrator about course details that look wrong or confusing') ?>
<div class="card form-card"><div class="card-body">
  <?= error_list($errors) ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label" for="course_id">Course</label>
      <select class="form-select" id="course_id" name="course_id" required>
        <option value="">Select course</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $courseId ? 'selected' : '' ?>><?= e($c['course_code']) ?> - <?= e($c['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-4">
      <label class="form-label" for="message">What looks wrong or confusing?</label>
      <textarea class="form-control" id="message" name="message" rows="5" maxlength="1000" required><?= e($message) ?></textarea>
      <div class="form-text">10 to 1000 characters. Do not include personal information.</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit">Submit report</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/student/reports.php">Cancel</a>
    </div>
  </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
