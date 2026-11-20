<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('admin');

$filter = $_GET['status'] ?? 'all';
$sql = 'SELECT c.*, d.name AS department
        FROM courses c LEFT JOIN departments d ON d.id = c.department_id';
$params = [];
if (in_array($filter, ['draft', 'published'], true)) {
    $sql .= ' WHERE c.status = ?';
    $params[] = $filter;
} else {
    $filter = 'all';
}
$sql .= ' ORDER BY c.level, c.semester, c.course_code';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$pageTitle = 'Courses';
require __DIR__ . '/../includes/header.php';
?>
<?= page_header('Course offerings', 'Courses stay hidden from students until they are reviewed and published',
    '<a class="btn btn-primary" href="' . BASE_URL . '/admin/course_form.php">' . icon('plus-lg') . 'Add course</a>') ?>

<ul class="nav nav-pills mb-3">
  <?php foreach (['all' => 'All', 'draft' => 'Drafts', 'published' => 'Published'] as $key => $label): ?>
    <li class="nav-item"><a class="nav-link <?= $filter === $key ? 'active' : '' ?>" href="?status=<?= e($key) ?>"><?= e($label) ?></a></li>
  <?php endforeach; ?>
</ul>

<?php if (!$courses): ?>
  <?= empty_state('journal-plus', 'No courses here yet', 'Add a course, save it as a draft, then review and publish it.',
      '<a class="btn btn-primary" href="' . BASE_URL . '/admin/course_form.php">Add course</a>') ?>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead>
      <tr><th>Code</th><th>Title</th><th>Type</th><th>Units</th><th>Level</th><th>Semester</th><th>Department</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($courses as $c): ?>
      <tr>
        <td><strong><?= e($c['course_code']) ?></strong></td>
        <td><?= e($c['title']) ?></td>
        <td><?= course_type_badge($c['course_type']) ?></td>
        <td><?= (int) $c['credit_units'] ?></td>
        <td><?= (int) $c['level'] ?></td>
        <td><?= e($c['semester']) ?></td>
        <td><?= e($c['department'] ?? '-') ?></td>
        <td>
          <?php if ($c['status'] === 'published'): ?>
            <span class="pill pill-success">Published</span>
          <?php else: ?>
            <span class="pill pill-warning">Draft</span>
          <?php endif; ?>
        </td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/admin/course_form.php?id=<?= (int) $c['id'] ?>">Edit</a>
          <?php if ($c['status'] === 'draft'): ?>
            <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/admin/course_review.php?id=<?= (int) $c['id'] ?>">Review</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
