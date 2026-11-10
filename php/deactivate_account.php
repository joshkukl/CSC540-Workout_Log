<?php
//======================================================================
// Deactivate Account - the logged-in user turns off their own account
// Uses is_suspended, so an admin can reactivate it from Manage Accounts
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

/* Admins can't lock themselves out, same rule as Manage Accounts */
if ($user_role == 1) {
  form_error("Admin accounts can't be deactivated.");
}

// The password has to be right, same check as logging in
$select_password = $db_connection->prepare(
  "SELECT password_salted FROM Credentials WHERE user_id = ?");
$select_password->bind_param("i", $user_id);
$select_password->execute();
$select_password->bind_result($password_salted);
$select_password->fetch();
$select_password->close();

if (!$password_salted || !hash_equals($password_salted, crypt($_POST['password'] ?? '', SALT))) {
  form_error("Your password is incorrect.");
}

$update_user = $db_connection->prepare("UPDATE Users SET is_suspended = 1 WHERE user_id = ?");
$update_user->bind_param("i", $user_id);

if (!$update_user->execute()) {
  form_error("Something went wrong deactivating your account. Please try again.");
}
$update_user->close();
$db_connection->close();

// logout.php keeps this message for the login page
$_SESSION['message'] = "Your account has been deactivated. An administrator can reactivate it.";
header("location: " . SRC_PATH . "/logout.php");
exit();

?>
