<?php
/**
 * User Dashboard Page
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026
 * @date      2026-02-24
 * @version   1.1
 * new update: 2026-10-20 - workout dashboard
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // set to 1 to display errors, 0 to hide them

  /* Quick Paths */
  /* note the 2 after __FILE__, because it's 2 directories deep */
  include_once (realpath(dirname(__FILE__, 2).'/php/session.php'));
  include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));

  /* Check Role */
  include_once (ROOT_SRC_PATH .'/check_user.php'); // Check user role and redirect if not authorized

  /* Page Name */
  $page_name = "user"; // Set page name for active link in header
  $page_title = "Dashboard";

  /* Greeting based on time of day */
  $hour = (int) date('G');
  if ($hour < 12) {
    $greeting = "Good morning";
  } elseif ($hour < 18) {
    $greeting = "Good afternoon";
  } else {
    $greeting = "Good evening";
  }

?>
<!doctype html>
<html lang="en">
  <head>
  <?php include_once (ROOT_PATH . '/include/head.php'); ?>
  </head>
  <body class="<?php echo $page_name; ?>">

  <?php include_once (ROOT_PATH . '/include/header.php'); ?>
    <main role="main" class="container">

      <?php /* Welcome Banner */ ?>
      <section class="dashboard-hero mb-4">
        <p class="text-uppercase small mb-1 opacity-75"><?php echo date('l, F j'); ?></p>
        <h1 class="mb-2"><?php echo $greeting . ', ' . htmlspecialchars($_SESSION['user_first']); ?></h1>
        <p class="lead mb-0">Ready to train? Log today's workout or look back at your progress.</p>
      </section>

      <?php /* Main Actions */ ?>
      <section class="row g-4 mb-4">
        <div class="col-md-6">
          <div class="app-card action-card">
            <div class="action-icon"><i class="bi bi-plus-lg"></i></div>
            <h2 class="h4">Log a Workout</h2>
            <p class="text-body-secondary">Record your exercises, sets, reps and weight for today's session.</p>
            <div>
              <a href="<?php echo BASE_URL; ?>/user/log_workout.php" class="btn btn-primary">
                <i class="bi bi-pencil-square"></i> Start Logging
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="app-card action-card">
            <div class="action-icon"><i class="bi bi-clock-history"></i></div>
            <h2 class="h4">Workout History <span class="badge text-bg-secondary fs-6 fw-normal align-middle">Coming soon</span></h2>
            <p class="text-body-secondary">Browse past workouts and see how your training has changed over time.</p>
            <div>
              <?php /* TODO: link to history.php once it is built */ ?>
              <button type="button" class="btn btn-outline-secondary" disabled>
                <i class="bi bi-list-ul"></i> View History
              </button>
            </div>
          </div>
        </div>
      </section>

      <?php /* Weekly Summary - values will come from the workout tables once they exist */ ?>
      <section class="mb-4">
        <h2 class="h5 mb-3">This Week</h2>
        <div class="row g-3">
          <div class="col-sm-4">
            <div class="app-card stat-card">
              <div class="stat-label"><i class="bi bi-calendar-check"></i> Workouts</div>
              <div class="stat-value">&mdash;</div>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="app-card stat-card">
              <div class="stat-label"><i class="bi bi-stopwatch"></i> Total Time</div>
              <div class="stat-value">&mdash;</div>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="app-card stat-card">
              <div class="stat-label"><i class="bi bi-fire"></i> Day Streak</div>
              <div class="stat-value">&mdash;</div>
            </div>
          </div>
        </div>
      </section>

      <?php /* Recent Workouts */ ?>
      <section>
        <h2 class="h5 mb-3">Recent Workouts</h2>
        <div class="app-card empty-state">
          <i class="bi bi-journal-text d-block mb-2"></i>
          <p class="mb-0">No workouts logged yet. Your recent sessions will show up here.</p>
        </div>
      </section>

    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
