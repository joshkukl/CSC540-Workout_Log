<?php
/**
 * create account Page
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026 
 * @date      2026-02-24
 * @version   1.0
 */

  /* Page Name */
  $page_name = "create_account";
  $page_title = "Create Account";

  /* Quick Paths */
  include_once (realpath(dirname(__FILE__).'/php/path.php'));

  /* Start The Session */
  session_start(); 

  /* Values typed before an error, so the user doesn't have to retype them */
  $old_input = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : array();
  unset($_SESSION['old_input']);
  function old_value($field) {
    global $old_input;
    return isset($old_input[$field]) ? htmlspecialchars($old_input[$field]) : '';
  }

?>
<!doctype html>
<html lang="en">
  <head>
    <?php include_once "./include/head.php"; ?>
  </head>
  <body>
    <?php include_once "./include/header.php"; ?>
    <main role="main" class="container">
      <div class="app-card auth-card">
          <div class="text-center mb-4">
            <i class="bi bi-person-plus auth-icon"></i>
            <h1 class="h3 mt-2">Create Account</h1>
            <p class="text-body-secondary mb-0">Start logging your workouts</p>
          </div>
          <form action="./php/create_account.php" method="post">
            <div class="row g-2 mb-3">
              <div class="col">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo old_value('first_name'); ?>" autocomplete="given-name" required>
              </div>
              <div class="col">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo old_value('last_name'); ?>" autocomplete="family-name" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" value="<?php echo old_value('email'); ?>" autocomplete="email" required>
            </div>
            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" id="username" name="username" value="<?php echo old_value('username'); ?>" autocomplete="username" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password" required>
              <div class="form-text">At least 8 characters.</div>
            </div>
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
            </div>
            <?php include_once (ROOT_PATH . '/php/error_rprt.php'); ?>
            <button type="submit" class="btn btn-primary w-100">Create Account</button>
          </form>
          <hr />
          <p class="text-center mb-0">
              <small>
                <a href="./index.php">Login</a> | <a href="./forgot_pass.php">Forgot Password</a>
              </small>
          </p>
      </div>
    </main>
    <?php include_once "./include/footer.php"; ?>
  </body>
</html>