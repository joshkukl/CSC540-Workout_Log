<?php
/**
 * Forgot Password  Page
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026 
 * @date      2026-02-24
 * @version   1.0
 */

  /* Page Name */
  $page_name = "forgot_pass";
  $page_title = "Account Recovery";

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
            <i class="bi bi-key auth-icon"></i>
            <h1 class="h3 mt-2">Account Recovery</h1>
            <p class="text-body-secondary mb-0">Enter the email on your account</p>
          </div>
          <form action="./db/recovery.php" method="post">
            <div class="input-group mb-3">
              <input type="email" class="form-control" id="email" aria-describedby="email" placeholder="Enter Email" name="email" required>
            </div>
            <?php
              /* Error Message */
              if (isset($error)) {
                // uses bootstrap alert style for error messages
                echo '<div class="alert alert-danger" role="alert">' . $error . '</div>';
              }
            ?>
            <button type="submit" class="btn btn-primary w-100">Next</button>
          </form>
          <hr />
          <p class="text-center mb-0">
            <small>
              <a href="./create_account.php">Create An Account</a> | <a href="./">Login</a>
            </small>
          </p>
      </div>
    </main>
    <?php include_once "./include/footer.php"; ?>
  </body>
</html>