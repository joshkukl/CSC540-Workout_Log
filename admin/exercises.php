<?php
/**
 * Admin Exercises & Equipment Page - the master lists everyone picks from
 * Built-in exercises have user_id NULL; users' own exercises aren't shown here
 *
 * @author    Joshua Kuklinski <kuklinskij1@southernct.edu>
 * @copyright 2026
 * @date      2026-11-17
 * @version   1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // set to 1 to display errors, 0 to hide them

  /* Quick Paths */
  /* note the 2 after __FILE__, because it's 2 directories deep */
  include_once (realpath(dirname(__FILE__, 2).'/php/session.php'));
  include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));

  /* Check Role */
  include_once (ROOT_SRC_PATH .'/check_admin.php');
  include_once (ROOT_SRC_PATH .'/csrf.php');

  /* Page Name */
  $page_name = "exercises";
  $page_title = "Exercises & Equipment";

  /* Equipment, with how many exercises and logged sets use each (used equipment can't be deleted) */
  $equipment = $db_connection->query(
    "SELECT eq.equipment_id, eq.brand_name, eq.machine_type, COUNT(e.exercise_id) AS exercise_count,
      (SELECT COUNT(*) FROM Sets s WHERE s.equipment_id = eq.equipment_id) AS set_count
    FROM Equipment eq
    LEFT JOIN Exercises e ON eq.equipment_id = e.equipment_id
    WHERE eq.user_id IS NULL
    GROUP BY eq.equipment_id
    ORDER BY eq.machine_type, eq.brand_name")->fetch_all(MYSQLI_ASSOC);

  /* Built-in exercises, active first, with their equipment, muscles and how many workouts used them */
  $exercises = $db_connection->query(
    "SELECT e.exercise_id, e.exercise_name, e.category, e.is_archived, e.equipment_id,
      eq.brand_name, eq.machine_type,
      GROUP_CONCAT(m.group_name ORDER BY m.muscle_group_id SEPARATOR ', ') AS muscles,
      GROUP_CONCAT(m.muscle_group_id) AS muscle_ids,
      (SELECT COUNT(DISTINCT s.workout_id) FROM Sets s WHERE s.exercise_id = e.exercise_id) AS workout_count
    FROM Exercises e
    LEFT JOIN Equipment eq ON e.equipment_id = eq.equipment_id
    LEFT JOIN Exercise_Muscle_Map em ON e.exercise_id = em.exercise_id
    LEFT JOIN Muscle_Groups m ON em.muscle_group_id = m.muscle_group_id
    WHERE e.user_id IS NULL
    GROUP BY e.exercise_id
    ORDER BY e.is_archived, e.category DESC, e.exercise_name")->fetch_all(MYSQLI_ASSOC);

  /* Muscle groups for the exercise form checkboxes */
  $muscle_groups = $db_connection->query(
    "SELECT muscle_group_id, group_name FROM Muscle_Groups ORDER BY muscle_group_id")->fetch_all(MYSQLI_ASSOC);

  /* Row being edited (?edit_equipment=ID or ?edit_exercise=ID), or null when adding */
  $edit_equipment = null;
  foreach ($equipment as $row) {
    if ($row['equipment_id'] == ($_GET['edit_equipment'] ?? '')) { $edit_equipment = $row; }
  }
  $edit_exercise = null;
  foreach ($exercises as $row) {
    if ($row['exercise_id'] == ($_GET['edit_exercise'] ?? '')) { $edit_exercise = $row; }
  }

  /* Values typed before an error, so the admin doesn't have to retype them */
  $old_input = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : array();
  unset($_SESSION['old_input']);
  $old_equipment = ($old_input['form'] ?? '') == 'equipment' ? $old_input : array();
  $old_exercise = ($old_input['form'] ?? '') == 'exercise' ? $old_input : array();

  $form_brand = $old_equipment['brand_name'] ?? ($edit_equipment['brand_name'] ?? '');
  $form_machine = $old_equipment['machine_type'] ?? ($edit_equipment['machine_type'] ?? '');
  $form_exercise_name = $old_exercise['exercise_name'] ?? ($edit_exercise['exercise_name'] ?? '');
  $form_category = $old_exercise['category'] ?? ($edit_exercise['category'] ?? 'Strength');
  $form_exercise_equipment = $old_exercise['exercise_equipment_id'] ?? ($edit_exercise['equipment_id'] ?? '');
  $form_muscles = $old_exercise['muscles'] ?? explode(',', $edit_exercise['muscle_ids'] ?? '');

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
        <h1 class="h3">Exercises &amp; Equipment</h1>
        <p class="text-body-secondary mb-0">The built-in lists every user picks from when logging sets. Keep equipment generic (Barbell, Cable Machine); users add the specific machines at their gym, like "Hoist Lat Pulldown", which only they see.</p>
      </div>

      <?php include_once (ROOT_PATH . '/php/error_rprt.php'); ?>

      <?php /* Equipment */ ?>
      <h2 class="h5 mb-3">Equipment</h2>
      <div class="row g-4 mb-5">
        <div class="col-lg-4">
          <div class="app-card p-4">
            <h3 class="h6 mb-3">
              <?php echo $edit_equipment ? '<i class="bi bi-pencil text-accent"></i> Edit Equipment' : '<i class="bi bi-plus-circle text-accent"></i> Add Equipment'; ?>
            </h3>
            <form action="<?php echo SRC_PATH; ?>/admin_equipment.php" method="post">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="save">
              <input type="hidden" name="equipment_id" value="<?php echo $edit_equipment ? $edit_equipment['equipment_id'] : ''; ?>">
              <div class="mb-3">
                <label for="machine_type" class="form-label">Type</label>
                <input type="text" class="form-control" id="machine_type" name="machine_type" value="<?php echo htmlspecialchars($form_machine); ?>" maxlength="255" placeholder="e.g. Barbell, Rowing Machine" required>
                <div class="form-text">Shown next to the exercise name when users log sets.</div>
              </div>
              <div class="mb-3">
                <label for="brand_name" class="form-label">Brand <span class="text-body-secondary">(optional)</span></label>
                <input type="text" class="form-control" id="brand_name" name="brand_name" value="<?php echo htmlspecialchars($form_brand); ?>" maxlength="255" placeholder="e.g. Rogue">
              </div>
              <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo $edit_equipment ? 'Save Changes' : 'Add Equipment'; ?></button>
              <?php if ($edit_equipment) { ?>
                <a href="<?php echo BASE_URL; ?>/admin/exercises.php" class="btn btn-outline-secondary">Cancel</a>
              <?php } ?>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="app-card p-3 table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr><th>Type</th><th>Brand</th><th>Used by</th><th class="text-end">Action</th></tr>
              </thead>
              <tbody>
                <?php foreach ($equipment as $row) { ?>
                  <tr<?php echo $edit_equipment && $edit_equipment['equipment_id'] == $row['equipment_id'] ? ' class="table-active"' : ''; ?>>
                    <td><?php echo htmlspecialchars($row['machine_type']); ?></td>
                    <td><?php echo $row['brand_name'] !== '' ? htmlspecialchars($row['brand_name']) : '<span class="text-body-secondary">&mdash;</span>'; ?></td>
                    <td>
                      <?php echo $row['exercise_count'] . ($row['exercise_count'] == 1 ? ' exercise' : ' exercises'); ?>
                      <span class="small text-body-secondary d-block"><?php echo $row['set_count'] . ($row['set_count'] == 1 ? ' logged set' : ' logged sets'); ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                      <a href="<?php echo BASE_URL; ?>/admin/exercises.php?edit_equipment=<?php echo $row['equipment_id']; ?>" class="btn btn-sm btn-outline-secondary" aria-label="Edit equipment"><i class="bi bi-pencil"></i></a>
                      <?php if ($row['exercise_count'] == 0 && $row['set_count'] == 0) { /* Used equipment can't be deleted */ ?>
                        <form action="<?php echo SRC_PATH; ?>/admin_equipment.php" method="post" class="d-inline"
                              onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($row['machine_type'])); ?>?');">
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="equipment_id" value="<?php echo $row['equipment_id']; ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete equipment"><i class="bi bi-trash"></i></button>
                        </form>
                      <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
                <?php if (empty($equipment)) { ?>
                  <tr><td colspan="4" class="text-body-secondary">No equipment yet.</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <p class="small text-body-secondary mt-2 mb-0">Equipment can only be deleted while no exercise or logged set uses it.</p>
        </div>
      </div>

      <?php /* Built-in Exercises */ ?>
      <h2 class="h5 mb-3">Built-in Exercises</h2>
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="app-card p-4">
            <h3 class="h6 mb-3">
              <?php echo $edit_exercise ? '<i class="bi bi-pencil text-accent"></i> Edit Exercise' : '<i class="bi bi-plus-circle text-accent"></i> Add Exercise'; ?>
            </h3>
            <form action="<?php echo SRC_PATH; ?>/admin_exercise.php" method="post">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="save">
              <input type="hidden" name="exercise_id" value="<?php echo $edit_exercise ? $edit_exercise['exercise_id'] : ''; ?>">
              <div class="mb-3">
                <label for="exercise_name" class="form-label">Name</label>
                <input type="text" class="form-control" id="exercise_name" name="exercise_name" value="<?php echo htmlspecialchars($form_exercise_name); ?>" maxlength="100" placeholder="e.g. Seated Row" required>
              </div>
              <div class="row g-2 mb-3">
                <div class="col-5">
                  <label for="category" class="form-label">Kind</label>
                  <select class="form-select" id="category" name="category"<?php echo $edit_exercise && $edit_exercise['workout_count'] > 0 ? ' disabled' : ''; ?>>
                    <?php foreach (array('Strength', 'Cardio') as $category) { ?>
                      <option value="<?php echo $category; ?>"<?php echo $form_category == $category ? ' selected' : ''; ?>><?php echo $category; ?></option>
                    <?php } ?>
                  </select>
                  <?php if ($edit_exercise && $edit_exercise['workout_count'] > 0) { /* disabled selects aren't submitted */ ?>
                    <input type="hidden" name="category" value="<?php echo $edit_exercise['category']; ?>">
                  <?php } ?>
                </div>
                <div class="col-7">
                  <label for="exercise_equipment_id" class="form-label">Equipment</label>
                  <select class="form-select" id="exercise_equipment_id" name="exercise_equipment_id">
                    <option value="">None needed</option>
                    <?php foreach ($equipment as $row) { ?>
                      <option value="<?php echo $row['equipment_id']; ?>"<?php echo $form_exercise_equipment == $row['equipment_id'] ? ' selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['machine_type'] . ($row['brand_name'] !== '' ? ' (' . $row['brand_name'] . ')' : '')); ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <?php if ($edit_exercise && $edit_exercise['workout_count'] > 0) { ?>
                <p class="form-text">Kind can't change once it's been logged, since past sets were saved as <?php echo $edit_exercise['category'] == 'Strength' ? 'weight and reps' : 'time and distance'; ?>.</p>
              <?php } ?>
              <fieldset class="mb-3">
                <legend class="form-label fs-6">Muscles worked <span class="text-body-secondary">(used by the history charts)</span></legend>
                <?php foreach ($muscle_groups as $muscle) { ?>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="muscles[]" id="muscle_<?php echo $muscle['muscle_group_id']; ?>" value="<?php echo $muscle['muscle_group_id']; ?>"<?php echo in_array($muscle['muscle_group_id'], $form_muscles) ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="muscle_<?php echo $muscle['muscle_group_id']; ?>"><?php echo htmlspecialchars($muscle['group_name']); ?></label>
                  </div>
                <?php } ?>
              </fieldset>
              <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo $edit_exercise ? 'Save Changes' : 'Add Exercise'; ?></button>
              <?php if ($edit_exercise) { ?>
                <a href="<?php echo BASE_URL; ?>/admin/exercises.php" class="btn btn-outline-secondary">Cancel</a>
              <?php } ?>
            </form>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="app-card p-3 table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr><th>Exercise</th><th>Equipment</th><th>Muscles</th><th>Used in</th><th class="text-end">Action</th></tr>
              </thead>
              <tbody>
                <?php foreach ($exercises as $row) { ?>
                  <tr class="<?php echo $row['is_archived'] ? 'text-body-secondary' : ''; ?><?php echo $edit_exercise && $edit_exercise['exercise_id'] == $row['exercise_id'] ? ' table-active' : ''; ?>">
                    <td>
                      <?php echo htmlspecialchars($row['exercise_name']); ?>
                      <?php if ($row['is_archived']) { ?><span class="badge text-bg-secondary fw-normal ms-1">Archived</span><?php } ?>
                      <span class="small text-body-secondary d-block"><?php echo $row['category']; ?></span>
                    </td>
                    <td><?php echo $row['machine_type'] !== null ? htmlspecialchars($row['machine_type']) : '<span class="text-body-secondary">&mdash;</span>'; ?></td>
                    <td class="small"><?php echo $row['muscles'] ? htmlspecialchars($row['muscles']) : '<span class="text-body-secondary">&mdash;</span>'; ?></td>
                    <td class="text-nowrap"><?php echo $row['workout_count'] . ($row['workout_count'] == 1 ? ' workout' : ' workouts'); ?></td>
                    <td class="text-end text-nowrap">
                      <a href="<?php echo BASE_URL; ?>/admin/exercises.php?edit_exercise=<?php echo $row['exercise_id']; ?>" class="btn btn-sm btn-outline-secondary" aria-label="Edit exercise"><i class="bi bi-pencil"></i></a>
                      <?php $js_name = htmlspecialchars(addslashes($row['exercise_name'])); ?>
                      <?php if ($row['is_archived']) { ?>
                        <form action="<?php echo SRC_PATH; ?>/admin_exercise.php" method="post" class="d-inline">
                          <input type="hidden" name="action" value="restore">
                          <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                      <?php } elseif ($row['workout_count'] > 0) { /* Used: archive, so past workouts keep their sets */ ?>
                        <form action="<?php echo SRC_PATH; ?>/admin_exercise.php" method="post" class="d-inline"
                              onsubmit="return confirm('Archive <?php echo $js_name; ?>? Users won\'t see it when logging, but past workouts keep their sets.');">
                          <input type="hidden" name="action" value="remove">
                          <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-archive"></i> Archive</button>
                      <?php } else { ?>
                        <form action="<?php echo SRC_PATH; ?>/admin_exercise.php" method="post" class="d-inline"
                              onsubmit="return confirm('Delete <?php echo $js_name; ?>? Nobody has logged it yet.');">
                          <input type="hidden" name="action" value="remove">
                          <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete exercise"><i class="bi bi-trash"></i></button>
                      <?php } ?>
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="exercise_id" value="<?php echo $row['exercise_id']; ?>">
                        </form>
                    </td>
                  </tr>
                <?php } ?>
                <?php if (empty($exercises)) { ?>
                  <tr><td colspan="5" class="text-body-secondary">No built-in exercises yet.</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <p class="small text-body-secondary mt-2 mb-0">Exercises nobody has logged are deleted. Logged ones are archived instead: hidden from the list users pick from, while past workouts keep their sets.</p>
        </div>
      </div>

    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
<?php $db_connection->close(); ?>
