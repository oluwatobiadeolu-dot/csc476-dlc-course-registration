<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$filter = $_GET['status'] ?? 'all';
$sql = 'SELECT r.id, r.status, r.created_at, u.full_name, u.matric_no, c.course_code
        FROM discrepancy_reports r
        JOIN users u ON u.id = r.student_id
        JOIN courses c ON c.id = r.course_id';
$params = [];
if (in_array($filter, ['open', 'under_review', 'resolved'], true)) {
    $sql .= ' WHERE r.status = ?';
    $params[] = $filter;
} else {
    $filter = 'all';
}
$sql .= ' ORDER BY r.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$pageTitle = 'Course reports';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Student course reports', 'Issues students have reported about course details') ?>
<ul class="nav nav-pills mb-3">
  <?php foreach (['all' => 'All', 'open' => 'Open', 'under_review' => 'Under review', 'resolved' => 'Resolved'] as $key => $label): ?>
    <li class="nav-item"><a class="nav-link <?= $filter === $key ? 'active' : '' ?>" href="?status=<?= e($key) ?>"><?= e($label) ?></a></li>
  <?php endforeach; ?>
</ul>

<?php if (!$reports): ?>
  <?= empty_state('flag', 'No reports here', 'Reports submitted by students will appear in this list.') ?>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead><tr><th>Submitted</th><th>Student</th><th>Course</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reports as $r): ?>
      <tr>
        <td><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
        <td><?= e($r['full_name']) ?> <small class="muted"><?= e($r['matric_no']) ?></small></td>
        <td><strong><?= e($r['course_code']) ?></strong></td>
        <td><?= report_badge($r['status']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/admin/report_view.php?id=<?= (int) $r['id'] ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
