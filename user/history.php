<?php
/**
 * Workout History Page - charts of the user's training, then every workout they've logged
 *
 * @author    Jack Mulcunry
 * @copyright 2026
 * @date      2026-10-28
 * @version   1.0
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
  $page_name = "history"; // Set page name for active link in header
  $page_title = "Workout History";

  /* Every workout, newest first */
  $select_workouts = $db_connection->prepare(
    "SELECT w.workout_id, w.workout_date, t.type_name, w.facility_name, w.duration_minutes, w.bodyweight_lbs, w.notes
    FROM Workouts w
    INNER JOIN Workout_Types t ON w.type_id = t.type_id
    WHERE w.user_id = ?
    ORDER BY w.workout_date DESC, w.workout_id DESC");
  $select_workouts->bind_param("i", $user_id);
  $select_workouts->execute();
  $workouts = $select_workouts->get_result()->fetch_all(MYSQLI_ASSOC);
  $select_workouts->close();

  /* Every set this user has logged */
  $select_sets = $db_connection->prepare(
    "SELECT s.workout_id, s.exercise_id, s.set_number, s.weight_lbs, s.reps, s.duration_minutes, s.distance_miles,
      e.exercise_name, e.category, w.workout_date
    FROM Sets s
    INNER JOIN Workouts w ON s.workout_id = w.workout_id
    INNER JOIN Exercises e ON s.exercise_id = e.exercise_id
    WHERE w.user_id = ?
    ORDER BY s.set_id");
  $select_sets->bind_param("i", $user_id);
  $select_sets->execute();
  $sets = $select_sets->get_result()->fetch_all(MYSQLI_ASSOC);
  $select_sets->close();

  /* Sets per muscle group (an exercise counts toward every group it targets) */
  $select_muscles = $db_connection->prepare(
    "SELECT m.group_name, COUNT(*) AS set_count
    FROM Sets s
    INNER JOIN Workouts w ON s.workout_id = w.workout_id
    INNER JOIN Exercise_Muscle_Map em ON s.exercise_id = em.exercise_id
    INNER JOIN Muscle_Groups m ON em.muscle_group_id = m.muscle_group_id
    WHERE w.user_id = ?
    GROUP BY m.muscle_group_id, m.group_name
    ORDER BY set_count DESC, m.group_name");
  $select_muscles->bind_param("i", $user_id);
  $select_muscles->execute();
  $muscle_counts = $select_muscles->get_result()->fetch_all(MYSQLI_ASSOC);
  $select_muscles->close();

  /* Group sets by workout, and keep the heaviest weight of each strength exercise per day */
  $sets_by_workout = array();
  $progress = array(); // exercise_id => name, and date => heaviest weight
  foreach ($sets as $set) {
    $sets_by_workout[$set['workout_id']][] = $set;
    if ($set['category'] == 'Strength' && $set['weight_lbs'] !== null) {
      $exercise_id = $set['exercise_id'];
      $date = $set['workout_date'];
      $progress[$exercise_id]['name'] = $set['exercise_name'];
      $progress[$exercise_id]['weights'][$date] = max($progress[$exercise_id]['weights'][$date] ?? 0, (float) $set['weight_lbs']);
    }
  }
  foreach ($progress as $exercise_id => $exercise) {
    ksort($progress[$exercise_id]['weights']);
  }
  /* Most logged exercise first, so the chart starts on something with a trend */
  uasort($progress, function ($a, $b) {
    return count($b['weights']) - count($a['weights']) ?: strcmp($a['name'], $b['name']);
  });

  /* Workouts per week for the last 12 weeks (weeks start on Monday) */
  $weekly_counts = array();
  for ($i = 11; $i >= 0; $i--) {
    $weekly_counts[date('Y-m-d', strtotime("-$i weeks", strtotime('monday this week')))] = 0;
  }

  /* Totals and bodyweight trend */
  $total_minutes = 0;
  $bodyweights = array(); // date => lbs
  foreach ($workouts as $workout) {
    $week = date('Y-m-d', strtotime('monday this week', strtotime($workout['workout_date'])));
    if (isset($weekly_counts[$week])) {
      $weekly_counts[$week]++;
    }
    $total_minutes += (int) $workout['duration_minutes'];
    if ($workout['bodyweight_lbs'] !== null) {
      $bodyweights[$workout['workout_date']] = (float) $workout['bodyweight_lbs'];
    }
  }
  ksort($bodyweights);

  /* Chart data goes in data-labels and data-values for js/main.js to draw */
  /* Values are sent as text so 151.6 doesn't print as 151.5999... with XAMPP's serialize_precision */
  function chart_data($labels, $values) {
    return 'data-labels="' . htmlspecialchars(json_encode(array_values($labels))) . '"'
      . ' data-values="' . htmlspecialchars(json_encode(array_map('strval', array_values($values)))) . '"';
  }

  /* Show 2026-10-27 as Oct 27 on chart axes */
  function chart_dates($dates) {
    return array_map(function ($date) { return date('M j', strtotime($date)); }, $dates);
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

      <div class="mb-4">
        <h1 class="h3">Workout History</h1>
        <p class="text-body-secondary mb-0">See how your training is trending, then look back through every session you've logged.</p>
      </div>

      <?php if (empty($workouts)) { ?>
        <div class="app-card empty-state">
          <i class="bi bi-journal-text d-block mb-2"></i>
          <p>No workouts logged yet. Your charts and past sessions will show up here.</p>
          <a href="<?php echo BASE_URL; ?>/user/log_workout.php" class="btn btn-primary">
            <i class="bi bi-pencil-square"></i> Start Logging
          </a>
        </div>
      <?php } else { ?>

      <?php /* Totals */ ?>
      <section class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
          <div class="app-card stat-card">
            <div class="stat-label"><i class="bi bi-calendar-check"></i> Workouts</div>
            <div class="stat-value"><?php echo count($workouts); ?></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="app-card stat-card">
            <div class="stat-label"><i class="bi bi-stopwatch"></i> Total Time</div>
            <div class="stat-value"><?php echo floor($total_minutes / 60) . 'h ' . ($total_minutes % 60) . 'm'; ?></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="app-card stat-card">
            <div class="stat-label"><i class="bi bi-list-check"></i> Sets Logged</div>
            <div class="stat-value"><?php echo count($sets); ?></div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="app-card stat-card">
            <div class="stat-label"><i class="bi bi-person"></i> Latest Bodyweight</div>
            <div class="stat-value"><?php echo empty($bodyweights) ? '&mdash;' : end($bodyweights) . ' lbs'; ?></div>
          </div>
        </div>
      </section>

      <?php /* Charts */ ?>
      <section class="row g-4 mb-4">
        <div class="col-lg-6">
          <div class="app-card chart-card">
            <h2 class="h6 mb-3"><i class="bi bi-bar-chart text-accent"></i> Workouts per Week</h2>
            <div class="chart-box">
              <canvas id="weekly_chart" role="img" aria-label="Workouts per week for the last 12 weeks"
                <?php echo chart_data(chart_dates(array_keys($weekly_counts)), $weekly_counts); ?>></canvas>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="app-card chart-card">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
              <h2 class="h6 mb-0"><i class="bi bi-graph-up-arrow text-accent"></i> Heaviest Set</h2>
              <?php if (!empty($progress)) { ?>
                <select class="form-select form-select-sm w-auto" id="progress_exercise" aria-label="Exercise">
                  <?php foreach ($progress as $exercise) { ?>
                    <option <?php echo chart_data(chart_dates(array_keys($exercise['weights'])), $exercise['weights']); ?>><?php echo htmlspecialchars($exercise['name']); ?></option>
                  <?php } ?>
                </select>
              <?php } ?>
            </div>
            <?php if (empty($progress)) { ?>
              <div class="empty-state">
                <i class="bi bi-graph-up d-block mb-2"></i>
                <p class="mb-0">Log a strength set with a weight to track your progress.</p>
              </div>
            <?php } else { ?>
              <div class="chart-box">
                <canvas id="progress_chart" role="img" aria-label="Heaviest set over time for the chosen exercise"></canvas>
              </div>
            <?php } ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="app-card chart-card">
            <h2 class="h6 mb-3"><i class="bi bi-activity text-accent"></i> Bodyweight</h2>
            <?php if (count($bodyweights) < 2) { ?>
              <div class="empty-state">
                <i class="bi bi-person d-block mb-2"></i>
                <p class="mb-0">Add your bodyweight to at least two workouts to see the trend.</p>
              </div>
            <?php } else { ?>
              <div class="chart-box">
                <canvas id="bodyweight_chart" role="img" aria-label="Bodyweight over time"
                  <?php echo chart_data(chart_dates(array_keys($bodyweights)), $bodyweights); ?>></canvas>
              </div>
            <?php } ?>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="app-card chart-card">
            <h2 class="h6 mb-3"><i class="bi bi-bullseye text-accent"></i> Sets per Muscle Group</h2>
            <?php if (empty($muscle_counts)) { ?>
              <div class="empty-state">
                <i class="bi bi-bullseye d-block mb-2"></i>
                <p class="mb-0">Add sets to your workouts to see which muscles you train most.</p>
              </div>
            <?php } else { ?>
              <div class="chart-box">
                <canvas id="muscle_chart" role="img" aria-label="Number of sets for each muscle group"
                  <?php echo chart_data(array_column($muscle_counts, 'group_name'), array_map('intval', array_column($muscle_counts, 'set_count'))); ?>></canvas>
              </div>
            <?php } ?>
          </div>
        </div>
      </section>

      <?php /* Every workout, newest first, grouped by month */ ?>
      <section>
        <h2 class="h5 mb-3">All Workouts</h2>
        <?php
        $current_month = '';
        foreach ($workouts as $workout) {
          $workout_id = $workout['workout_id'];
          $workout_sets = $sets_by_workout[$workout_id] ?? array();
          $exercise_names = array_unique(array_column($workout_sets, 'exercise_name'));
          $month = date('F Y', strtotime($workout['workout_date']));
          if ($month != $current_month) {
            $current_month = $month;
            echo '<h3 class="h6 text-uppercase text-body-secondary mt-4 mb-2">' . $month . '</h3>';
          }
        ?>
          <div class="app-card p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
              <div>
                <h4 class="h6 mb-1">
                  <?php echo date('l, F j', strtotime($workout['workout_date'])); ?>
                  <span class="badge text-bg-dark fw-normal ms-1"><?php echo htmlspecialchars($workout['type_name']); ?></span>
                </h4>
                <p class="text-body-secondary small mb-1">
                  <?php
                  $details = show_workout_details($workout['facility_name'], $workout['duration_minutes'], $workout['bodyweight_lbs']);
                  echo ($details ? $details . ' &middot; ' : '') . count($workout_sets) . (count($workout_sets) == 1 ? ' set' : ' sets');
                  ?>
                </p>
                <?php if ($exercise_names) { ?>
                  <p class="small mb-0"><?php echo htmlspecialchars(implode(', ', $exercise_names)); ?></p>
                <?php } ?>
              </div>
              <div class="d-flex gap-2">
                <?php if ($workout_sets) { ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#sets_<?php echo $workout_id; ?>" aria-expanded="false" aria-controls="sets_<?php echo $workout_id; ?>">
                    <i class="bi bi-list-ul"></i> Sets
                  </button>
                <?php } ?>
                <a href="<?php echo BASE_URL; ?>/user/workout.php?id=<?php echo $workout_id; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Open</a>
                <a href="<?php echo BASE_URL; ?>/user/log_workout.php?template=<?php echo $workout_id; ?>" class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat"></i> Repeat</a>
              </div>
            </div>
            <?php if ($workout['notes']) { ?>
              <p class="small text-body-secondary mb-0 mt-2"><i class="bi bi-sticky"></i> <?php echo nl2br(htmlspecialchars($workout['notes'])); ?></p>
            <?php } ?>
            <?php if ($workout_sets) { ?>
              <div class="collapse" id="sets_<?php echo $workout_id; ?>">
                <div class="table-responsive mt-3"><table class="table table-sm mb-0 align-middle">
                  <thead>
                    <tr><th>Exercise</th><th>Set</th><th>Result</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($workout_sets as $set) { ?>
                      <tr>
                        <td><?php echo htmlspecialchars($set['exercise_name']); ?></td>
                        <td><?php echo $set['set_number']; ?></td>
                        <td><?php echo show_set_result($set); ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table></div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </section>

      <?php } ?>

    </main>
    <?php /* Chart.js draws the charts above - only this page needs it */ ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4" crossorigin="anonymous"></script>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
