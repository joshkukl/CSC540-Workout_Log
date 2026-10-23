<?php
/**
 * Log Workout Page - create a new workout, optionally from a past one
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
  $page_title = "Log Workout";

  /* Values typed before an error, so the user doesn't have to retype them */
  $old_input = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : array();
  unset($_SESSION['old_input']);
  function old_value($field, $default = '') {
    global $old_input;
    return htmlspecialchars(isset($old_input[$field]) ? $old_input[$field] : $default);
  }

  /* Past workouts that can be used as a template, grouped by type */
  $select_past = $db_connection->prepare(
    "SELECT w.workout_id, w.workout_date, w.facility_name, t.type_name, COUNT(s.set_id)
    FROM Workouts w
    INNER JOIN Workout_Types t ON w.type_id = t.type_id
    LEFT JOIN Sets s ON w.workout_id = s.workout_id
    WHERE w.user_id = ?
    GROUP BY w.workout_id
    ORDER BY t.type_name, w.workout_date DESC, w.workout_id DESC");
  $select_past->bind_param("i", $user_id);
  $select_past->execute();
  $select_past->bind_result($past_id, $past_date, $past_facility, $past_type, $past_sets);
  $past_workouts = array(); // type name => list of workouts
  while ($select_past->fetch()) {
    $past_workouts[$past_type][] = array(
      'id' => $past_id,
      'text' => date('M j, Y', strtotime($past_date))
        . ($past_facility ? ' at ' . $past_facility : '') . ' (' . $past_sets . ($past_sets == 1 ? ' set)' : ' sets)'),
    );
  }
  $select_past->close();

  /* This user's types first, then common splits they haven't used yet */
  $select_types = $db_connection->prepare(
    "SELECT type_name FROM Workout_Types WHERE user_id = ? ORDER BY type_name");
  $select_types->bind_param("i", $user_id);
  $select_types->execute();
  $select_types->bind_result($type_name);
  $type_suggestions = array();
  while ($select_types->fetch()) {
    $type_suggestions[] = $type_name;
  }
  $select_types->close();
  foreach (array('Push', 'Pull', 'Legs', 'Upper', 'Lower', 'Full Body', 'Cardio') as $common_type) {
    if (!in_array(strtolower($common_type), array_map('strtolower', $type_suggestions))) {
      $type_suggestions[] = $common_type;
    }
  }

  /* Template picked from the dashboard or a workout page (?template=ID) */
  $selected_template = isset($old_input['template_id']) ? $old_input['template_id'] : ($_GET['template'] ?? '');

?>
<!doctype html>
<html lang="en">
  <head>
  <?php include_once (ROOT_PATH . '/include/head.php'); ?>
  </head>
  <body class="<?php echo $page_name; ?>">

  <?php include_once (ROOT_PATH . '/include/header.php'); ?>
    <main role="main" class="container">

      <div class="app-card form-card">
        <h1 class="h3 mb-1"><i class="bi bi-plus-lg text-accent"></i> Log a Workout</h1>
        <p class="text-body-secondary">Create the session first, then add your exercises and sets.</p>

        <form action="<?php echo SRC_PATH; ?>/create_workout.php" method="post">
          <div class="mb-3">
            <label for="template_id" class="form-label">Start from a past workout <span class="text-body-secondary">(optional)</span></label>
            <select class="form-select" id="template_id" name="template_id">
              <option value="">Blank workout</option>
              <?php foreach ($past_workouts as $type_name => $type_workouts) { ?>
                <optgroup label="<?php echo htmlspecialchars($type_name); ?>">
                  <?php foreach ($type_workouts as $past) { ?>
                    <option value="<?php echo $past['id']; ?>" data-type="<?php echo htmlspecialchars($type_name); ?>"<?php echo $selected_template == $past['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($type_name . ' - ' . $past['text']); ?></option>
                  <?php } ?>
                </optgroup>
              <?php } ?>
            </select>
            <div class="form-text">Copies that workout's exercises and sets so you only change what's different.</div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label for="type_name" class="form-label">Type</label>
              <input type="text" class="form-control" id="type_name" name="type_name" value="<?php echo old_value('type_name'); ?>" maxlength="50" list="type_list" placeholder="e.g. Push, Pull, Legs" autocomplete="off" required>
              <datalist id="type_list">
                <?php foreach ($type_suggestions as $type_name) { ?>
                  <option value="<?php echo htmlspecialchars($type_name); ?>">
                <?php } ?>
              </datalist>
              <div class="form-text">Pick one or type your own.</div>
            </div>
            <div class="col-sm-6">
              <label for="workout_date" class="form-label">Date</label>
              <input type="date" class="form-control" id="workout_date" name="workout_date" value="<?php echo old_value('workout_date', date('Y-m-d')); ?>" max="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-sm-6">
              <label for="bodyweight_lbs" class="form-label">Bodyweight in lbs <span class="text-body-secondary">(optional)</span></label>
              <input type="number" class="form-control" id="bodyweight_lbs" name="bodyweight_lbs" value="<?php echo old_value('bodyweight_lbs'); ?>" min="50" max="1000" step="0.1">
            </div>
            <div class="col-sm-6">
              <label for="duration_minutes" class="form-label">Duration in minutes <span class="text-body-secondary">(optional)</span></label>
              <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" value="<?php echo old_value('duration_minutes'); ?>" min="1" max="1440">
            </div>
          </div>
          <div class="mb-3">
            <label for="facility_name" class="form-label">Facility <span class="text-body-secondary">(optional)</span></label>
            <input type="text" class="form-control" id="facility_name" name="facility_name" value="<?php echo old_value('facility_name'); ?>" maxlength="255" placeholder="e.g. Campus Rec Center">
          </div>
          <div class="mb-3">
            <label for="notes" class="form-label">Notes <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo old_value('notes'); ?></textarea>
          </div>
          <?php include_once (ROOT_PATH . '/php/error_rprt.php'); ?>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Workout</button>
          <a href="<?php echo BASE_URL; ?>/user/" class="btn btn-outline-secondary">Cancel</a>
        </form>
      </div>

    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
