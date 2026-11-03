<?php
//======================================================================
// CSRF TOKENS for the user-side forms
// Same idea as Josh's admin/accounts.php (and the same session key), so a
// form only works when it was submitted from one of our own pages.
//   Pages:    echo csrf_field(); inside every POST form
//   Handlers: csrf_check($back_url); before changing anything
//======================================================================

/* The token for this session, made the first time it's needed */
function csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf_token'];
}

/* Hidden input to put inside a form */
function csrf_field() {
  return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/* Stop the request (back to $back_url with an error) unless the token matches */
function csrf_check($back_url) {
  if (empty($_POST['csrf_token'])
      || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['error'] = "That request could not be verified. Please try again.";
    header("location: " . $back_url);
    exit();
  }
}

?>
