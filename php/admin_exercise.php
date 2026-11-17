<?php
//======================================================================
// Admin Exercise - manage the built-in exercises (user_id NULL)
//   action=save    (exercise_id empty to add, set to edit)
//   action=remove  (never logged: deleted; logged: archived, so past
//                   workouts keep their sets, like delete_exercise.php)
//   action=restore (un-archive)
// Users' own exercises are theirs to manage on the workout page
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));
include_once (ROOT_SRC_PATH . '/csrf.php');

$exercises_page = BASE_URL . "/admin/exercises.php";

/* Only admins */
if ($user_role != 1) {
  $_SESSION['message'] = "Access Denied";
  header("location: " . SRC_PATH . "/logout.php");
  exit();
}
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  header("location: " . $exercises_page);
  exit();
}
csrf_check($exercises_page);

$exercise_id = (int) ($_POST['exercise_id'] ?? 0);
$action = $_POST['action'] ?? '';

/* Send the admin back to the form with an error, keeping what they typed */
function form_error($message) {
  global $exercises_page, $exercise_id;
  $_SESSION['error'] = $message;
  $_SESSION['old_input'] = array(
    'form' => 'exercise',
    'exercise_name' => $_POST['exercise_name'] ?? '',
    'category' => $_POST['category'] ?? '',
    'exercise_equipment_id' => $_POST['exercise_equipment_id'] ?? '',
    'muscles' => (array) ($_POST['muscles'] ?? array()),
  );
  header("location: " . $exercises_page . ($exercise_id ? "?edit_exercise=" . $exercise_id : ""));
  exit();
}

