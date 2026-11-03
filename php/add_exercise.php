<?php
//======================================================================
// Add Exercise - a user adds their own exercise to the exercise list
// It's saved with their user_id, so only they see it (built-ins have NULL)
// Optional muscle groups go in Exercise_Muscle_Map, so the history
// page's muscle chart counts it like a built-in
// Re-adding the name of one they deleted (archived) brings it back
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

/* Go back to the workout the form was on */
$workout_id = (int) ($_POST['workout_id'] ?? 0);
$workout_url = BASE_URL . "/user/workout.php?id=" . $workout_id;
csrf_check($workout_url);

/* Send the user back to the workout with an error */
function form_error($message) {
    global $workout_url;
    $_SESSION['error'] = $message;
    header("location: " . $workout_url);
    exit();
}

// Name: trimmed, inner spaces collapsed, 1-100 characters
$exercise_name = trim(preg_replace('/\s+/', ' ', $_POST['exercise_name'] ?? ''));
$category = $_POST['category'] ?? '';
if ($exercise_name === '' || mb_strlen($exercise_name) > 100) {
    form_error("Please enter an exercise name (up to 100 characters).");
}
if (!in_array($category, array('Strength', 'Cardio'), true)) {
    form_error("Please choose Strength or Cardio.");
}

// Muscle groups ticked: keep only ids that really exist
$valid_muscles = array_column(
    $db_connection->query("SELECT muscle_group_id FROM Muscle_Groups")->fetch_all(MYSQLI_ASSOC),
    'muscle_group_id');
$muscles = array_intersect(array_map('intval', (array) ($_POST['muscles'] ?? array())), $valid_muscles);

/* Replace the exercise's muscle groups with the ticked ones */
function save_muscles($exercise_id, $muscles) {
    global $db_connection;
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
}

// Same name (case doesn't matter) as a built-in, or as one of this user's own?
$select_same = $db_connection->prepare(
    "SELECT exercise_id, user_id, is_archived, category FROM Exercises
    WHERE LOWER(exercise_name) = LOWER(?) AND (user_id IS NULL OR user_id = ?)");
$select_same->bind_param("si", $exercise_name, $user_id);
$select_same->execute();
$same_names = $select_same->get_result()->fetch_all(MYSQLI_ASSOC);
$select_same->close();

$archived = null;
foreach ($same_names as $same) {
    if ($same['user_id'] === null || !$same['is_archived']) {
        form_error(htmlspecialchars($exercise_name) . " is already in your exercise list.");
    }
    $archived = $same;
}

if ($archived) {
    // One they deleted earlier: bring it back. Its kind stays the same, because past
    // workouts logged it that way (weight/reps vs time/distance)
    $exercise_id = $archived['exercise_id'];
    $restore = $db_connection->prepare("UPDATE Exercises SET is_archived = 0 WHERE exercise_id = ?");
    $restore->bind_param("i", $exercise_id);
    $restore->execute();
    $restore->close();
    if (!empty($muscles)) {
        save_muscles($exercise_id, $muscles);
    }
    $_SESSION['success'] = htmlspecialchars($exercise_name) . " is back in your list, with your past sets."
        . ($archived['category'] != $category ? " It stays " . $archived['category'] . ", since that's how you logged it before." : "");
} else {
    $insert_exercise = $db_connection->prepare(
        "INSERT INTO Exercises (user_id, equipment_id, exercise_name, category) VALUES (?, NULL, ?, ?)");
    $insert_exercise->bind_param("iss", $user_id, $exercise_name, $category);
    $insert_exercise->execute();
    $exercise_id = $insert_exercise->insert_id;
    $insert_exercise->close();
    save_muscles($exercise_id, $muscles);
    $_SESSION['success'] = htmlspecialchars($exercise_name) . " added. It's selected in the exercise list.";
}

$_SESSION['last_exercise'] = $exercise_id; // pre-select it in the add-set form
$db_connection->close();
header("location: " . $workout_url);
exit();

?>
