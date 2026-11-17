<?php
//======================================================================
// Add Equipment - a user adds a machine from their gym, like "Hoist Lat Pulldown"
// It's saved with their user_id, so only they see it. Built-in equipment
// (user_id NULL) is the short generic list admins manage
// Re-adding one they deleted (archived) brings it back
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));
include_once (realpath(dirname(__FILE__).'/csrf.php'));
include_once (realpath(dirname(__FILE__).'/format.php'));

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
    header("location: " . $workout_url . "#my-equipment");
    exit();
}

// Trimmed, inner spaces collapsed; the machine is required, the brand isn't
$brand_name = trim(preg_replace('/\s+/', ' ', $_POST['brand_name'] ?? ''));
$machine_type = trim(preg_replace('/\s+/', ' ', $_POST['machine_type'] ?? ''));
if ($machine_type === '' || mb_strlen($machine_type) > 100) {
    form_error("Please enter the machine (up to 100 characters).");
}
if (mb_strlen($brand_name) > 100) {
    form_error("The brand can be at most 100 characters.");
}
$display_name = htmlspecialchars(equipment_name($brand_name, $machine_type));

// Same brand and machine (case doesn't matter) as a built-in, or as one of this user's own?
$select_same = $db_connection->prepare(
    "SELECT equipment_id, user_id, is_archived FROM Equipment
    WHERE LOWER(brand_name) = LOWER(?) AND LOWER(machine_type) = LOWER(?) AND (user_id IS NULL OR user_id = ?)");
$select_same->bind_param("ssi", $brand_name, $machine_type, $user_id);
$select_same->execute();
$same_names = $select_same->get_result()->fetch_all(MYSQLI_ASSOC);
$select_same->close();

$archived = null;
foreach ($same_names as $same) {
    if ($same['user_id'] === null || !$same['is_archived']) {
        form_error($display_name . " is already in your equipment list.");
    }
    $archived = $same;
}

if ($archived) {
    // One they deleted earlier: bring it back
    $equipment_id = $archived['equipment_id'];
    $restore = $db_connection->prepare("UPDATE Equipment SET is_archived = 0 WHERE equipment_id = ?");
    $restore->bind_param("i", $equipment_id);
    $restore->execute();
    $restore->close();
    $_SESSION['success'] = $display_name . " is back in your list. It's selected for your next set.";
} else {
    $insert_equipment = $db_connection->prepare(
        "INSERT INTO Equipment (user_id, brand_name, machine_type) VALUES (?, ?, ?)");
    $insert_equipment->bind_param("iss", $user_id, $brand_name, $machine_type);
    $insert_equipment->execute();
    $equipment_id = $insert_equipment->insert_id;
    $insert_equipment->close();
    $_SESSION['success'] = $display_name . " added. It's selected for your next set.";
}

// Pre-select it in the add-set form (user/workout.php reads last_set)
$_SESSION['last_set'] = array('exercise_id' => $_SESSION['last_exercise'] ?? '', 'equipment_id' => $equipment_id);
$db_connection->close();
header("location: " . $workout_url);
exit();

?>
