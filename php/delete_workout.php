<?php
//======================================================================
// Delete Workout - remove a whole workout session
// Its sets are deleted too (Sets has ON DELETE CASCADE on workout_id)
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
    header("location: " . BASE_URL . "/user/");
    exit();
}

csrf_check(BASE_URL . "/user/");

// Only delete it if the workout belongs to this user
$workout_id = (int) ($_POST['workout_id'] ?? 0);
$delete_workout = $db_connection->prepare(
    "DELETE FROM Workouts WHERE workout_id = ? AND user_id = ?");
$delete_workout->bind_param("ii", $workout_id, $user_id);
$delete_workout->execute();
$deleted = $delete_workout->affected_rows == 1;
$delete_workout->close();
$db_connection->close();

if ($deleted) {
    $_SESSION['success'] = "Workout deleted.";
} else {
    $_SESSION['error'] = "That workout couldn't be found.";
}
header("location: " . BASE_URL . "/user/");
exit();

?>
