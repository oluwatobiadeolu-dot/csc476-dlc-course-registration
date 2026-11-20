<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$id = (int) ($_GET['id'] ?? 0);
$editing = $id > 0;
$levels = [100, 200, 300, 400, 500];
$semesters = ['First', 'Second'];
$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();

$course = [
    'course_code' => '', 'title' => '', 'credit_units' => '',
    'course_type' => 'compulsory', 'level' => '', 'semester' => '', 'department_id' => '', 'status' => 'draft',
];

if ($editing) {
    $stmt = db()->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        http_response_code(404);
        exit('Course not found.');
    }
    $course = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $course['course_code']   = strtoupper(trim($_POST['course_code'] ?? ''));
    $course['title']         = trim($_POST['title'] ?? '');
    $course['credit_units']  = trim($_POST['credit_units'] ?? '');
    $course['level']         = trim($_POST['level'] ?? '');
    $course['semester']      = trim($_POST['semester'] ?? '');
    $course['department_id'] = trim($_POST['department_id'] ?? '');
    $course['course_type']   = trim($_POST['course_type'] ?? '');

    if (!preg_match('/^[A-Z]{2,4} ?[0-9]{3}$/', $course['course_code'])) {
        $errors[] = 'Enter a valid course code, for example CSC 476.';
    }
    if (strlen($course['title']) < 3 || strlen($course['title']) > 200) {
        $errors[] = 'Enter the course title (3 to 200 characters).';
    }
    if (!ctype_digit($course['credit_units']) || (int) $course['credit_units'] < 1 || (int) $course['credit_units'] > 6) {
        $errors[] = 'Credit units must be a number from 1 to 6.';
    }
    if (!in_array((int) $course['level'], $levels, true)) {
        $errors[] = 'Select a level.';
    }
    if (!in_array($course['semester'], $semesters, true)) {
        $errors[] = 'Select a semester.';
    }
    if (!in_array($course['course_type'], ['compulsory', 'elective'], true)) {
        $errors[] = 'Select whether the course is compulsory or elective.';
    }
    $deptIds = array_map('intval', array_column($departments, 'id'));
    if (!in_array((int) $course['department_id'], $deptIds, true)) {
        $errors[] = 'Select a department.';
    }

    if (!$errors) {
        $dup = db()->prepare('SELECT id FROM courses WHERE course_code = ? AND id <> ?');
        $dup->execute([$course['course_code'], $id]);
        if ($dup->fetch()) {
            $errors[] = 'Another course already uses this code.';
        }
    }

    if (!$errors) {
        if ($editing) {
            // edited courses go back to draft
            $upd = db()->prepare(
                'UPDATE courses SET course_code = ?, title = ?, credit_units = ?, course_type = ?, level = ?, semester = ?,
                 department_id = ?, status = "draft", published_at = NULL WHERE id = ?'
            );
            $upd->execute([
                $course['course_code'], $course['title'], (int) $course['credit_units'], $course['course_type'],
                (int) $course['level'], $course['semester'], (int) $course['department_id'], $id,
            ]);
            flash('success', 'Changes saved. The course is now a draft and needs review before students can see it.');
        } else {
            $ins = db()->prepare(
                'INSERT INTO courses (course_code, title, credit_units, course_type, level, semester, department_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "draft")'
            );
            $ins->execute([
                $course['course_code'], $course['title'], (int) $course['credit_units'], $course['course_type'],
                (int) $course['level'], $course['semester'], (int) $course['department_id'],
            ]);
            flash('success', 'Course saved as a draft. Review it to publish.');
        }
        redirect('/admin/courses.php');
    }
}

$pageTitle = $editing ? 'Edit course' : 'Add course';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header($editing ? 'Edit course' : 'Add a course', 'Saved changes are kept as a draft until the course is reviewed and published') ?>
<div class="card form-card"><div class="card-body">
  <?php if ($editing && $course['status'] === 'published'): ?>
    <div class="alert alert-warning" role="alert"><?= icon('exclamation-triangle-fill') ?><div>
      This course is published. Saving changes returns it to draft, and students will not see it until you review and publish it again.
    </div></div>
  <?php endif; ?>
  <?= error_list($errors) ?>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="row">
      <div class="col-md-5 mb-3">
        <label class="form-label" for="course_code">Course code</label>
        <input class="form-control" id="course_code" name="course_code" value="<?= e($course['course_code']) ?>" placeholder="CSC 476" required>
      </div>
      <div class="col-md-7 mb-3">
        <label class="form-label" for="credit_units">Credit units</label>
        <input class="form-control" type="number" min="1" max="6" id="credit_units" name="credit_units" value="<?= e($course['credit_units']) ?>" required>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label" for="title">Course title</label>
      <input class="form-control" id="title" name="title" value="<?= e($course['title']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="course_type">Course type</label>
      <select class="form-select" id="course_type" name="course_type" required>
        <option value="compulsory" <?= $course['course_type'] === 'compulsory' ? 'selected' : '' ?>>Compulsory</option>
        <option value="elective" <?= $course['course_type'] === 'elective' ? 'selected' : '' ?>>Elective</option>
      </select>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label" for="level">Level</label>
        <select class="form-select" id="level" name="level" required>
          <option value="">Select level</option>
          <?php foreach ($levels as $l): ?>
            <option value="<?= $l ?>" <?= (string) $l === (string) $course['level'] ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label" for="semester">Semester</label>
        <select class="form-select" id="semester" name="semester" required>
          <option value="">Select semester</option>
          <?php foreach ($semesters as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $course['semester'] ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mb-4">
      <label class="form-label" for="department_id">Department</label>
      <select class="form-select" id="department_id" name="department_id" required>
        <option value="">Select department</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= (int) $d['id'] ?>" <?= (string) $d['id'] === (string) $course['department_id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit">Save as draft</button>
      <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/admin/courses.php">Cancel</a>
    </div>
  </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
