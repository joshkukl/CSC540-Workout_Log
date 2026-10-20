<?php
//======================================================================
// LOGOUT
//======================================================================

include_once (realpath(dirname(__FILE__).'/path.php'));

session_start();

/* Keep any login message so the login page can show it */
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;

if(session_destroy()) {
  if ($message) {
    session_start();
    $_SESSION['message'] = $message;
  }
  header("Location: " . BASE_URL);
  exit();
}

?>