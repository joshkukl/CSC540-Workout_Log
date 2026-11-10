<?php
//======================================================================
// Change Password - the logged-in user's own password
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));
include_once (ROOT_SRC_PATH . '/csrf.php');

$settings_page = BASE_URL . "/settings.php";

/* Send the user back to the form with an error */
function form_error($message) {
  global $settings_page;
  $_SESSION['error'] = $message;
  header("location: " . $settings_page);
  exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
  header("location: " . $settings_page);
  exit();
}
csrf_check($settings_page);

// collect value of input field
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($current_password === '' || $new_password === '') {
  form_error("All password fields are required.");
}
if (strlen($new_password) < 8) {
  form_error("New password must be at least 8 characters.");
}
if ($new_password !== $confirm_password) {
  form_error("New passwords do not match.");
}

// The current password has to be right, same check as logging in
$select_password = $db_connection->prepare(
  "SELECT password_salted FROM Credentials WHERE user_id = ?");
$select_password->bind_param("i", $user_id);
$select_password->execute();
$select_password->bind_result($password_salted);
$select_password->fetch();
$select_password->close();

if (!$password_salted || !hash_equals($password_salted, crypt($current_password, SALT))) {
  form_error("Your current password is incorrect.");
}

// Update the Credentials table
$new_salted = crypt($new_password, SALT);
$update_password = $db_connection->prepare(
  "UPDATE Credentials SET password_salted = ? WHERE user_id = ?");
$update_password->bind_param("si", $new_salted, $user_id);

if (!$update_password->execute()) {
  form_error("Something went wrong changing your password. Please try again.");
}
$update_password->close();
$db_connection->close();

$_SESSION['success'] = "Your password has been changed.";
header("location: " . $settings_page);
exit();

?>
