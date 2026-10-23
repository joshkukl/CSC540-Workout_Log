<?php
//======================================================================
// Delete Set - remove a set the user logged by mistake
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

// Find the set's workout, only if that workout belongs to this user
$set_id = (int) ($_POST['set_id'] ?? 0);
$select_set = $db_connection->prepare(
    "SELECT s.workout_id, s.exercise_id, s.set_number FROM Sets s
    INNER JOIN Workouts w ON s.workout_id = w.workout_id
    WHERE s.set_id = ? AND w.user_id = ?");
$select_set->bind_param("ii", $set_id, $user_id);
$select_set->execute();
$select_set->bind_result($workout_id, $exercise_id, $set_number);
$set_found = $select_set->fetch();
$select_set->close();

if (!$set_found) {
    $_SESSION['error'] = "That set couldn't be found.";
    header("location: " . BASE_URL . "/user/log_workout.php");
    exit();
}

$delete_set = $db_connection->prepare("DELETE FROM Sets WHERE set_id = ?");
$delete_set->bind_param("i", $set_id);
$delete_set->execute();
$delete_set->close();

// Close the gap so the remaining sets of that exercise stay numbered 1, 2, 3...
$renumber_sets = $db_connection->prepare(
    "UPDATE Sets SET set_number = set_number - 1
    WHERE workout_id = ? AND exercise_id = ? AND set_number > ?");
$renumber_sets->bind_param("iii", $workout_id, $exercise_id, $set_number);
$renumber_sets->execute();
$renumber_sets->close();
$db_connection->close();

$_SESSION['success'] = "Set deleted.";
header("location: " . BASE_URL . "/user/workout.php?id=" . $workout_id);
exit();

?>
