<?php
//======================================================================
// Suspend or Reactivate an Account (admin only)
//======================================================================

include_once (realpath(dirname(__FILE__).'/session.php'));

$accounts_page = BASE_URL . "/admin/accounts.php";

/* Only admins, only by POST from the accounts page */
if ($user_role != 1) {
  $_SESSION['message'] = "Access Denied";
  header("location: " . SRC_PATH . "/logout.php");
  exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_POST['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
  $_SESSION['error'] = "That request could not be verified. Please try again.";
  header("location: " . $accounts_page);
  exit();
}

$target_id = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!in_array($action, array('suspend', 'reactivate'))) {
  $_SESSION['error'] = "Unknown action.";
  header("location: " . $accounts_page);
  exit();
}
if ($target_id == $_SESSION['user_id']) {
  $_SESSION['error'] = "You can't suspend your own account.";
  header("location: " . $accounts_page);
  exit();
}

$is_suspended = ($action == 'suspend') ? 1 : 0;
$update = $db_connection->prepare("UPDATE Users SET is_suspended = ? WHERE user_id = ?");
$update->bind_param("ii", $is_suspended, $target_id);
$update->execute();

if ($update->affected_rows == 1) {
  $_SESSION['success'] = ($action == 'suspend') ? "Account suspended." : "Account reactivated.";
} else {
  $_SESSION['error'] = "No change made. The account may not exist or already has that status.";
}
$update->close();
$db_connection->close();

header("location: " . $accounts_page);
exit();

?>
