<?php
//======================================================================
// Delete Equipment - remove one of the user's own machines from their list
// Built-in equipment (user_id NULL) is managed by admins, not here
// Never used: deleted for real
// Used for logged sets: archived instead (hidden from the add-set list),
//   so those sets and the history charts keep their machine
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

/* Go back to the workout the button was on */
$workout_id = (int) ($_POST['workout_id'] ?? 0);
$workout_url = BASE_URL . "/user/workout.php?id=" . $workout_id;
csrf_check($workout_url);

// Only equipment this user added (and hasn't already deleted)
$equipment_id = (int) ($_POST['equipment_id'] ?? 0);
$select_equipment = $db_connection->prepare(
    "SELECT brand_name, machine_type,
        (SELECT COUNT(*) FROM Sets WHERE equipment_id = ?) + (SELECT COUNT(*) FROM Exercises WHERE equipment_id = ?)
    FROM Equipment WHERE equipment_id = ? AND user_id = ? AND is_archived = 0");
$select_equipment->bind_param("iiii", $equipment_id, $equipment_id, $equipment_id, $user_id);
$select_equipment->execute();
$select_equipment->bind_result($brand_name, $machine_type, $times_used);
$equipment_found = $select_equipment->fetch();
$select_equipment->close();
if (!$equipment_found) {
    $_SESSION['error'] = "You can only delete equipment you added.";
    header("location: " . $workout_url);
    exit();
}
$display_name = htmlspecialchars(equipment_name($brand_name, $machine_type));

if ($times_used > 0) {
    $archive = $db_connection->prepare("UPDATE Equipment SET is_archived = 1 WHERE equipment_id = ?");
    $archive->bind_param("i", $equipment_id);
    $archive->execute();
    $archive->close();
    $_SESSION['success'] = $display_name . " removed from your list. Sets you logged on it keep it. Add it again to bring it back.";
} else {
    $delete_equipment = $db_connection->prepare("DELETE FROM Equipment WHERE equipment_id = ?");
    $delete_equipment->bind_param("i", $equipment_id);
    $delete_equipment->execute();
    $delete_equipment->close();
    $_SESSION['success'] = $display_name . " deleted from your equipment.";
}
$db_connection->close();

if (($_SESSION['last_set']['equipment_id'] ?? '') == $equipment_id) {
    unset($_SESSION['last_set']);
}
header("location: " . $workout_url . "#my-equipment");
exit();

?>
