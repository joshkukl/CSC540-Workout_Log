<?php
/**
 * Footer Include File
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026
 * @date      2026-02-24
 * @version   1.0
 */
include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));
?>
<footer class="footer mt-auto">
  <div class="container py-3">
    <div class="row">
      <div class="col-sm fs-6 text-center text-body-secondary">
        <small>&copy; <?php echo date("Y"); // Shows current year ?> CSC 540 Workout Log</small>
      </div>
    </div>
  </div>
</footer>

<!-- Core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->


<!-- Required for bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<!-- Custom app javascript -->
<script src="<?php echo BASE_URL; ?>/js/main.js"></script>
