<?php
/**
 * logout.php allows a user to logout of the website, ensuring
 * they can no longer access the main site unless they login again
 */

session_start();
$_SESSION = []; // empty out everything we saved

session_destroy();

header('Location: login.php');

exit;

?>