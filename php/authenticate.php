<?php

/**
 * Authentication Script
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026 
 * @date      2026-02-24
 * @version   1.0
 */

// Include necessary configuration and path files
include_once (realpath(dirname(__FILE__).'/path.php'));
include_once (realpath(dirname(__FILE__).'/config.php'));

//-----------------------------------------------------
// Authenticate
//-----------------------------------------------------
if (isset($_POST['submit'])) {
  // Validate input
  if (empty($_POST['username']) || empty($_POST['password'])) {
    // Handle empty input
    $error = "Username or Password is empty!";
    return $error;
  }
} else {
/* Check the Username and Password */
  if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Define variables
    $username = $_POST['username'];
    $user_roll = 0;
    // Protect against MYSQL injection
    $username = stripslashes($username);
    $username = mysqli_real_escape_string($db_connection, $username);
    // SQL query to fetch the user and their password hash
    $select_users = $db_connection->prepare(
        // SQL query to fetch user details
        "SELECT u.user_id, u.role_id, cr.username, cr.password_salted, u.is_suspended
         FROM Users u
         JOIN Credentials cr ON u.user_id = cr.user_id
         WHERE cr.username = ? LIMIT 1");
    $select_users->bind_param("s", $username);
    $select_users->execute();
    $select_users->bind_result($user_id, $user_role, $username, $password_salted, $is_suspended);
    $select_users->store_result();

    //Checking the result of the query: exactly 1 match, and the password fits its hash
    if($select_users->num_rows == 1 && $select_users->fetch()
        && password_verify($_POST['password'] ?? '', $password_salted)) {
        // Accounts made before password_hash() still have an old crypt() hash
        // (only the first 8 characters counted) - now that we know the password, replace it
        if (password_needs_rehash($password_salted, PASSWORD_DEFAULT)) {
          $new_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
          $update_hash = $db_connection->prepare("UPDATE Credentials SET password_salted = ? WHERE user_id = ?");
          $update_hash->bind_param("si", $new_hash, $user_id);
          $update_hash->execute();
          $update_hash->close();
        }
        // Correct password, but suspended accounts may not log in
        if ($is_suspended) {
          session_start();
          $_SESSION['message'] = "This account is not active. Please contact an administrator to reactivate it.";
          header("location: " . SRC_PATH . "/logout.php");
          exit();
        }
        // Successful login
        session_start(); 
        # This area needs to send users and admins to there own directory
        $_SESSION['user_id'] = $user_id;
        $_SESSION['login_user']=$username;
        $_SESSION['user_role'] = $user_role;
        // Redirect based on user role
        if ($_SESSION['user_role'] == 1) {
          header("location: " . BASE_URL . "/admin");
        } elseif ($_SESSION['user_role'] == 2) {
          header("location:" . BASE_URL . "/user");
        } elseif ($_SESSION['user_role'] == 3) {
          header("location:" . BASE_URL . "/guest");
        } else {
          $_SESSION['error'] = "Login Failed";
          
        }
  } else {
    // Invalid login
    session_start();
    $_SESSION['message'] = "Username or Password did not match!";
    // Redirect to logout
    header("location: " . SRC_PATH . "/logout.php"); 
    exit();
  }
  // close the mysql connection
  $select_user->close();
} else {
  // Direct access without POST data
  header("location: " . SRC_PATH . "/logout.php"); 
}
}