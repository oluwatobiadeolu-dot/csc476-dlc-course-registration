</main>
<?php if (empty($authLayout)): ?>
<footer class="app-footer d-print-none">
  <div class="container d-flex justify-content-between flex-wrap gap-2">
    <span>CSC 476 academic prototype, Department of Computer Science, University of Ibadan</span>
    <span>Made-up data only</span>
  </div>
</footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app.js') ?: 1 ?>"></script>
<?php if (!empty($extraScripts)) { echo $extraScripts; } ?>
</body>
</html>
