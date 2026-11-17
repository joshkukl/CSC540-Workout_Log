<?php
/**
 * Account Settings Page - change your name, email and password
 * Lives in the root folder because every role (admin, user, guest) can open it
 *
 * @author    Joshua Kuklinski <kuklinskij1@southernct.edu>
 * @copyright 2026
 * @date      2026-11-10
 * @version   1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // set to 1 to display errors, 0 to hide them

  /* Quick Paths */
  include_once (realpath(dirname(__FILE__).'/php/session.php'));
  include_once (realpath(dirname(__FILE__).'/php/path.php'));
  include_once (ROOT_SRC_PATH .'/csrf.php');

  /* Page Name */
  $page_name = "settings"; // Set page name for active link in header
  $page_title = "Account Settings";

  /* Current name and email for the form */
  $select_contact = $db_connection->prepare(
    "SELECT c.first_name, c.last_name, c.email
    FROM Contacts c
    INNER JOIN Users u ON c.contact_id = u.contact_id
    WHERE u.user_id = ?");
  $select_contact->bind_param("i", $user_id);
  $select_contact->execute();
  $contact = $select_contact->get_result()->fetch_assoc();
  $select_contact->close();

  /* Values typed before an error, so the user doesn't have to retype them */
  $old_input = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : array();
  unset($_SESSION['old_input']);
  function old_value($field, $default = '') {
    global $old_input;
    return htmlspecialchars(isset($old_input[$field]) ? $old_input[$field] : $default);
  }

?>
<!doctype html>
<html lang="en">
  <head>
  <?php include_once (ROOT_PATH . '/include/head.php'); ?>
  </head>
  <body class="<?php echo $page_name; ?>">

  <?php include_once (ROOT_PATH . '/include/header.php'); ?>
    <main role="main" class="container">
      <div class="row justify-content-center">
        <div class="col-lg-7">

          <h1 class="h3 mb-3">Account Settings</h1>

          <?php include_once (ROOT_PATH . '/php/error_rprt.php'); ?>

          <?php /* Name and Email */ ?>
          <section class="app-card p-4 mb-4">
            <h2 class="h5 mb-3"><i class="bi bi-person text-accent"></i> Profile</h2>
            <form action="<?php echo SRC_PATH; ?>/update_profile.php" method="post">
              <?php echo csrf_field(); ?>
              <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($username); ?>" disabled>
              </div>
              <div class="row g-2 mb-3">
                <div class="col">
                  <label for="first_name" class="form-label">First Name</label>
                  <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo old_value('first_name', $contact['first_name']); ?>" maxlength="255" autocomplete="given-name" required>
                </div>
                <div class="col">
                  <label for="last_name" class="form-label">Last Name</label>
                  <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo old_value('last_name', $contact['last_name']); ?>" maxlength="255" autocomplete="family-name" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo old_value('email', $contact['email']); ?>" maxlength="255" autocomplete="email" required>
              </div>
              <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
            </form>
          </section>

          <?php /* Password */ ?>
          <section class="app-card p-4">
            <h2 class="h5 mb-3"><i class="bi bi-key text-accent"></i> Change Password</h2>
            <form action="<?php echo SRC_PATH; ?>/change_password.php" method="post">
              <?php echo csrf_field(); ?>
              <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
              </div>
              <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required>
                <div class="form-text">At least 8 characters.</div>
              </div>
              <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
              </div>
              <button type="submit" class="btn btn-primary"><i class="bi bi-key"></i> Change Password</button>
            </form>
          </section>

          <?php /* Deactivate Account - not for admins, or the last admin could lock everyone out */ ?>
          <?php if ($user_role != 1) { ?>
            <section class="app-card border-danger p-4 mt-4">
              <h2 class="h5 mb-2 text-danger"><i class="bi bi-person-slash"></i> Deactivate Account</h2>
              <p class="text-body-secondary">You'll be logged out and won't be able to log back in. Your workouts are kept, and an administrator can reactivate your account.</p>
              <form action="<?php echo SRC_PATH; ?>/deactivate_account.php" method="post"
                    onsubmit="return confirm('Deactivate your account? You will be logged out and need an administrator to reactivate it.');">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                  <label for="deactivate_password" class="form-label">Enter your password to confirm</label>
                  <input type="password" class="form-control" id="deactivate_password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-person-slash"></i> Deactivate Account</button>
              </form>
            </section>
          <?php } ?>

        </div>
      </div>
    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
