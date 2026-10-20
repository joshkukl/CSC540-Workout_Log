<?php
/**
 * login Page
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026 
 * @date      2026-02-24
 * @version   1.0
 */
  /* Quick Paths */
  include_once (realpath(dirname(__FILE__).'/php/path.php'));


  /* Page Name */
  $page_name = "home";

  /* Start The Session */
  session_start(); 

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
          <i class="bi bi-activity auth-icon"></i>
          <h1 class="h3 mt-2">Welcome Back</h1>
          <p class="text-body-secondary mb-0">Log in to track your workouts</p>
        </div>
        <form action="./php/authenticate.php" method="post">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" placeholder="Enter username" name="username" autocomplete="username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" placeholder="Enter password" name="password" autocomplete="current-password" required>
          </div>

          <?php include_once (ROOT_PATH . '/php/error_rprt.php'); ?>
          <button type="submit" class="btn btn-primary w-100">Log In</button>
        </form>
        <hr />
        <p class="text-center mb-0">
            <small>
              <a href="./create_account.php">Create An Account</a> | <a href="./forgot_pass.php">Forgot Password</a>
            </small>
        </p>
      </div>
    </main>
    <?php include_once "./include/footer.php"; ?>
  </body>
</html>