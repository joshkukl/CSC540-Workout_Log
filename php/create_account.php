<?php
//======================================================================
// Create Account
//======================================================================

include_once (realpath(dirname(__FILE__).'/path.php'));
include_once (realpath(dirname(__FILE__).'/config.php'));

session_start();

/* Salt used for seasoning */
$salt = SALT;

/* Send the user back to the form with an error, keeping what they typed */
function form_error($message) {
    $_SESSION['error'] = $message;
    $_SESSION['old_input'] = array(
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'username' => $_POST['username'] ?? '',
    );
    header("location: " . BASE_URL . "/create_account.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: " . BASE_URL . "/create_account.php");
    exit();
}

// collect value of input field
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
    form_error("All fields are required.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    form_error("Please enter a valid email address.");
}
if (strlen($password) < 8) {
    form_error("Password must be at least 8 characters.");
}
if ($password !== $confirm_password) {
    form_error("Passwords do not match.");
}

// Check if username already exists
$check_user = $db_connection->prepare(
    "SELECT user_id FROM Credentials WHERE username = ?");
$check_user->bind_param("s", $username);
$check_user->execute();
$check_user->store_result();

if ($check_user->num_rows > 0) {
    $check_user->close();
    form_error("That username is taken. Please choose a different one.");
}
$check_user->close();

// Insert into Contacts table (phone and address are not collected)
$phone = "";
$insert_contact = $db_connection->prepare(
    "INSERT INTO Contacts (first_name, last_name, email, phone, updated) VALUES (?, ?, ?, ?, NOW())");
$insert_contact->bind_param("ssss", $first_name, $last_name, $email, $phone);

if (!$insert_contact->execute()) {
    form_error("Something went wrong creating your account. Please try again.");
}
// Get the contact_id of the newly inserted contact
$contact_id = $insert_contact->insert_id;
$insert_contact->close();

// Insert into Users table with default role_id = 2 (user)
$role_id = 2; // Default role_id for regular users
$insert_user = $db_connection->prepare(
    "INSERT INTO Users (contact_id, role_id, creation_date) VALUES (?, ?, NOW())");
$insert_user->bind_param("ii", $contact_id, $role_id);

if (!$insert_user->execute()) {
    form_error("Something went wrong creating your account. Please try again.");
}
// Get the user_id of the newly inserted user
$user_id = $insert_user->insert_id;
$insert_user->close();

// Insert into Credentials table with hashed password
//$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$password_salted = crypt($password, $salt);
$insert_credential = $db_connection->prepare(
    "INSERT INTO Credentials (user_id, username, password_salted) VALUES (?, ?, ?)");
$insert_credential->bind_param("iss", $user_id, $username, $password_salted);

if (!$insert_credential->execute()) {
    form_error("Something went wrong creating your account. Please try again.");
}
$insert_credential->close();
$db_connection->close();

// Back to the login page with a success message
$_SESSION['success'] = "Account created! You can log in now.";
header("location: " . BASE_URL . "/index.php");
exit();

?>
