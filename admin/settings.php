<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$limits = unit_limits();
$min = (string) $limits['min'];
$max = (string) $limits['max'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $min = trim($_POST['min_units'] ?? '');
    $max = trim($_POST['max_units'] ?? '');

    foreach (['Minimum' => $min, 'Maximum' => $max] as $label => $v) {
        if (!ctype_digit($v) || (int) $v > 60) {
            $errors[] = $label . ' units must be a whole number from 0 to 60 (0 means no limit).';
        }
    }
    if (!$errors && (int) $max > 0 && (int) $min > (int) $max) {
        $errors[] = 'The minimum cannot be higher than the maximum.';
    }

    if (!$errors) {
        $save = db()->prepare('REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)');
        $save->execute(['min_units', (string) (int) $min]);
        $save->execute(['max_units', (string) (int) $max]);
        flash('success', 'Unit limits saved.');
        redirect('/admin/settings.php');
    }
}

$pageTitle = 'Unit limits';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Unit limits', 'Used by the unit calculator on the student course page') ?>
<div class="card form-card"><div class="card-body">
  <p class="muted">The maximum is enforced when students add or register courses. The minimum only shows a warning. Use 0 for no limit.</p>
  <?= error_list($errors) ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label" for="min_units">Minimum units per semester</label>
        <input class="form-control" id="min_units" name="min_units" inputmode="numeric" value="<?= e($min) ?>" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label" for="max_units">Maximum units per semester</label>
        <input class="form-control" id="max_units" name="max_units" inputmode="numeric" value="<?= e($max) ?>" required>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Save limits</button>
  </form>
  <p class="form-text mt-3 mb-0">The starting values (15 and 24) are placeholders for testing. They are not official University policy unless you verify them.</p>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
