<?php
/**
 * Header Include File
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026
 * @date      2026-02-24
 * @version   1.0
 */
//
include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));

/* Dashboard folder for each role: 1 = admin, 2 = user, 3 = guest */
$role_dirs = array(1 => '/admin', 2 => '/user', 3 => '/guest');
$is_logged_in = isset($_SESSION['user_name']) && isset($_SESSION['user_role']);
$dashboard_url = BASE_URL . ($is_logged_in && isset($role_dirs[$_SESSION['user_role']]) ? $role_dirs[$_SESSION['user_role']] . '/' : '/');
$current_page = isset($page_name) ? $page_name : '';
?>
<?php
// Header Section
?>
<header class="mb-4">
<?php
// navigation bar
?>
<nav class="navbar navbar-expand-lg app-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?php echo $dashboard_url; ?>">
      <i class="bi bi-activity"></i> Workout Log
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarText">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if (!$is_logged_in) { /* Show login links only when user is NOT logged in */ ?>
          <li class="nav-item"><a class="nav-link<?php echo $current_page == 'home' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/">Login</a></li>
          <li class="nav-item"><a class="nav-link<?php echo $current_page == 'create_account' ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>/create_account.php">Create Account</a></li>
        <?php } else { ?>
          <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?php echo $dashboard_url; ?>">Dashboard</a></li>
          <?php if ($_SESSION['user_role'] == 2) { /* Workout pages - not built yet */ ?>
            <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">Log Workout</a></li>
            <li class="nav-item"><a class="nav-link disabled" aria-disabled="true">History</a></li>
          <?php } ?>
        <?php } ?>
      </ul>
      <?php if ($is_logged_in && isset($_SESSION['user_first'])) { /* User Information */ ?>
        <span class="navbar-text me-3">
          <i class="bi bi-person-circle"></i>
          <?php echo htmlspecialchars($_SESSION['user_first']); ?>
          <span class="badge text-bg-light text-capitalize"><?php echo htmlspecialchars($_SESSION['user_type']); ?></span>
        </span>
        <a class="btn btn-sm btn-outline-light" href="<?php echo SRC_PATH; ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      <?php } ?>
    </div>
  </div>
</nav>
</header>
