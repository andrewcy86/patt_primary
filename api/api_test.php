<?php
use function Env\env;
require '/public/data/patt/patt-approot/vendor/autoload.php';

$root_dir = dirname(__DIR__);

$dotenv = Dotenv\Dotenv::createUnsafeImmutable($root_dir);

if (file_exists($root_dir . '/.env')) {
    $dotenv->load();
}

$host = env('STAGE_DB_HOST'); /* Host name */
$user = env('STAGE_DB_USER'); /* User */
$password = env('STAGE_DB_PASS'); /* Password */
$dbname = env('STAGE_DB_NAME'); /* Database name */

echo $host.'<br />';
echo $user.'<br />';
echo $password.'<br />';
echo $dbname.'<br />';