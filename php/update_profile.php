<?php
//======================================================================
// Update Profile - the logged-in user's name and email
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));
include_once (ROOT_SRC_PATH . '/csrf.php');

$settings_page = BASE_URL . "/settings.php";

/* Send the user back to the form with an error, keeping what they typed */
function form_error($message) {
  global $settings_page;
  $_SESSION['error'] = $message;
  $_SESSION['old_input'] = array(
    'first_name' => $_POST['first_name'] ?? '',
    'last_name' => $_POST['last_name'] ?? '',
    'email' => $_POST['email'] ?? '',
  );
  header("location: " . $settings_page);
  exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
  header("location: " . $settings_page);
  exit();
}
csrf_check($settings_page);

// collect value of input field
$new_first = trim($_POST['first_name'] ?? '');
$new_last = trim($_POST['last_name'] ?? '');
$new_email = trim($_POST['email'] ?? '');

if ($new_first === '' || $new_last === '' || $new_email === '') {
  form_error("All fields are required.");
}
if (strlen($new_first) > 255 || strlen($new_last) > 255 || strlen($new_email) > 255) {
  form_error("Names and email can be at most 255 characters.");
}
if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
  form_error("Please enter a valid email address.");
}

// Update this user's row in the Contacts table
$update_contact = $db_connection->prepare(
  "UPDATE Contacts c
  INNER JOIN Users u ON c.contact_id = u.contact_id
  SET c.first_name = ?, c.last_name = ?, c.email = ?, c.updated = NOW()
  WHERE u.user_id = ?");
$update_contact->bind_param("sssi", $new_first, $new_last, $new_email, $user_id);

if (!$update_contact->execute()) {
  form_error("Something went wrong saving your profile. Please try again.");
}
$update_contact->close();
$db_connection->close();

$_SESSION['success'] = "Your profile has been updated.";
header("location: " . $settings_page);
exit();

?>
