<?php
//======================================================================
// Update Set - change the numbers (and equipment) of a set already logged
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));
include_once (realpath(dirname(__FILE__).'/csrf.php'));
include_once (realpath(dirname(__FILE__).'/set_values.php'));

/* Only standard users log workouts */
if ($user_role != 2) {
    $_SESSION['message'] = "Access Denied";
    header("location: " . SRC_PATH . "/logout.php");
    exit();
}

$workout_id = (int) ($_POST['workout_id'] ?? 0);
$set_id = (int) ($_POST['set_id'] ?? 0);
$workout_url = BASE_URL . "/user/workout.php?id=" . $workout_id;

/* Send the user back to the set's edit form with an error */
function form_error($message) {
    global $workout_url, $set_id;
    $_SESSION['error'] = $message;
    header("location: " . $workout_url . "&edit_set=" . $set_id . "#set-" . $set_id);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

csrf_check($workout_url);

// The set has to be in this workout, and the workout has to belong to this user
$select_set = $db_connection->prepare(
    "SELECT e.category, s.equipment_id FROM Sets s
    INNER JOIN Workouts w ON s.workout_id = w.workout_id
    INNER JOIN Exercises e ON s.exercise_id = e.exercise_id
    WHERE s.set_id = ? AND s.workout_id = ? AND w.user_id = ?");
$select_set->bind_param("iii", $set_id, $workout_id, $user_id);
$select_set->execute();
$select_set->bind_result($category, $current_equipment);
$set_found = $select_set->fetch();
$select_set->close();

if (!$set_found) {
    $_SESSION['error'] = "That set couldn't be found.";
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

// Same rules as adding a set; the set keeps its equipment even if the user has since deleted it
$set_equipment = set_equipment_from_post($db_connection, $user_id, $current_equipment);
$values = set_values_from_post($category);

$update_set = $db_connection->prepare(
    "UPDATE Sets SET equipment_id = ?, weight_lbs = ?, reps = ?, duration_minutes = ?, distance_miles = ?
    WHERE set_id = ?");
$update_set->bind_param("ididdi", $set_equipment, $values['weight_lbs'], $values['reps'],
    $values['duration_minutes'], $values['distance_miles'], $set_id);
if (!$update_set->execute()) {
    form_error("Something went wrong saving that set. Please try again.");
}
$update_set->close();
$db_connection->close();

$_SESSION['success'] = "Set updated.";
header("location: " . $workout_url . "#set-" . $set_id);
exit();

?>
