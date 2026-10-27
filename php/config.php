<?php
/**
 * Database Configuration File
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026 
 * @date      2026-02-24
 * @version   1.0
 */

// Author: Joshua Connor - Added new validation logic (2026-02-24)
// To use this configuration file, make sure to set the correct
// database connection parameters below.  

include_once (ROOT_PATH.'/php/path.php');

/* Important - Comment Error Reporting Section out before going live!!!  */
error_reporting(E_ALL);
ini_set('display_errors', 1); // set to 1 to display errors, 0 to hide them
// End of Error Reporting
/* --------------------------------------------------------------------- */

/* Timezone for dates and times shown in the app (XAMPP defaults to Europe/Berlin) */
date_default_timezone_set('America/New_York');

DEFINE('DB_HOST', "localhost");
DEFINE('DB_USER', "root");
/* Put your MySQL root password in php/db_local.php (not committed) if you set one */
if (file_exists(__DIR__ . '/db_local.php')) {
  include_once (__DIR__ . '/db_local.php');
}
if (!defined('DB_PASSWORD')) {
  DEFINE('DB_PASSWORD', ""); // XAMPP default: no password
}
DEFINE('DB_NAME', "login_db");
DEFINE('SALT', "authentication");

try {
  $db_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME)
    OR die("Connection failed: " . $db_connection->connect_error);
} catch (Exception $e) {
  echo 'Caught exception: ',  $e->getMessage(), "\n";
} 
  
?>