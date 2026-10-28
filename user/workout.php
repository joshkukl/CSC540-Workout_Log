<?php
/**
 * Workout Page - add sets to a workout (workout.php?id=WORKOUT_ID)
 *
 * @author    Sebastian Scrimenti
 * @copyright 2026
 * @date      2026-10-23
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
  $page_name = "log_workout"; // Set page name for active link in header
  $page_title = "Add Sets";

  /* Load the workout, only if it belongs to this user */
  $workout_id = (int) ($_GET['id'] ?? 0);
  $select_workout = $db_connection->prepare(
    "SELECT w.workout_date, t.type_name, w.facility_name, w.duration_minutes, w.bodyweight_lbs, w.notes
    FROM Workouts w
    INNER JOIN Workout_Types t ON w.type_id = t.type_id
    WHERE w.workout_id = ? AND w.user_id = ?");
  $select_workout->bind_param("ii", $workout_id, $user_id);
  $select_workout->execute();
  $select_workout->bind_result($workout_date, $type_name, $facility_name, $duration_minutes, $bodyweight_lbs, $notes);
  $workout_found = $select_workout->fetch();
  $select_workout->close();

  if (!$workout_found) {
    $_SESSION['error'] = "That workout couldn't be found.";
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
  }

  /* Sets already logged for this workout */
  $select_sets = $db_connection->prepare(
    "SELECT s.set_id, s.set_number, s.weight_lbs, s.reps, s.duration_minutes, s.distance_miles,
      e.exercise_name, e.category
    FROM Sets s
    INNER JOIN Exercises e ON s.exercise_id = e.exercise_id
    WHERE s.workout_id = ?
    ORDER BY s.set_id");
  $select_sets->bind_param("i", $workout_id);
  $select_sets->execute();
  $sets = $select_sets->get_result()->fetch_all(MYSQLI_ASSOC);
  $select_sets->close();

  /* Exercises for the add-set form, with equipment so similar ones are easy to tell apart */
  $exercises = $db_connection->query(
    "SELECT e.exercise_id, e.exercise_name, e.category, eq.machine_type
    FROM Exercises e
    LEFT JOIN Equipment eq ON e.equipment_id = eq.equipment_id
    ORDER BY e.category DESC, e.exercise_name")->fetch_all(MYSQLI_ASSOC);

  /* Keep the last exercise picked so adding several sets in a row is quick */
  $last_exercise = $_SESSION['last_exercise'] ?? '';

?>
<!doctype html>
<html lang="en">
  <head>
  <?php include_once (ROOT_PATH . '/include/head.php'); ?>
  </head>
  <body class="<?php echo $page_name; ?>">

  <?php include_once (ROOT_PATH . '/include/header.php'); ?>
    <main role="main" class="container">

      <?php /* Workout Summary */ ?>
      <section class="app-card p-4 mb-4">
        <h1 class="h3 mb-1">
          <?php echo date('l, F j, Y', strtotime($workout_date)); ?>
          <span class="badge text-bg-dark fs-6 fw-normal align-middle"><?php echo htmlspecialchars($type_name); ?></span>
        </h1>
        <p class="text-body-secondary mb-0">
          <?php echo show_workout_details($facility_name, $duration_minutes, $bodyweight_lbs); ?>
        </p>
        <?php if ($notes) { ?>
          <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($notes)); ?></p>
        <?php } ?>
      </section>

      <?php include_once (ROOT_PATH . '/php/error_rprt.php'); ?>

      <div class="row g-4">

        <?php /* Add Set Form */ ?>
        <div class="col-lg-5">
          <div class="app-card p-4">
            <h2 class="h5 mb-3"><i class="bi bi-plus-circle text-accent"></i> Add a Set</h2>
            <form action="<?php echo SRC_PATH; ?>/add_set.php" method="post">
              <input type="hidden" name="workout_id" value="<?php echo $workout_id; ?>">
              <div class="mb-3">
                <label for="exercise_id" class="form-label">Exercise</label>
                <select class="form-select" id="exercise_id" name="exercise_id" required>
                  <option value="">Choose an exercise</option>
                  <?php foreach (array('Strength', 'Cardio') as $category) { ?>
                    <optgroup label="<?php echo $category; ?>">
                      <?php foreach ($exercises as $exercise) { if ($exercise['category'] != $category) { continue; } ?>
                        <option value="<?php echo $exercise['exercise_id']; ?>" data-category="<?php echo $category; ?>"<?php echo $last_exercise == $exercise['exercise_id'] ? ' selected' : ''; ?>>
                          <?php echo htmlspecialchars($exercise['exercise_name']) . ($exercise['machine_type'] ? ' (' . htmlspecialchars($exercise['machine_type']) . ')' : ''); ?>
                        </option>
                      <?php } ?>
                    </optgroup>
                  <?php } ?>
                </select>
              </div>
              <div class="row g-2 mb-3" data-fields="Strength">
                <div class="col">
                  <label for="weight_lbs" class="form-label">Weight (lbs)</label>
                  <input type="number" class="form-control" id="weight_lbs" name="weight_lbs" min="0" max="2000" step="0.5" placeholder="Bodyweight">
                </div>
                <div class="col">
                  <label for="reps" class="form-label">Reps</label>
                  <input type="number" class="form-control" id="reps" name="reps" min="1" max="1000">
                </div>
              </div>
              <div class="row g-2 mb-3" data-fields="Cardio">
                <div class="col">
                  <label for="set_duration" class="form-label">Time (min)</label>
                  <input type="number" class="form-control" id="set_duration" name="duration_minutes" min="0" max="1440" step="0.1">
                </div>
                <div class="col">
                  <label for="distance_miles" class="form-label">Distance (mi)</label>
                  <input type="number" class="form-control" id="distance_miles" name="distance_miles" min="0" max="500" step="0.01">
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Set</button>
            </form>
          </div>
        </div>

        <?php /* Sets Logged So Far */ ?>
        <div class="col-lg-7">
          <h2 class="h5 mb-3">Sets Logged</h2>
          <?php if (empty($sets)) { ?>
            <div class="app-card empty-state">
              <i class="bi bi-journal-plus d-block mb-2"></i>
              <p class="mb-0">No sets yet. Add your first one on the left.</p>
            </div>
          <?php } else { ?>
            <div class="app-card p-3 table-responsive"><table class="table mb-0 align-middle">
              <thead>
                <tr><th>Exercise</th><th>Set</th><th>Result</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($sets as $set) { ?>
                  <tr>
                    <td><?php echo htmlspecialchars($set['exercise_name']); ?></td>
                    <td><?php echo $set['set_number']; ?></td>
                    <td><?php echo show_set_result($set); ?></td>
                    <td class="text-end">
                      <form action="<?php echo SRC_PATH; ?>/delete_set.php" method="post" class="d-inline">
                        <input type="hidden" name="set_id" value="<?php echo $set['set_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete set"><i class="bi bi-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table></div>
          <?php } ?>
          <a href="<?php echo BASE_URL; ?>/user/" class="btn btn-outline-secondary mt-3"><i class="bi bi-check2"></i> Done</a>
        </div>

      </div>

    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
