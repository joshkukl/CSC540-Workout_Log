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
      s.exercise_id, e.exercise_name, e.category
    FROM Sets s
    INNER JOIN Exercises e ON s.exercise_id = e.exercise_id
    WHERE s.workout_id = ?
    ORDER BY s.set_id");
  $select_sets->bind_param("i", $workout_id);
  $select_sets->execute();
  $sets = $select_sets->get_result()->fetch_all(MYSQLI_ASSOC);
  $select_sets->close();

  /* Exercises in this workout, for the remove-exercise buttons (exercise_id => name) */
  $workout_exercises = array();
  foreach ($sets as $set) {
    $workout_exercises[$set['exercise_id']] = $set['exercise_name'];
  }

  /* Exercises for the add-set form: built-in ones (no owner) plus the ones this user added
     and hasn't deleted, with equipment so similar ones are easy to tell apart */
  $select_exercises = $db_connection->prepare(
    "SELECT e.exercise_id, e.exercise_name, e.category, eq.machine_type
    FROM Exercises e
    LEFT JOIN Equipment eq ON e.equipment_id = eq.equipment_id
    WHERE (e.user_id IS NULL OR e.user_id = ?) AND e.is_archived = 0
    ORDER BY e.category DESC, e.exercise_name");
  $select_exercises->bind_param("i", $user_id);
  $select_exercises->execute();
  $exercises = $select_exercises->get_result()->fetch_all(MYSQLI_ASSOC);
  $select_exercises->close();

  /* The user's own exercises with their muscle groups, listed under the add-exercise form */
  $select_mine = $db_connection->prepare(
    "SELECT e.exercise_id, e.exercise_name, e.category,
      GROUP_CONCAT(m.group_name ORDER BY m.muscle_group_id SEPARATOR ', ') AS muscles
    FROM Exercises e
    LEFT JOIN Exercise_Muscle_Map em ON e.exercise_id = em.exercise_id
    LEFT JOIN Muscle_Groups m ON em.muscle_group_id = m.muscle_group_id
    WHERE e.user_id = ? AND e.is_archived = 0
    GROUP BY e.exercise_id
    ORDER BY e.exercise_name");
  $select_mine->bind_param("i", $user_id);
  $select_mine->execute();
  $my_exercises = $select_mine->get_result()->fetch_all(MYSQLI_ASSOC);
  $select_mine->close();

  /* Muscle groups for the add-exercise checkboxes */
  $muscle_groups = $db_connection->query(
    "SELECT muscle_group_id, group_name FROM Muscle_Groups ORDER BY muscle_group_id")->fetch_all(MYSQLI_ASSOC);

  /* CSRF token for this page's forms */
  include_once (ROOT_SRC_PATH . '/csrf.php');

  /* Keep the last exercise picked so adding several sets in a row is quick */
  $last_exercise = $_SESSION['last_exercise'] ?? '';

  /* Show 95.00 as 95 and 3.10 as 3.1 */
  function show_number($value) {
    return $value === null ? '' : rtrim(rtrim($value, '0'), '.');
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

      <?php /* Workout Summary */ ?>
      <section class="app-card p-4 mb-4">
        <h1 class="h3 mb-1">
          <?php echo date('l, F j, Y', strtotime($workout_date)); ?>
          <span class="badge text-bg-dark fs-6 fw-normal align-middle"><?php echo htmlspecialchars($type_name); ?></span>
        </h1>
        <p class="text-body-secondary mb-0">
          <?php
          $details = array();
          if ($facility_name) { $details[] = htmlspecialchars($facility_name); }
          if ($duration_minutes) { $details[] = $duration_minutes . ' min'; }
          if ($bodyweight_lbs) { $details[] = 'Bodyweight ' . show_number($bodyweight_lbs) . ' lbs'; }
          echo implode(' &middot; ', $details);
          ?>
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
              <?php echo csrf_field(); ?>
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

          <?php /* My Exercises - add your own, delete ones you added */ ?>
          <div class="app-card p-4 mt-4">
            <h2 class="h5 mb-3"><i class="bi bi-bookmark-plus text-accent"></i> My Exercises</h2>
            <form action="<?php echo SRC_PATH; ?>/add_exercise.php" method="post">
              <input type="hidden" name="workout_id" value="<?php echo $workout_id; ?>">
              <?php echo csrf_field(); ?>
              <div class="row g-2 mb-3">
                <div class="col-7">
                  <label for="new_exercise_name" class="form-label">New exercise</label>
                  <input type="text" class="form-control" id="new_exercise_name" name="exercise_name" maxlength="100" placeholder="e.g. Cable Fly" required>
                </div>
                <div class="col-5">
                  <label for="new_exercise_category" class="form-label">Kind</label>
                  <select class="form-select" id="new_exercise_category" name="category">
                    <option value="Strength">Strength</option>
                    <option value="Cardio">Cardio</option>
                  </select>
                </div>
              </div>
              <fieldset class="mb-3">
                <legend class="form-label fs-6">Muscles worked <span class="text-body-secondary">(optional, used by the history charts)</span></legend>
                <?php foreach ($muscle_groups as $muscle) { ?>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="muscles[]" id="muscle_<?php echo $muscle['muscle_group_id']; ?>" value="<?php echo $muscle['muscle_group_id']; ?>">
                    <label class="form-check-label" for="muscle_<?php echo $muscle['muscle_group_id']; ?>"><?php echo htmlspecialchars($muscle['group_name']); ?></label>
                  </div>
                <?php } ?>
              </fieldset>
              <button type="submit" class="btn btn-outline-secondary w-100"><i class="bi bi-plus-lg"></i> Add Exercise</button>
            </form>
            <?php if (empty($my_exercises)) { ?>
              <p class="text-body-secondary small mt-3 mb-0">Exercises you add show up in the exercise list above. Only you can see them.</p>
            <?php } else { ?>
              <ul class="list-group mt-3">
                <?php foreach ($my_exercises as $exercise) { ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                      <?php echo htmlspecialchars($exercise['exercise_name']); ?>
                      <span class="text-body-secondary small d-block"><?php echo $exercise['category'] . ($exercise['muscles'] ? ' &middot; ' . htmlspecialchars($exercise['muscles']) : ''); ?></span>
                    </span>
                    <form action="<?php echo SRC_PATH; ?>/delete_exercise.php" method="post" class="d-inline"
                          onsubmit="return confirm('Remove <?php echo htmlspecialchars(addslashes($exercise['exercise_name'])); ?> from your exercises? Past workouts keep their sets.');">
                      <input type="hidden" name="workout_id" value="<?php echo $workout_id; ?>">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="exercise_id" value="<?php echo $exercise['exercise_id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete exercise"><i class="bi bi-trash"></i></button>
                    </form>
                  </li>
                <?php } ?>
              </ul>
            <?php } ?>
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
                    <td>
                      <?php
                      if ($set['category'] == 'Strength') {
                        echo ($set['weight_lbs'] === null ? 'Bodyweight' : show_number($set['weight_lbs']) . ' lbs') . ' &times; ' . $set['reps'];
                      } else {
                        $parts = array();
                        if ($set['duration_minutes'] !== null) { $parts[] = show_number($set['duration_minutes']) . ' min'; }
                        if ($set['distance_miles'] !== null) { $parts[] = show_number($set['distance_miles']) . ' mi'; }
                        echo implode(' &middot; ', $parts);
                      }
                      ?>
                    </td>
                    <td class="text-end">
                      <form action="<?php echo SRC_PATH; ?>/delete_set.php" method="post" class="d-inline">
                        <input type="hidden" name="set_id" value="<?php echo $set['set_id']; ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete set"><i class="bi bi-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table></div>

            <?php /* Remove a whole exercise (all its sets) from this workout */ ?>
            <div class="mt-3">
              <span class="text-body-secondary small me-1">Remove from this workout:</span>
              <?php foreach ($workout_exercises as $exercise_id => $exercise_name) { ?>
                <form action="<?php echo SRC_PATH; ?>/remove_exercise.php" method="post" class="d-inline"
                      onsubmit="return confirm('Remove <?php echo htmlspecialchars(addslashes($exercise_name)); ?> and all its sets from this workout?');">
                  <input type="hidden" name="workout_id" value="<?php echo $workout_id; ?>">
                  <input type="hidden" name="exercise_id" value="<?php echo $exercise_id; ?>">
                  <?php echo csrf_field(); ?>
                  <button type="submit" class="btn btn-sm btn-outline-danger mb-1"><i class="bi bi-x-lg"></i> <?php echo htmlspecialchars($exercise_name); ?></button>
                </form>
              <?php } ?>
            </div>
          <?php } ?>
          <div class="d-flex justify-content-between align-items-start mt-3">
            <a href="<?php echo BASE_URL; ?>/user/" class="btn btn-outline-secondary"><i class="bi bi-check2"></i> Done</a>
            <form action="<?php echo SRC_PATH; ?>/delete_workout.php" method="post"
                  onsubmit="return confirm('Delete this whole workout and all its sets? This can\'t be undone.');">
              <input type="hidden" name="workout_id" value="<?php echo $workout_id; ?>">
              <?php echo csrf_field(); ?>
              <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete Workout</button>
            </form>
          </div>
        </div>

      </div>

    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
