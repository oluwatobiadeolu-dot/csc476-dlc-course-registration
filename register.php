<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect_by_role();
}

$errors = [];
$old = ['full_name' => '', 'matric_no' => '', 'email' => '', 'department_id' => '', 'level' => ''];
$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$levels = [100, 200, 300, 400, 500];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($old['full_name']) < 3) {
        $errors[] = 'Enter your full name.';
    }
    if (!preg_match('/^[A-Za-z0-9\/-]{3,30}$/', $old['matric_no'])) {
        $errors[] = 'Enter a valid matric number (letters, numbers, / or - only).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    $deptIds = array_map('intval', array_column($departments, 'id'));
    if (!in_array((int) $old['department_id'], $deptIds, true)) {
        $errors[] = 'Select your department.';
    }
    if (!in_array((int) $old['level'], $levels, true)) {
        $errors[] = 'Select your level.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        $check = db()->prepare('SELECT 1 FROM users WHERE email = ? OR matric_no = ?');
        $check->execute([$old['email'], $old['matric_no']]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email or matric number already exists.';
        }
    }

    if (!$errors) {
        $insert = db()->prepare(
            'INSERT INTO users (full_name, matric_no, email, password_hash, role, department_id, level)
             VALUES (?, ?, ?, ?, "student", ?, ?)'
        );
        $insert->execute([
            $old['full_name'],
            $old['matric_no'],
            $old['email'],
            password_hash($password, PASSWORD_DEFAULT),
            (int) $old['department_id'],
            (int) $old['level'],
        ]);
        flash('success', 'Account created. You can now log in.');
        redirect('/login.php');
    }
}

$pageTitle = 'Create account';
$authLayout = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-shell">
  <?php require __DIR__ . '/includes/auth_aside.php'; ?>
  <section class="auth-panel">
    <div class="auth-card wide">
      <h2>Create a student account</h2>
      <p class="muted mb-4">Use the details on your student record.</p>
      <?= error_list($errors) ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="row">
          <div class="col-md-7 mb-3">
            <label class="form-label" for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" autocomplete="name" required>
          </div>
          <div class="col-md-5 mb-3">
            <label class="form-label" for="matric_no">Matric number</label>
            <input class="form-control" id="matric_no" name="matric_no" value="<?= e($old['matric_no']) ?>" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" required>
        </div>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label" for="department_id">Department</label>
            <select class="form-select" id="department_id" name="department_id" required>
              <option value="">Select department</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= (string) $d['id'] === $old['department_id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label" for="level">Level</label>
            <select class="form-select" id="level" name="level" required>
              <option value="">Select</option>
              <?php foreach ($levels as $l): ?>
                <option value="<?= $l ?>" <?= (string) $l === $old['level'] ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-4">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
            <div class="form-text">At least 8 characters.</div>
          </div>
          <div class="col-md-6 mb-4">
            <label class="form-label" for="confirm_password">Confirm password</label>
            <input class="form-control" type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
          </div>
        </div>
        <button class="btn btn-primary w-100" type="submit">Create account</button>
      </form>
      <p class="auth-switch">Already registered? <a href="<?= BASE_URL ?>/login.php">Log in</a></p>
    </div>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
