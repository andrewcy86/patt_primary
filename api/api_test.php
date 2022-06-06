<?php
$host = env('STAGE_DB_HOST'); /* Host name */
$user = env('STAGE_DB_USER'); /* User */
$password = env('STAGE_DB_PASS'); /* Password */
$dbname = env('STAGE_DB_NAME'); /* Database name */

echo $host.'<br />';
echo $user.'<br />';
echo $password.'<br />';
echo $dbname.'<br />';