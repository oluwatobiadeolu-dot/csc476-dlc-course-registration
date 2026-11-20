<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_login('student');
$session = require_current_session();
$deadlines = get_deadlines((int) $session['id']);
$regOpen = deadline_open($deadlines['registration_deadline'] ?? null);
$limits = unit_limits();

$q = trim($_GET['q'] ?? '');
// own level by default; searching or choosing "all levels" also shows lower levels for carryovers
$allLevels = ($_GET['scope'] ?? '') === 'all' || $q !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $ids = array_map('intval', (array) ($_POST['course_ids'] ?? []));
    if (isset($_POST['course_id'])) {
        $ids[] = (int) $_POST['course_id'];
    }
    $ids = array_values(array_unique(array_filter($ids)));

    $registered = registered_course_ids((int) $user['id'], (int) $session['id']);
    $inCart = cart_ids();

    if (!$regOpen) {
        flash('danger', 'The registration deadline has passed. You can no longer add courses.');
    } elseif (!$ids) {
        flash('warning', 'Select at least one course.');
    } else {
        $valid = [];
        $already = 0;
        $invalid = 0;
        foreach ($ids as $id) {
            $c = find_eligible_course($user, $session, $id);
            if (!$c) {
                $invalid++;
                continue;
            }
            if (in_array($id, $registered, true)) {
                $already++;
                continue;
            }
            if (in_array($id, $inCart, true)) {
                continue;
            }
            $valid[$id] = $c;
        }

        $newUnits = array_sum(array_column($valid, 'credit_units'));
        $current  = active_units((int) $user['id'], (int) $session['id']) + cart_units($user, $session);

        if ($limits['max'] > 0 && $current + $newUnits > $limits['max']) {
            flash('danger', 'Not added: these courses would bring your total to ' . ($current + $newUnits)
                . ' units, above the configured maximum of ' . $limits['max'] . ' units.');
        } elseif ($valid) {
            $_SESSION['cart'] = array_values(array_unique(array_merge($inCart, array_keys($valid))));
            flash('success', count($valid) . (count($valid) === 1 ? ' course added to your cart.' : ' courses added to your cart.'));
            if ($already > 0) {
                flash('warning', $already . ' already registered and skipped.');
            }
        } elseif ($already > 0) {
            flash('warning', 'You have already registered this course.');
        } elseif ($invalid > 0) {
            flash('danger', 'That course is not available to you.');
        } else {
            flash('info', 'Those courses are already in your cart.');
        }
    }
    $back = [];
    if ($q !== '') {
        $back['q'] = $q;
    }
    if (($_GET['scope'] ?? '') === 'all') {
        $back['scope'] = 'all';
    }
    redirect('/student/courses.php' . ($back ? '?' . http_build_query($back) : ''));
}

$sql = 'SELECT * FROM courses
        WHERE status = "published" AND level ' . ($allLevels ? '<=' : '=') . ' ? AND semester = ? AND department_id = ?';
