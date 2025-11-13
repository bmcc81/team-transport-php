<?php if (isset($_SESSION['success'])): ?>
<div id="successToast" class="toast align-items-center text-bg-success border-0 shadow-lg position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
  <div class="d-flex">
    <div class="toast-body">
      <strong>✅ <?= htmlspecialchars($_SESSION['success']); ?></strong>
    </div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div id="errorToast" class="toast align-items-center text-bg-danger border-0 shadow-lg position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
  <div class="d-flex">
    <div class="toast-body">
      <strong>⚠️ <?= htmlspecialchars($_SESSION['error']); ?></strong>
    </div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['info'])): ?>
<div id="infoToast" class="toast align-items-center text-bg-info border-0 shadow-lg position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
  <div class="d-flex">
    <div class="toast-body text-dark">
      <strong>ℹ️ <?= htmlspecialchars($_SESSION['info']); ?></strong>
    </div>
    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
  </div>
</div>
<?php endif; ?>

<script src="../styles/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  ['successToast', 'errorToast', 'infoToast'].forEach(id => {
    const el = document.getElementById(id);
    if (el) new bootstrap.Toast(el, { delay: 4000 }).show();
  });
});
</script>

<?php
// clear toast messages after rendering
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['info']);
?>
