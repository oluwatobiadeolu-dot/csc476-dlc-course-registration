<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect_by_role();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, full_name, email, password_hash, role, level, department_id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);   // new session id after login
        unset($row['password_hash']);
        $_SESSION['user'] = $row;
        redirect_by_role();
    }
    // same message for a wrong email or a wrong password
    $error = 'Email or password is incorrect.';
}

$pageTitle = 'Log in';
$authLayout = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-shell">
  <?php require __DIR__ . '/includes/auth_aside.php'; ?>
  <section class="auth-panel">
    <div class="auth-card">
      <h2>Log in</h2>
      <p class="muted mb-4">Enter your email and password to continue.</p>
      <?= render_flashes() ?>
      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= icon('exclamation-octagon-fill') ?><div><?= e($error) ?></div></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <div class="input-group">
            <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
            <button class="btn btn-outline-secondary" type="button" data-toggle-password="password">Show</button>
          </div>
        </div>
        <button class="btn btn-primary w-100" type="submit">Log in</button>
      </form>
      <p class="auth-switch">New student? <a href="<?= BASE_URL ?>/register.php">Create an account</a></p>
    </div>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