$params = [(int) $user['level'], $session['semester'], (int) $user['department_id']];
if ($q !== '') {
    $sql .= ' AND (course_code LIKE ? OR title LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= ' ORDER BY level DESC, course_type, course_code';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$registered = registered_course_ids((int) $user['id'], (int) $session['id']);
$inCart = cart_ids();
$regUnits  = active_units((int) $user['id'], (int) $session['id']);
$cartUnits = cart_units($user, $session);
$baseUnits = $regUnits + $cartUnits;

$selectable = 0;
foreach ($courses as $c) {
    $cid = (int) $c['id'];
    if (!in_array($cid, $registered, true) && !in_array($cid, $inCart, true)) {
        $selectable++;
    }
}

ob_start();
?>
<script>
(function () {
  const base = <?= (int) $baseUnits ?>, min = <?= (int) $limits['min'] ?>, max = <?= (int) $limits['max'] ?>;
  const picks = document.querySelectorAll('.pick');
  const out = { selected: document.getElementById('u-selected'), total: document.getElementById('u-total'),
                status: document.getElementById('u-status'), btn: document.getElementById('add-btn'), bar: document.getElementById('u-bar') };
  if (!out.total) { return; }
  function update() {
    let sel = 0;
    picks.forEach(function (p) { if (p.checked) { sel += parseInt(p.dataset.units, 10); } });
    const total = base + sel;
    out.selected.textContent = sel;
    out.total.textContent = total;
    if (out.bar && max > 0) {
      out.bar.style.width = Math.min(100, total / max * 100) + '%';
      out.bar.className = 'progress-bar' + (total > max ? ' bg-danger' : '');
    }
    let msg = 'Within the configured limits.', cls = 'status-ok', block = false;
    if (max > 0 && total > max) { msg = 'Above the configured maximum of ' + max + ' units. Remove a course to continue.'; cls = 'status-bad'; block = true; }
    else if (min > 0 && total < min) { msg = (min - total) + ' units below the configured minimum of ' + min + '.'; cls = 'status-warn'; }
    out.status.textContent = msg;
    out.status.className = 'small mt-2 ' + cls;
    if (out.btn) { out.btn.disabled = block || sel === 0; }
  }
  picks.forEach(function (p) { p.addEventListener('change', update); });
  const allBtn = document.getElementById('pick-compulsory');
  if (allBtn) {
    allBtn.addEventListener('click', function () {
      picks.forEach(function (p) { if (p.dataset.type === 'compulsory') { p.checked = true; } });
      update();
    });
  }
  update();
})();
</script>
<?php
$extraScripts = ob_get_clean();

$pageTitle = 'Courses';
require __DIR__ . '/../includes/header.php';
$scopeParam = ($_GET['scope'] ?? '') === 'all' ? 'scope=all' : '';
$barMax = (int) $limits['max'];
$barPct = $barMax > 0 ? min(100, (int) round($baseUnits / $barMax * 100)) : 0;
?>
<?= page_header('Available courses', session_label($session) . ' · ' . (int) $user['level'] . ' Level') ?>

<?= deadline_banner($session) ?>

<div class="toolbar">
  <ul class="nav nav-pills">
    <li class="nav-item"><a class="nav-link <?= !$allLevels ? 'active' : '' ?>" href="?">My level (<?= (int) $user['level'] ?>)</a></li>
    <li class="nav-item"><a class="nav-link <?= $allLevels ? 'active' : '' ?>" href="?scope=all">All levels up to mine (carryovers)</a></li>
  </ul>
  <form class="search-box" method="get">
    <?php if ($scopeParam !== ''): ?><input type="hidden" name="scope" value="all"><?php endif; ?>
    <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search code or title, all levels" aria-label="Search courses">
    <button class="btn btn-primary" type="submit">Search</button>
    <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="?<?= e($scopeParam) ?>">Clear</a><?php endif; ?>
  </form>
</div>

<?php if (!$courses): ?>
  <?= empty_state('search', 'No courses found',
      ($q !== '' ? 'Nothing matches your search ' : 'There are no published courses ') . 'for your department in the current semester.'
      . (!$allLevels ? ' Carryover courses are under "All levels up to mine".' : ''),
      !$allLevels ? '<a class="btn btn-outline-secondary" href="?scope=all">Show all levels</a>' : '') ?>
<?php else: ?>
<form method="post">
  <?= csrf_field() ?>
  <?php if ($regOpen && $selectable > 0): ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
      <span class="muted small">Tick the courses you want, then send them to your cart.</span>
      <button class="btn btn-sm btn-outline-secondary" type="button" id="pick-compulsory">Select all compulsory</button>
    </div>
  <?php endif; ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th style="width:2.5rem"></th><th>Code</th><th>Title</th><th>Level</th><th>Type</th><th>Units</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($courses as $c): $cid = (int) $c['id']; ?>
        <?php $isReg = in_array($cid, $registered, true); $isCart = in_array($cid, $inCart, true); ?>
        <tr>
          <td>
            <?php if ($regOpen && !$isReg && !$isCart): ?>
              <input class="form-check-input pick" type="checkbox" name="course_ids[]" value="<?= $cid ?>"
                     data-units="<?= (int) $c['credit_units'] ?>" data-type="<?= e($c['course_type']) ?>"
                     aria-label="Select <?= e($c['course_code']) ?>">
            <?php endif; ?>
          </td>
          <td><strong><?= e($c['course_code']) ?></strong></td>
          <td><?= e($c['title']) ?></td>
          <td><?= (int) $c['level'] ?><?php if ((int) $c['level'] < (int) $user['level']): ?> <span class="pill pill-warning">Carryover</span><?php endif; ?></td>
          <td><?= course_type_badge($c['course_type']) ?></td>
          <td><?= (int) $c['credit_units'] ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-link" href="<?= BASE_URL ?>/student/report_new.php?course_id=<?= $cid ?>">Report issue</a>
            <?php if ($isReg): ?>
              <span class="pill pill-success">Registered</span>
            <?php elseif ($isCart): ?>
              <span class="pill pill-info">In cart</span>
            <?php elseif (!$regOpen): ?>
              <span class="pill pill-secondary">Registration closed</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card calc-card">
    <div class="card-body py-3">
      <div class="row text-center g-2">
        <div class="col-3"><div class="calc-num" id="u-registered"><?= (int) $regUnits ?></div><small>Registered</small></div>
        <div class="col-3"><div class="calc-num" id="u-cart"><?= (int) $cartUnits ?></div><small>In cart</small></div>
        <div class="col-3"><div class="calc-num" id="u-selected">0</div><small>Selected now</small></div>
        <div class="col-3"><div class="calc-num" id="u-total"><?= (int) $baseUnits ?></div><small>Total units</small></div>
      </div>
      <?php if ($barMax > 0): ?>
        <div class="progress unit-bar" role="progressbar" aria-label="Unit load"><div class="progress-bar" id="u-bar" style="width: <?= $barPct ?>%"></div></div>
      <?php endif; ?>
      <div id="u-status" class="small mt-2" aria-live="polite"></div>
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-1">
        <div class="form-text m-0"><?= e(limits_text($limits)) ?></div>
        <?php if ($regOpen && $selectable > 0): ?>
          <button class="btn btn-primary" type="submit" id="add-btn"><?= icon('cart-plus') ?>Add selected to cart</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
