<?php
//======================================================================
// Delete Exercise - remove one of the user's own exercises from their list
// Built-in exercises (user_id NULL) can't be deleted here
// Never used: deleted for real
// Used in past workouts: archived instead (hidden from the list and the
//   add-set dropdown), so those workouts and the history keep their sets
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

/* Go back to the workout the button was on */
$workout_id = (int) ($_POST['workout_id'] ?? 0);
$workout_url = BASE_URL . "/user/workout.php?id=" . $workout_id;
csrf_check($workout_url);

// Only exercises this user added (and hasn't already deleted)
$exercise_id = (int) ($_POST['exercise_id'] ?? 0);
$select_exercise = $db_connection->prepare(
    "SELECT exercise_name FROM Exercises WHERE exercise_id = ? AND user_id = ? AND is_archived = 0");
$select_exercise->bind_param("ii", $exercise_id, $user_id);
$select_exercise->execute();
$select_exercise->bind_result($exercise_name);
$exercise_found = $select_exercise->fetch();
$select_exercise->close();
if (!$exercise_found) {
    $_SESSION['error'] = "You can only delete exercises you added.";
    header("location: " . $workout_url);
    exit();
}

// How many workouts still use it
$count_workouts = $db_connection->prepare(
    "SELECT COUNT(DISTINCT workout_id) FROM Sets WHERE exercise_id = ?");
$count_workouts->bind_param("i", $exercise_id);
$count_workouts->execute();
$count_workouts->bind_result($workouts_using);
$count_workouts->fetch();
$count_workouts->close();

if ($workouts_using > 0) {
    $archive = $db_connection->prepare("UPDATE Exercises SET is_archived = 1 WHERE exercise_id = ?");
    $archive->bind_param("i", $exercise_id);
    $archive->execute();
    $archive->close();
    $_SESSION['success'] = htmlspecialchars($exercise_name) . " removed from your list. "
        . ($workouts_using == 1 ? "The 1 workout" : "The " . $workouts_using . " workouts")
        . " that used it keep their sets. Add it again by name to bring it back.";
} else {
    // Its muscle-group links go too (Exercise_Muscle_Map has ON DELETE CASCADE)
    $delete_exercise = $db_connection->prepare("DELETE FROM Exercises WHERE exercise_id = ?");
    $delete_exercise->bind_param("i", $exercise_id);
    $delete_exercise->execute();
    $delete_exercise->close();
    $_SESSION['success'] = htmlspecialchars($exercise_name) . " deleted from your exercises.";
}
$db_connection->close();

if (($_SESSION['last_exercise'] ?? '') == $exercise_id) {
    unset($_SESSION['last_exercise']);
}
header("location: " . $workout_url);
exit();

?>
