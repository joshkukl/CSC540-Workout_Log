<?php
/**
 * Admin Account Management Page
 *
 * @author    Joshua Kuklinski <kuklinskij1@southernct.edu>
 * @copyright 2026
 * @date      2026-10-27
 * @version   1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // set to 1 to display errors, 0 to hide them

  /* Quick Paths */
  /* note the 2 after __FILE__, because it's 2 directories deep */
  include_once (realpath(dirname(__FILE__, 2).'/php/session.php'));
  include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));

  /* Check Role */
  include_once (ROOT_SRC_PATH .'/check_admin.php');

  /* Page Name */
  $page_name = "accounts";
  $page_title = "Manage Accounts";

  /* Token so the suspend/reactivate buttons only work from this page */
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
  }

  /* All accounts, newest first */
  $accounts = $db_connection->query(
    "SELECT
        u.user_id,
        c.first_name,
        c.last_name,
        c.email,
        cr.username,
        r.role_type,
        u.creation_date,
        u.is_suspended
    FROM Users u
    INNER JOIN Contacts c ON u.contact_id = c.contact_id
    INNER JOIN Credentials cr ON u.user_id = cr.user_id
    INNER JOIN Roles r ON u.role_id = r.role_id
    ORDER BY u.user_id ASC");

?>
<!doctype html>
<html lang="en">
  <head>
  <?php include_once (ROOT_PATH . '/include/head.php'); ?>
  </head>
  <body class="<?php echo $page_name; ?>">

  <?php include_once (ROOT_PATH . '/include/header.php'); ?>
    <main role="main" class="container">

      <div class="mb-4">
        <h1 class="h3">Manage Accounts</h1>
        <p class="text-body-secondary mb-0">Suspended accounts can't log in, and anyone already logged in is signed out on their next page load.</p>
      </div>

      <?php include_once (ROOT_PATH . '/php/error_rprt.php'); ?>

      <div class="app-card p-3 table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Name</th>
              <th scope="col">Username</th>
              <th scope="col">Email</th>
              <th scope="col">Role</th>
              <th scope="col">Created</th>
              <th scope="col">Status</th>
              <th scope="col" class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($row = $accounts->fetch_assoc()) {
            $is_self = ($row['user_id'] == $_SESSION['user_id']);
            $full_name = $row['first_name'] . ' ' . $row['last_name'];
          ?>
            <tr>
              <th scope="row"><?php echo $row['user_id']; ?></th>
              <td><?php echo htmlspecialchars($full_name); ?><?php if ($is_self) { echo ' <span class="text-body-secondary">(you)</span>'; } ?></td>
              <td><?php echo htmlspecialchars($row['username']); ?></td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
              <td class="text-capitalize"><?php echo htmlspecialchars($row['role_type']); ?></td>
              <td><?php echo $row['creation_date'] ? date('M j, Y', strtotime($row['creation_date'])) : '&mdash;'; ?></td>
              <td>
                <?php if ($row['is_suspended']) { ?>
                  <span class="badge text-bg-danger">Suspended</span>
                <?php } else { ?>
                  <span class="badge text-bg-success">Active</span>
                <?php } ?>
              </td>
              <td class="text-end">
                <?php if ($is_self) { /* Admins can't lock themselves out */ ?>
                  <span class="text-body-secondary small">&mdash;</span>
                <?php } else { ?>
                  <form action="<?php echo SRC_PATH; ?>/update_account_status.php" method="post" class="d-inline"
                        onsubmit="return confirm('<?php echo $row['is_suspended'] ? 'Reactivate' : 'Suspend'; ?> <?php echo htmlspecialchars(addslashes($row['username'])); ?>?');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                    <?php if ($row['is_suspended']) { ?>
                      <input type="hidden" name="action" value="reactivate">
                      <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-unlock"></i> Reactivate</button>
                    <?php } else { ?>
                      <input type="hidden" name="action" value="suspend">
                      <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i> Suspend</button>
                    <?php } ?>
                  </form>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>

    </main>
    <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
<?php $db_connection->close(); ?>
