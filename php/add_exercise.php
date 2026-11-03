<?php
//======================================================================
// Add Exercise - a user adds their own exercise to the exercise list
// It's saved with their user_id, so only they see it (built-ins have NULL)
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));

/* Only standard users log workouts */
if ($user_role != 2) {
    $_SESSION['message'] = "Access Denied";
    header("location: " . SRC_PATH . "/logout.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

/* Go back to the workout the form was on */
$workout_id = (int) ($_POST['workout_id'] ?? 0);
$workout_url = BASE_URL . "/user/workout.php?id=" . $workout_id;

// Name: trimmed, inner spaces collapsed, 1-100 characters
$exercise_name = trim(preg_replace('/\s+/', ' ', $_POST['exercise_name'] ?? ''));
$category = $_POST['category'] ?? '';
if ($exercise_name === '' || mb_strlen($exercise_name) > 100) {
    $_SESSION['error'] = "Please enter an exercise name (up to 100 characters).";
    header("location: " . $workout_url);
    exit();
}
if (!in_array($category, array('Strength', 'Cardio'), true)) {
    $_SESSION['error'] = "Please choose Strength or Cardio.";
    header("location: " . $workout_url);
    exit();
}

// No duplicates of a built-in or one of this user's own (case doesn't matter)
$check_name = $db_connection->prepare(
    "SELECT exercise_id FROM Exercises
    WHERE LOWER(exercise_name) = LOWER(?) AND (user_id IS NULL OR user_id = ?)");
$check_name->bind_param("si", $exercise_name, $user_id);
$check_name->execute();
$check_name->store_result();
$name_taken = $check_name->num_rows > 0;
$check_name->close();
if ($name_taken) {
    $_SESSION['error'] = htmlspecialchars($exercise_name) . " is already in your exercise list.";
    header("location: " . $workout_url);
    exit();
}

$insert_exercise = $db_connection->prepare(
    "INSERT INTO Exercises (user_id, equipment_id, exercise_name, category) VALUES (?, NULL, ?, ?)");
$insert_exercise->bind_param("iss", $user_id, $exercise_name, $category);
$insert_exercise->execute();
$_SESSION['last_exercise'] = $insert_exercise->insert_id; // pre-select it in the add-set form
$insert_exercise->close();
$db_connection->close();

$_SESSION['success'] = htmlspecialchars($exercise_name) . " added. It's selected in the exercise list.";
header("location: " . $workout_url);
exit();

?>
