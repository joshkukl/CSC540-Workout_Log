<?php
//======================================================================
// Remove Exercise - take one exercise (all its sets) out of a workout
// The exercise itself stays in the exercise list
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));
include_once (realpath(dirname(__FILE__).'/csrf.php'));

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

csrf_check(BASE_URL . "/user/workout.php?id=" . (int) ($_POST['workout_id'] ?? 0));

// The workout has to belong to this user
$workout_id = (int) ($_POST['workout_id'] ?? 0);
$exercise_id = (int) ($_POST['exercise_id'] ?? 0);
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

$delete_sets = $db_connection->prepare(
    "DELETE FROM Sets WHERE workout_id = ? AND exercise_id = ?");
$delete_sets->bind_param("ii", $workout_id, $exercise_id);
$delete_sets->execute();
$removed = $delete_sets->affected_rows;
$delete_sets->close();
$db_connection->close();

if ($removed > 0) {
    $_SESSION['success'] = "Exercise removed from this workout.";
} else {
    $_SESSION['error'] = "That exercise isn't in this workout.";
}
header("location: " . BASE_URL . "/user/workout.php?id=" . $workout_id);
exit();

?>
