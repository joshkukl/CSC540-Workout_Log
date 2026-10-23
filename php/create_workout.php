<?php
//======================================================================
// Create Workout (optionally copying the sets of a past workout)
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));

/* Only standard users log workouts */
if ($user_role != 2) {
    $_SESSION['message'] = "Access Denied";
    header("location: " . SRC_PATH . "/logout.php");
    exit();
}

/* Send the user back to the form with an error, keeping what they typed */
function form_error($message) {
    $_SESSION['error'] = $message;
    $_SESSION['old_input'] = array(
        'workout_date' => $_POST['workout_date'] ?? '',
        'type_name' => $_POST['type_name'] ?? '',
        'bodyweight_lbs' => $_POST['bodyweight_lbs'] ?? '',
        'facility_name' => $_POST['facility_name'] ?? '',
        'duration_minutes' => $_POST['duration_minutes'] ?? '',
        'notes' => $_POST['notes'] ?? '',
        'template_id' => $_POST['template_id'] ?? '',
    );
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

// collect value of input field (empty optional fields are stored as NULL)
$workout_date = trim($_POST['workout_date'] ?? '');
$type_name = trim($_POST['type_name'] ?? '');
$facility_name = trim($_POST['facility_name'] ?? '');
$facility_name = $facility_name === '' ? null : $facility_name;
$duration_minutes = trim($_POST['duration_minutes'] ?? '');
$duration_minutes = $duration_minutes === '' ? null : $duration_minutes;
$bodyweight_lbs = trim($_POST['bodyweight_lbs'] ?? '');
$bodyweight_lbs = $bodyweight_lbs === '' ? null : $bodyweight_lbs;
$notes = trim($_POST['notes'] ?? '');
$notes = $notes === '' ? null : $notes;
$template_id = $_POST['template_id'] ?? '';

$date = DateTime::createFromFormat('Y-m-d', $workout_date);
if (!$date || $date->format('Y-m-d') !== $workout_date) {
    form_error("Please enter a valid date.");
}
if ($workout_date > date('Y-m-d')) {
    form_error("The workout date can't be in the future.");
}
if ($type_name === '') {
    form_error("Please choose a workout type.");
}
if (strlen($type_name) > 50) {
    form_error("Workout types can be at most 50 characters.");
}
if ($duration_minutes !== null && (!ctype_digit($duration_minutes) || $duration_minutes < 1 || $duration_minutes > 1440)) {
    form_error("Duration must be a whole number of minutes.");
}
if ($bodyweight_lbs !== null && (!is_numeric($bodyweight_lbs) || $bodyweight_lbs < 50 || $bodyweight_lbs > 1000)) {
    form_error("Bodyweight must be a number of pounds between 50 and 1000.");
}

// The template has to be one of this user's own workouts
if ($template_id !== '') {
    $check_template = $db_connection->prepare(
        "SELECT workout_id FROM Workouts WHERE workout_id = ? AND user_id = ?");
    $check_template->bind_param("ii", $template_id, $user_id);
    $check_template->execute();
    $check_template->store_result();
    $template_found = $check_template->num_rows > 0;
    $check_template->close();
    if (!$template_found) {
        form_error("That past workout couldn't be found.");
    }
}

// Use the user's existing type with that name (any capitalization), or create it
$type_id = null;
$select_type = $db_connection->prepare(
    "SELECT type_id FROM Workout_Types WHERE user_id = ? AND type_name = ?");
$select_type->bind_param("is", $user_id, $type_name);
$select_type->execute();
$select_type->bind_result($type_id);
$select_type->fetch();
$select_type->close();

if ($type_id === null) {
    $insert_type = $db_connection->prepare(
        "INSERT INTO Workout_Types (user_id, type_name) VALUES (?, ?)");
    $insert_type->bind_param("is", $user_id, $type_name);
    if (!$insert_type->execute()) {
        form_error("Something went wrong saving that workout type. Please try again.");
    }
    $type_id = $insert_type->insert_id;
    $insert_type->close();
}

// Insert into Workouts table
$insert_workout = $db_connection->prepare(
    "INSERT INTO Workouts (user_id, type_id, workout_date, duration_minutes, facility_name, bodyweight_lbs, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insert_workout->bind_param("iisisds", $user_id, $type_id, $workout_date, $duration_minutes, $facility_name, $bodyweight_lbs, $notes);

if (!$insert_workout->execute()) {
    form_error("Something went wrong creating your workout. Please try again.");
}
$workout_id = $insert_workout->insert_id;
$insert_workout->close();

// Copy the template's sets into the new workout
if ($template_id !== '') {
    $copy_sets = $db_connection->prepare(
        "INSERT INTO Sets (workout_id, exercise_id, set_number, weight_lbs, reps, duration_minutes, distance_miles)
        SELECT ?, exercise_id, set_number, weight_lbs, reps, duration_minutes, distance_miles
        FROM Sets WHERE workout_id = ?
        ORDER BY set_id");
    $copy_sets->bind_param("ii", $workout_id, $template_id);
    $copy_sets->execute();
    $copy_sets->close();
    $_SESSION['success'] = "Workout created from your past workout. Delete or add sets to match what you did today.";
} else {
    $_SESSION['success'] = "Workout created! Now add your exercises and sets.";
}
$db_connection->close();

header("location: " . BASE_URL . "/user/workout.php?id=" . $workout_id);
exit();

?>
