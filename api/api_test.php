<?php
$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -5))); 
include_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');

$host = 'localhost'; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

echo $user.'<br />';
echo $password.'<br />';
echo $dbname.'<br />';