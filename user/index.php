<?php
/**
 * User Dashboard Page
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026
 * @date      2026-02-24
 * @version   1.1
 * new update: 2026-10-20 - workout dashboard
 * new update: 2026-11-03 - weekly stats and recent workouts
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // set to 1 to display errors, 0 to hide them

  /* Quick Paths */
  /* note the 2 after __FILE__, because it's 2 directories deep */
  include_once (realpath(dirname(__FILE__, 2).'/php/session.php'));
  include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));

  /* Check Role */
  include_once (ROOT_SRC_PATH .'/check_user.php'); // Check user role and redirect if not authorized
  include_once (ROOT_SRC_PATH .'/format.php');

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

  /* Every workout, newest first */
  $select_workouts = $db_connection->prepare(
    "SELECT w.workout_id, w.workout_date, t.type_name, w.facility_name, w.duration_minutes, w.bodyweight_lbs, COUNT(s.set_id) AS set_count
    FROM Workouts w
    INNER JOIN Workout_Types t ON w.type_id = t.type_id
    LEFT JOIN Sets s ON w.workout_id = s.workout_id
    WHERE w.user_id = ?
    GROUP BY w.workout_id
    ORDER BY w.workout_date DESC, w.workout_id DESC");
  $select_workouts->bind_param("i", $user_id);
  $select_workouts->execute();
  $workouts = $select_workouts->get_result()->fetch_all(MYSQLI_ASSOC);
  $select_workouts->close();

  /* This week's workouts and time (weeks start on Monday, same as the history page) */
  $week_start = date('Y-m-d', strtotime('monday this week'));
  $week_workouts = array();
  $week_minutes = 0;
  $week_untimed = 0;
  $workout_days = array(); // date => true
  foreach ($workouts as $workout) {
    $workout_days[$workout['workout_date']] = true;
    if ($workout['workout_date'] >= $week_start) {
      $week_workouts[] = $workout;
      $week_minutes += (int) $workout['duration_minutes'];
      if (!$workout['duration_minutes']) {
        $week_untimed++;
      }
    }
  }

  /* Day streak: days in a row with a workout, still alive if the last one was yesterday */
  $today = date('Y-m-d');
  $logged_today = isset($workout_days[$today]);
  $streak_day = $logged_today ? $today : date('Y-m-d', strtotime('-1 day'));
  $day_streak = 0;
  while (isset($workout_days[$streak_day])) {
    $day_streak++;
    $streak_day = date('Y-m-d', strtotime('-1 day', strtotime($streak_day)));
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
            <h2 class="h4">Workout History</h2>
            <p class="text-body-secondary">Browse past workouts and see how your training has changed over time.</p>
            <div>
              <a href="<?php echo BASE_URL; ?>/user/history.php" class="btn btn-outline-secondary">
                <i class="bi bi-list-ul"></i> View History
              </a>
            </div>
          </div>
        </div>
      </section>

      <?php /* Weekly Summary */ ?>
      <section class="mb-4">
        <h2 class="h5 mb-3">This Week</h2>
        <div class="row g-3">
          <div class="col-sm-4">
            <div class="app-card stat-card">
              <div class="stat-label"><i class="bi bi-calendar-check"></i> Workouts</div>
              <div class="stat-value"><?php echo count($week_workouts); ?></div>
              <div class="stat-note">
                <?php
                /* Links to the 3 most recent, more would wrap and make this card taller than the others */
                $week_links = array();
                foreach (array_slice($week_workouts, 0, 3) as $workout) {
                  $week_links[] = '<a href="' . BASE_URL . '/user/workout.php?id=' . $workout['workout_id'] . '">' . date('M j', strtotime($workout['workout_date'])) . '</a>';
                }
                echo $week_links ? implode(' &middot; ', $week_links) : 'None yet this week';
                ?>
              </div>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="app-card stat-card">
              <div class="stat-label"><i class="bi bi-stopwatch"></i> Total Time</div>
              <div class="stat-value"><?php echo floor($week_minutes / 60) . 'h ' . ($week_minutes % 60) . 'm'; ?></div>
              <div class="stat-note">
                <?php
                if ($week_untimed) {
                  echo $week_untimed . ($week_untimed == 1 ? ' workout has' : ' workouts have') . ' no time logged';
                } elseif ($week_workouts) {
                  echo 'Across ' . count($week_workouts) . (count($week_workouts) == 1 ? ' workout' : ' workouts');
                } else {
                  echo 'None yet this week';
                }
                ?>
              </div>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="app-card stat-card">
              <div class="stat-label"><i class="bi bi-fire"></i> Day Streak</div>
              <div class="stat-value"><?php echo $day_streak; ?></div>
              <div class="stat-note">
                <?php
                if ($logged_today) {
                  echo 'Logged today';
                } elseif ($day_streak) {
                  echo 'Log today to keep it going';
                } else {
                  echo 'Log a workout to start one';
                }
                ?>
              </div>
            </div>
          </div>
        </div>
      </section>

      <?php /* Recent Workouts */ ?>
      <section>
        <h2 class="h5 mb-3">Recent Workouts</h2>
        <?php if (empty($workouts)) { ?>
          <div class="app-card empty-state">
            <i class="bi bi-journal-text d-block mb-2"></i>
            <p class="mb-0">No workouts logged yet. Your recent sessions will show up here.</p>
          </div>
        <?php } ?>
        <?php foreach (array_slice($workouts, 0, 5) as $workout) { ?>
          <div class="app-card p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <h3 class="h6 mb-1">
                  <?php echo date('l, F j', strtotime($workout['workout_date'])); ?>
                  <span class="badge text-bg-dark fw-normal ms-1"><?php echo htmlspecialchars($workout['type_name']); ?></span>
                </h3>
                <p class="text-body-secondary small mb-0">
                  <?php
                  $details = show_workout_details($workout['facility_name'], $workout['duration_minutes'], $workout['bodyweight_lbs']);
                  echo ($details ? $details . ' &middot; ' : '') . $workout['set_count'] . ($workout['set_count'] == 1 ? ' set' : ' sets');
                  ?>
                </p>
              </div>
              <a href="<?php echo BASE_URL; ?>/user/workout.php?id=<?php echo $workout['workout_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Open</a>
            </div>
          </div>
        <?php } ?>
      </section>

    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
