<?php
//======================================================================
// Add Set - one set of an exercise in a workout
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
$workout_url = BASE_URL . "/user/workout.php?id=" . $workout_id;

/* Send the user back to the workout with an error */
function form_error($message) {
    global $workout_url;
    $_SESSION['error'] = $message;
    header("location: " . $workout_url);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

csrf_check($workout_url);

// The workout has to belong to this user
$check_workout = $db_connection->prepare(
    "SELECT workout_id FROM Workouts WHERE workout_id = ? AND user_id = ?");
$check_workout->bind_param("ii", $workout_id, $user_id);
$check_workout->execute();
$check_workout->store_result();
$workout_found = $check_workout->num_rows > 0;
$check_workout->close();
if (!$workout_found) {
    $_SESSION['error'] = "That workout couldn't be found.";
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

// The exercise decides which fields are used (built-in, or one this user added and hasn't deleted)
$exercise_id = (int) ($_POST['exercise_id'] ?? 0);
$select_exercise = $db_connection->prepare(
    "SELECT category FROM Exercises
    WHERE exercise_id = ? AND (user_id IS NULL OR user_id = ?) AND is_archived = 0");
$select_exercise->bind_param("ii", $exercise_id, $user_id);
$select_exercise->execute();
$select_exercise->bind_result($category);
$exercise_found = $select_exercise->fetch();
$select_exercise->close();
if (!$exercise_found) {
    form_error("Please choose an exercise.");
}
$_SESSION['last_exercise'] = $exercise_id;

// Equipment is optional (the form fills in the exercise's usual one)
$set_equipment = set_equipment_from_post($db_connection, $user_id);
// Keep the machine picked for the next set of the same exercise
$_SESSION['last_set'] = array('exercise_id' => $exercise_id, 'equipment_id' => $set_equipment ?? '');

// Strength sets use weight/reps, cardio sets use duration/distance
$values = set_values_from_post($category);
$weight_lbs = $values['weight_lbs'];
$reps = $values['reps'];
$set_duration = $values['duration_minutes'];
$distance_miles = $values['distance_miles'];

// Next set number for this exercise in this workout
$select_number = $db_connection->prepare(
    "SELECT COALESCE(MAX(set_number), 0) + 1 FROM Sets WHERE workout_id = ? AND exercise_id = ?");
$select_number->bind_param("ii", $workout_id, $exercise_id);
$select_number->execute();
$select_number->bind_result($set_number);
$select_number->fetch();
$select_number->close();

// Insert into Sets table
$insert_set = $db_connection->prepare(
    "INSERT INTO Sets (workout_id, exercise_id, equipment_id, set_number, weight_lbs, reps, duration_minutes, distance_miles) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$insert_set->bind_param("iiiididd", $workout_id, $exercise_id, $set_equipment, $set_number, $weight_lbs, $reps, $set_duration, $distance_miles);

if (!$insert_set->execute()) {
    form_error("Something went wrong saving that set. Please try again.");
}
$insert_set->close();
$db_connection->close();

$_SESSION['success'] = "Set added.";
header("location: " . $workout_url);
exit();

?>
