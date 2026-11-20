<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$sessions = db()->query('SELECT * FROM academic_sessions ORDER BY session_name DESC, semester')->fetchAll();
if (!$sessions) {
    exit('No academic sessions found.');
}
$selectedId = (int) ($_GET['session_id'] ?? $_POST['session_id'] ?? 0);
if (!$selectedId) {
    $cur = current_session();
    $selectedId = $cur ? (int) $cur['id'] : (int) $sessions[0]['id'];
}
$selected = null;
foreach ($sessions as $s) {
    if ((int) $s['id'] === $selectedId) {
        $selected = $s;
    }
}
if (!$selected) {
    exit('Session not found.');
}

$existing = get_deadlines($selectedId);
$regVal  = $existing ? date('Y-m-d\TH:i', strtotime($existing['registration_deadline'])) : '';
$dropVal = $existing ? date('Y-m-d\TH:i', strtotime($existing['drop_deadline'])) : '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $regVal  = trim($_POST['registration_deadline'] ?? '');
    $dropVal = trim($_POST['drop_deadline'] ?? '');

    $reg  = DateTime::createFromFormat('Y-m-d\TH:i', $regVal);
    $drop = DateTime::createFromFormat('Y-m-d\TH:i', $dropVal);
    if (!$reg || $reg->format('Y-m-d\TH:i') !== $regVal) {
        $errors[] = 'Enter a valid registration deadline.';
    }
    if (!$drop || $drop->format('Y-m-d\TH:i') !== $dropVal) {
        $errors[] = 'Enter a valid course drop deadline.';
    }

    if (!$errors) {
        if ($existing) {
            $stmt = db()->prepare('UPDATE deadlines SET registration_deadline = ?, drop_deadline = ? WHERE session_id = ?');
            $stmt->execute([$reg->format('Y-m-d H:i:s'), $drop->format('Y-m-d H:i:s'), $selectedId]);
        } else {
            $stmt = db()->prepare('INSERT INTO deadlines (session_id, registration_deadline, drop_deadline) VALUES (?, ?, ?)');
            $stmt->execute([$selectedId, $reg->format('Y-m-d H:i:s'), $drop->format('Y-m-d H:i:s')]);
        }
        flash('success', 'Deadlines saved for ' . session_label($selected) . '.');
        redirect('/admin/deadlines.php?session_id=' . $selectedId);
    }
}

$pageTitle = 'Deadlines';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Registration and drop deadlines', 'Set per session and semester. Late requests are refused by the server.') ?>
<div class="card form-card"><div class="card-body">
  <form method="get" class="mb-4">
    <label class="form-label" for="session_id">Session and semester</label>
    <select class="form-select" id="session_id" name="session_id" onchange="this.form.submit()">
      <?php foreach ($sessions as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === $selectedId ? 'selected' : '' ?>>
          <?= e(session_label($s)) ?><?= $s['is_current'] ? ' (current)' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?= error_list($errors) ?>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="session_id" value="<?= $selectedId ?>">
    <div class="mb-3">
      <label class="form-label" for="registration_deadline">Registration deadline</label>
      <input class="form-control" type="datetime-local" id="registration_deadline" name="registration_deadline" value="<?= e($regVal) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="drop_deadline">Course drop deadline</label>
      <input class="form-control" type="datetime-local" id="drop_deadline" name="drop_deadline" value="<?= e($dropVal) ?>" required>
      <div class="form-text">Times use Nigerian time (WAT). Until a deadline is set, nothing is blocked for that session.</div>
    </div>
    <button class="btn btn-primary" type="submit">Save deadlines</button>
  </form>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
