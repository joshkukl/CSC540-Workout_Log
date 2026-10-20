<?php
/**
 * Head Include File
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026
 * @date      2026-02-24
 * @version   1.0
 */
//======================================================================
// Head Section For All Pages
// For New Developers
// https://fonts.google.com/specimen/Roboto+Flex
// Bootstrap Icons
// https://icons.getbootstrap.com/
// Bootstrap 5.3 CDN
// https://getbootstrap.com/docs/5.3/getting-started/introduction/
//======================================================================

/* Paths are needed so assets load from any folder depth (e.g. /user/) */
include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));

/* Pages can set $page_title before including this file */
$full_title = isset($page_title) ? $page_title . ' | Workout Log' : 'Workout Log';

?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="Log your workouts and track your training history">
<meta name="author" content="CSC 540 Team Project">
<link rel="icon" href="<?php echo BASE_URL; ?>/img/favicon.svg" type="image/svg+xml">

<title><?php echo htmlspecialchars($full_title); ?></title>

<?php /* Google Fonts - Roboto flex */ ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">

<?php /* Bootstrap Icons */ ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<?php /* Bootstrap imported from a CDN */ ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

<?php /* Application Custom Styles */ ?>
<link href="<?php echo BASE_URL; ?>/css/style.css" rel="stylesheet" type="text/css" />
