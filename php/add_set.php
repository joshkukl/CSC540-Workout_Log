<?php
//======================================================================
// Add Set - one set of an exercise in a workout
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));

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

/* Empty number fields become NULL, anything else must be a number in range */
function number_or_null($field, $min, $max, $label) {
    $value = trim($_POST[$field] ?? '');
    if ($value === '') {
        return null;
    }
    if (!is_numeric($value) || $value < $min || $value > $max) {
        form_error($label . " must be a number between " . $min . " and " . $max . ".");
    }
    return $value;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

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

// The exercise decides which fields are used
$exercise_id = (int) ($_POST['exercise_id'] ?? 0);
$select_exercise = $db_connection->prepare(
    "SELECT category FROM Exercises WHERE exercise_id = ?");
$select_exercise->bind_param("i", $exercise_id);
$select_exercise->execute();
$select_exercise->bind_result($category);
$exercise_found = $select_exercise->fetch();
$select_exercise->close();
if (!$exercise_found) {
    form_error("Please choose an exercise.");
}
$_SESSION['last_exercise'] = $exercise_id;

// Strength sets use weight/reps, cardio sets use duration/distance
$weight_lbs = $reps = $set_duration = $distance_miles = null;
if ($category == 'Strength') {
    $weight_lbs = number_or_null('weight_lbs', 0, 2000, "Weight");
    $reps = number_or_null('reps', 1, 1000, "Reps");
    if ($reps === null || !ctype_digit($reps)) {
        form_error("Please enter the number of reps as a whole number.");
    }
} else {
    $set_duration = number_or_null('duration_minutes', 0, 1440, "Time");
    $distance_miles = number_or_null('distance_miles', 0, 500, "Distance");
    if ($set_duration === null && $distance_miles === null) {
        form_error("Please enter a time, a distance, or both.");
    }
}

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
    "INSERT INTO Sets (workout_id, exercise_id, set_number, weight_lbs, reps, duration_minutes, distance_miles) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insert_set->bind_param("iiididd", $workout_id, $exercise_id, $set_number, $weight_lbs, $reps, $set_duration, $distance_miles);

if (!$insert_set->execute()) {
    form_error("Something went wrong saving that set. Please try again.");
}
$insert_set->close();
$db_connection->close();

$_SESSION['success'] = "Set added.";
header("location: " . $workout_url);
exit();

?>
