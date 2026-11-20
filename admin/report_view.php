<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT r.*, u.full_name, u.matric_no, c.course_code, c.title
     FROM discrepancy_reports r
     JOIN users u ON u.id = r.student_id
     JOIN courses c ON c.id = r.course_id
     WHERE r.id = ?'
);
$stmt->execute([$id]);
$report = $stmt->fetch();
if (!$report) {
    http_response_code(404);
    exit('Report not found.');
}

$errors = [];
$status = $report['status'];
$response = (string) $report['admin_response'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $status = $_POST['status'] ?? '';
    $response = trim($_POST['admin_response'] ?? '');

    if (!in_array($status, ['open', 'under_review', 'resolved'], true)) {
        $errors[] = 'Select a valid status.';
    }
    if (strlen($response) > 1000) {
        $errors[] = 'The response must be 1000 characters or fewer.';
    }

    if (!$errors) {
        $newResponse = $response === '' ? null : $response;
        if ($status === $report['status'] && $newResponse === $report['admin_response']) {
            flash('info', 'No changes to save. Change the status or write a response first.');
        } else {
            $upd = db()->prepare('UPDATE discrepancy_reports SET status = ?, admin_response = ? WHERE id = ?');
            $upd->execute([$status, $newResponse, $id]);
            flash('success', 'Saved. The student now sees this report as "' . ucfirst(str_replace('_', ' ', $status)) . '".');
        }
        redirect('/admin/report_view.php?id=' . $id);
    }
}

$pageTitle = 'Report details';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Course report', $report['course_code'] . ' - ' . $report['title'],
    '<a class="btn btn-outline-secondary" href="' . BASE_URL . '/admin/reports.php">' . icon('arrow-left') . 'All reports</a>') ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="card-title-row"><h2>Student's report</h2><?= report_badge($report['status']) ?></div>
      <dl class="row mb-0">
        <dt class="col-sm-4">Student</dt><dd class="col-sm-8"><?= e($report['full_name']) ?> (<?= e($report['matric_no']) ?>)</dd>
        <dt class="col-sm-4">Submitted</dt><dd class="col-sm-8"><?= e(date('j M Y, g:i A', strtotime($report['created_at']))) ?></dd>
        <dt class="col-sm-4">Last updated</dt><dd class="col-sm-8"><?= e(date('j M Y, g:i A', strtotime($report['updated_at']))) ?></dd>
        <dt class="col-sm-4">Message</dt><dd class="col-sm-8"><?= nl2br(e($report['message'])) ?></dd>
      </dl>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
      <div class="card-title-row"><h2>Update the student</h2></div>
      <?= error_list($errors) ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $report['id'] ?>">
        <div class="mb-3">
          <label class="form-label" for="status">Status</label>
          <select class="form-select" id="status" name="status">
            <?php foreach (['open' => 'Open', 'under_review' => 'Under review', 'resolved' => 'Resolved'] as $v => $l): ?>
              <option value="<?= e($v) ?>" <?= $status === $v ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="admin_response">Response to the student (optional)</label>
          <textarea class="form-control" id="admin_response" name="admin_response" rows="5" maxlength="1000"><?= e($response) ?></textarea>
          <div class="form-text">The student sees the status and this message under My reports.</div>
        </div>
        <button class="btn btn-primary" type="submit">Save and update student</button>
      </form>
    </div></div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
