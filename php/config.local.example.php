<?php
//======================================================================
// LOCAL SETTINGS TEMPLATE
// Copy this file to php/config.local.php and fill in your own values.
// config.local.php is in .gitignore, so your password stays off GitHub.
// Without config.local.php the app uses the XAMPP defaults below.
//======================================================================

DEFINE('DB_HOST', "localhost");
DEFINE('DB_USER', "root");
DEFINE('DB_PASSWORD', ""); // your MySQL root password, if you set one
DEFINE('DB_NAME', "login_db");
DEFINE('SALT', "authentication");
