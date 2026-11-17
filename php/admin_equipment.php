<?php
//======================================================================
// Admin Equipment - add, edit or delete a piece of equipment
//   action=save   (equipment_id empty to add, set to edit)
//   action=delete (only when no exercise or logged set uses it)
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));
include_once (ROOT_SRC_PATH . '/csrf.php');

$exercises_page = BASE_URL . "/admin/exercises.php";

/* Only admins */
if ($user_role != 1) {
  $_SESSION['message'] = "Access Denied";
  header("location: " . SRC_PATH . "/logout.php");
  exit();
}
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  header("location: " . $exercises_page);
  exit();
}
csrf_check($exercises_page);

$equipment_id = (int) ($_POST['equipment_id'] ?? 0);
$action = $_POST['action'] ?? '';

/* Send the admin back to the form with an error, keeping what they typed */
function form_error($message) {
  global $exercises_page, $equipment_id;
  $_SESSION['error'] = $message;
  $_SESSION['old_input'] = array(
    'form' => 'equipment',
    'machine_type' => $_POST['machine_type'] ?? '',
    'brand_name' => $_POST['brand_name'] ?? '',
  );
  header("location: " . $exercises_page . ($equipment_id ? "?edit_equipment=" . $equipment_id : ""));
  exit();
}

/* Equipment being edited or deleted has to exist */
if ($equipment_id) {
  $select_equipment = $db_connection->prepare(
    "SELECT machine_type,
      (SELECT COUNT(*) FROM Exercises WHERE equipment_id = ?),
      (SELECT COUNT(*) FROM Sets WHERE equipment_id = ?)
    FROM Equipment WHERE equipment_id = ? AND user_id IS NULL");
  $select_equipment->bind_param("iii", $equipment_id, $equipment_id, $equipment_id);
  $select_equipment->execute();
  $select_equipment->bind_result($current_type, $exercise_count, $set_count);
  $equipment_found = $select_equipment->fetch();
  $select_equipment->close();
  if (!$equipment_found) {
    $_SESSION['error'] = "That equipment couldn't be found.";
    header("location: " . $exercises_page);
    exit();
  }
}

if ($action == 'delete') {
  if (!$equipment_id) {
    form_error("Please choose equipment to delete.");
  }
  // The foreign keys from Exercises and Sets would block it anyway; say why instead
  if ($exercise_count > 0) {
    $_SESSION['error'] = htmlspecialchars($current_type) . " is used by " . $exercise_count
      . ($exercise_count == 1 ? " exercise" : " exercises") . ". Change those exercises' equipment first.";
    header("location: " . $exercises_page);
    exit();
  }
  if ($set_count > 0) {
    $_SESSION['error'] = htmlspecialchars($current_type) . " has been used for " . $set_count
      . ($set_count == 1 ? " logged set" : " logged sets") . ", so it stays in the list to keep users' history.";
    header("location: " . $exercises_page);
    exit();
  }
  $delete_equipment = $db_connection->prepare("DELETE FROM Equipment WHERE equipment_id = ?");
  $delete_equipment->bind_param("i", $equipment_id);
  $delete_equipment->execute();
  $delete_equipment->close();
  $db_connection->close();
  $_SESSION['success'] = htmlspecialchars($current_type) . " deleted.";
  header("location: " . $exercises_page);
  exit();
}

if ($action != 'save') {
  form_error("Unknown action.");
}

// collect value of input field (brand is optional, stored as an empty string)
$machine_type = trim(preg_replace('/\s+/', ' ', $_POST['machine_type'] ?? ''));
$brand_name = trim(preg_replace('/\s+/', ' ', $_POST['brand_name'] ?? ''));

if ($machine_type === '') {
  form_error("Please enter the type of equipment.");
}
if (mb_strlen($machine_type) > 255 || mb_strlen($brand_name) > 255) {
  form_error("Type and brand can be at most 255 characters.");
}

// Same type and brand (case doesn't matter) already in the list?
$select_same = $db_connection->prepare(
  "SELECT equipment_id FROM Equipment
  WHERE LOWER(machine_type) = LOWER(?) AND LOWER(brand_name) = LOWER(?) AND equipment_id != ? AND user_id IS NULL");
$select_same->bind_param("ssi", $machine_type, $brand_name, $equipment_id);
$select_same->execute();
$select_same->store_result();
$is_duplicate = $select_same->num_rows > 0;
$select_same->close();
if ($is_duplicate) {
  form_error("That equipment is already in the list.");
}

if ($equipment_id) {
  $update_equipment = $db_connection->prepare(
    "UPDATE Equipment SET machine_type = ?, brand_name = ? WHERE equipment_id = ?");
  $update_equipment->bind_param("ssi", $machine_type, $brand_name, $equipment_id);
  $update_equipment->execute();
  $update_equipment->close();
  $_SESSION['success'] = htmlspecialchars($machine_type) . " saved.";
} else {
  $insert_equipment = $db_connection->prepare(
    "INSERT INTO Equipment (machine_type, brand_name) VALUES (?, ?)");
  $insert_equipment->bind_param("ss", $machine_type, $brand_name);
  $insert_equipment->execute();
  $insert_equipment->close();
  $_SESSION['success'] = htmlspecialchars($machine_type) . " added.";
}
$db_connection->close();

header("location: " . $exercises_page);
exit();

?>