/* Exercise being changed has to be a built-in one */
if ($exercise_id) {
  $select_exercise = $db_connection->prepare(
    "SELECT exercise_name, category, is_archived,
      (SELECT COUNT(DISTINCT workout_id) FROM Sets WHERE exercise_id = ?)
    FROM Exercises WHERE exercise_id = ? AND user_id IS NULL");
  $select_exercise->bind_param("ii", $exercise_id, $exercise_id);
  $select_exercise->execute();
  $select_exercise->bind_result($current_name, $current_category, $is_archived, $workouts_using);
  $exercise_found = $select_exercise->fetch();
  $select_exercise->close();
  if (!$exercise_found) {
    $_SESSION['error'] = "That exercise couldn't be found.";
    header("location: " . $exercises_page);
    exit();
  }
}

if ($action == 'remove' || $action == 'restore') {
  if (!$exercise_id) {
    form_error("Please choose an exercise.");
  }
  if ($action == 'restore') {
    $restore = $db_connection->prepare("UPDATE Exercises SET is_archived = 0 WHERE exercise_id = ?");
    $restore->bind_param("i", $exercise_id);
    $restore->execute();
    $restore->close();
    $_SESSION['success'] = htmlspecialchars($current_name) . " restored. Users can pick it again.";
  } elseif ($workouts_using > 0) {
    $archive = $db_connection->prepare("UPDATE Exercises SET is_archived = 1 WHERE exercise_id = ?");
    $archive->bind_param("i", $exercise_id);
    $archive->execute();
    $archive->close();
    $_SESSION['success'] = htmlspecialchars($current_name) . " archived. "
      . ($workouts_using == 1 ? "The 1 workout" : "The " . $workouts_using . " workouts") . " that used it keep their sets.";
  } else {
    // Its muscle-group links go too (Exercise_Muscle_Map has ON DELETE CASCADE)
    $delete_exercise = $db_connection->prepare("DELETE FROM Exercises WHERE exercise_id = ?");
    $delete_exercise->bind_param("i", $exercise_id);
    $delete_exercise->execute();
    $delete_exercise->close();
    $_SESSION['success'] = htmlspecialchars($current_name) . " deleted.";
  }
  $db_connection->close();
  header("location: " . $exercises_page);
  exit();
}

if ($action != 'save') {
  form_error("Unknown action.");
}

// Name: trimmed, inner spaces collapsed, 1-100 characters (same rules as add_exercise.php)
$exercise_name = trim(preg_replace('/\s+/', ' ', $_POST['exercise_name'] ?? ''));
$category = $_POST['category'] ?? '';
if ($exercise_name === '' || mb_strlen($exercise_name) > 100) {
  form_error("Please enter an exercise name (up to 100 characters).");
}
if (!in_array($category, array('Strength', 'Cardio'), true)) {
  form_error("Please choose Strength or Cardio.");
}
// Past sets were saved as weight/reps or time/distance, so the kind is fixed once logged
if ($exercise_id && $workouts_using > 0 && $category != $current_category) {
  form_error(htmlspecialchars($current_name) . " has been logged as " . $current_category . ", so its kind can't change.");
}

// Equipment is optional, but has to exist when chosen
$equipment_id = ($_POST['exercise_equipment_id'] ?? '') === '' ? null : (int) $_POST['exercise_equipment_id'];
if ($equipment_id !== null) {
  $check_equipment = $db_connection->prepare("SELECT equipment_id FROM Equipment WHERE equipment_id = ? AND user_id IS NULL");
  $check_equipment->bind_param("i", $equipment_id);
  $check_equipment->execute();
  $check_equipment->store_result();
  $equipment_found = $check_equipment->num_rows > 0;
  $check_equipment->close();
  if (!$equipment_found) {
    form_error("Please choose equipment from the list.");
  }
}

// Muscle groups ticked: keep only ids that really exist
$valid_muscles = array_column(
  $db_connection->query("SELECT muscle_group_id FROM Muscle_Groups")->fetch_all(MYSQLI_ASSOC),
  'muscle_group_id');
$muscles = array_intersect(array_map('intval', (array) ($_POST['muscles'] ?? array())), $valid_muscles);

// Another built-in with the same name (case doesn't matter), archived ones included
$select_same = $db_connection->prepare(
  "SELECT is_archived FROM Exercises
  WHERE LOWER(exercise_name) = LOWER(?) AND user_id IS NULL AND exercise_id != ?");
$select_same->bind_param("si", $exercise_name, $exercise_id);
$select_same->execute();
$select_same->bind_result($same_archived);
$same_found = $select_same->fetch();
$select_same->close();
if ($same_found) {
  form_error(htmlspecialchars($exercise_name) . ($same_archived
    ? " is archived. Restore it from the list instead."
    : " is already a built-in exercise."));
}

if ($exercise_id) {
  $update_exercise = $db_connection->prepare(
    "UPDATE Exercises SET exercise_name = ?, category = ?, equipment_id = ? WHERE exercise_id = ?");
  $update_exercise->bind_param("ssii", $exercise_name, $category, $equipment_id, $exercise_id);
  $update_exercise->execute();
  $update_exercise->close();
  $_SESSION['success'] = htmlspecialchars($exercise_name) . " saved.";
} else {
  $insert_exercise = $db_connection->prepare(
    "INSERT INTO Exercises (user_id, equipment_id, exercise_name, category) VALUES (NULL, ?, ?, ?)");
  $insert_exercise->bind_param("iss", $equipment_id, $exercise_name, $category);
  $insert_exercise->execute();
  $exercise_id = $insert_exercise->insert_id;
  $insert_exercise->close();
  $_SESSION['success'] = htmlspecialchars($exercise_name) . " added. Every user can pick it now.";
}

// Replace the exercise's muscle groups with the ticked ones
$clear_map = $db_connection->prepare("DELETE FROM Exercise_Muscle_Map WHERE exercise_id = ?");
$clear_map->bind_param("i", $exercise_id);
$clear_map->execute();
$clear_map->close();
$insert_map = $db_connection->prepare(
  "INSERT INTO Exercise_Muscle_Map (exercise_id, muscle_group_id) VALUES (?, ?)");
$insert_map->bind_param("ii", $exercise_id, $muscle_group_id);
foreach ($muscles as $muscle_group_id) {
  $insert_map->execute();
}
$insert_map->close();
$db_connection->close();

header("location: " . $exercises_page);
exit();

?>
