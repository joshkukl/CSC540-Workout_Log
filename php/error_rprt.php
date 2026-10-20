<?php
//======================================================================
// ERROR AND MESSAGE REPORTS
//======================================================================

  /* Error Report */
  if (isset($error)) {
    // uses bootstrap alert style for error messages
    echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
  }
  if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger" role="alert">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']); // only show once
  }
  /* Success Report */
  if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success" role="alert">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']); // only show once
  }
  /* Message Report */
  if (isset($message)) {
    // uses bootstrap alert style for error messages
    echo '<div class="alert alert-warning" role="alert">' . $message . '</div>';
  }
  if (isset($_SESSION['message'])) {
    // uses bootstrap alert style for error messages
    echo '<div class="alert alert-warning" role="alert">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']); // only show once
  }
?>