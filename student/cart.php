<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');
$session = require_current_session();
$deadlines = get_deadlines((int) $session['id']);
$limits = unit_limits();

// drop cart items that are no longer valid
$courses = [];
foreach (cart_ids() as $id) {
    $c = find_eligible_course($user, $session, $id);
    if ($c) {
        $courses[(int) $c['id']] = $c;
    }
}
$_SESSION['cart'] = array_keys($courses);
$registeredIds = registered_course_ids((int) $user['id'], (int) $session['id']);
$currentUnits  = active_units((int) $user['id'], (int) $session['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'remove') {
        $removeId = (int) ($_POST['course_id'] ?? 0);
        $_SESSION['cart'] = array_values(array_diff(cart_ids(), [$removeId]));
        flash('info', 'Course removed from your cart.');
        redirect('/student/cart.php');
    }

    if ($action === 'register') {
        // deadline and unit limit are checked here, on the server
        if (!deadline_open($deadlines['registration_deadline'] ?? null)) {
            flash('danger', 'Registration rejected: the registration deadline has passed.');
            redirect('/student/cart.php');
        }
        if (!$courses) {
            flash('warning', 'Your cart is empty.');
            redirect('/student/cart.php');
        }

        $newUnits = 0;
        foreach ($courses as $c) {
            if (!in_array((int) $c['id'], $registeredIds, true)) {
                $newUnits += (int) $c['credit_units'];
            }
        }
        if ($limits['max'] > 0 && $currentUnits + $newUnits > $limits['max']) {
            flash('danger', 'Registration rejected: this would bring your total to ' . ($currentUnits + $newUnits)
                . ' units, above the configured maximum of ' . $limits['max'] . ' units.');
            redirect('/student/cart.php');
        }

        $pdo = db();
        $added = 0;
        $skipped = 0;
        $pdo->beginTransaction();
        try {
            $sel = $pdo->prepare('SELECT id, status FROM registrations WHERE student_id = ? AND course_id = ? AND session_id = ?');
            $ins = $pdo->prepare('INSERT INTO registrations (student_id, course_id, session_id, status) VALUES (?, ?, ?, "active")');
            $re  = $pdo->prepare('UPDATE registrations SET status = "active", dropped_at = NULL, registered_at = NOW() WHERE id = ?');

            foreach ($courses as $c) {
                $sel->execute([$user['id'], $c['id'], $session['id']]);
                $row = $sel->fetch();
                if ($row && $row['status'] === 'active') {
                    $skipped++;          // already registered
                    continue;
                }
                if ($row) {
                    $re->execute([$row['id']]);   // dropped before, register again
                } else {
                    $ins->execute([$user['id'], $c['id'], $session['id']]);
                }
                $added++;
            }
            $pdo->commit();
        } catch (Throwable $t) {
            $pdo->rollBack();
            flash('danger', 'Registration failed. Please try again.');
            redirect('/student/cart.php');
        }

        $_SESSION['cart'] = [];
        $msg = $added . ($added === 1 ? ' course registered.' : ' courses registered.');
        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' already registered and skipped.';
        }
        flash('success', $msg);
        $after = active_units((int) $user['id'], (int) $session['id']);
        if ($limits['min'] > 0 && $after < $limits['min']) {
            flash('warning', 'Your total is ' . $after . ' units, below the configured minimum of ' . $limits['min'] . '. You can add more courses while registration is open.');
        }
        redirect('/student/registrations.php');
    }
}

$cartUnits = array_sum(array_column($courses, 'credit_units'));
$projected = $currentUnits + $cartUnits;

$pageTitle = 'Cart';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Your cart', 'Courses waiting to be registered') ?>

<?= deadline_banner($session) ?>

<?php if (!$courses): ?>
  <?= empty_state('cart3', 'Your cart is empty', 'Pick the courses you need from the course list and they will appear here.',
      '<a class="btn btn-primary" href="' . BASE_URL . '/student/courses.php">Browse courses</a>') ?>
<?php else: ?>
<div class="table-responsive">
  <table class="table align-middle">
    <thead><tr><th>Code</th><th>Title</th><th>Type</th><th>Units</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($courses as $c): ?>
      <tr>
        <td><?= e($c['course_code']) ?></td>
        <td><?= e($c['title']) ?></td>
        <td><?= course_type_badge($c['course_type']) ?></td>
        <td><?= (int) $c['credit_units'] ?></td>
        <td class="text-end">
          <form method="post" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th colspan="3">Units in cart</th><th><?= (int) $cartUnits ?></th><th></th></tr></tfoot>
  </table>
</div>

<div class="card mb-3"><div class="card-body">
  <div class="card-title-row"><h2>Summary</h2></div>
  <div>Already registered: <strong><?= (int) $currentUnits ?></strong> units &middot; Total after registering: <strong id="cart-total"><?= (int) $projected ?></strong> units</div>
  <?php if ($limits['max'] > 0 && $projected > $limits['max']): ?>
    <div class="small status-bad mt-1">Above the configured maximum of <?= (int) $limits['max'] ?> units. Remove a course before registering.</div>
  <?php elseif ($limits['min'] > 0 && $projected < $limits['min']): ?>
    <div class="small status-warn mt-1"><?= (int) ($limits['min'] - $projected) ?> units below the configured minimum of <?= (int) $limits['min'] ?>.</div>
  <?php else: ?>
    <div class="small status-ok mt-1">Within the configured limits.</div>
  <?php endif; ?>
  <div class="form-text"><?= e(limits_text($limits)) ?></div>
</div></div>

<form method="post" class="d-flex flex-wrap gap-2">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="register">
  <button class="btn btn-primary" type="submit"><?= icon('check2-circle') ?>Register selected courses</button>
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/student/courses.php">Add more courses</a>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
