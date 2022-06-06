<?php
use function Env\env;
require '/public/data/patt/patt-approot/vendor/autoload.php';

$root_dir = dirname(__DIR__);

$dotenv = Dotenv\Dotenv::createUnsafeImmutable($root_dir);

if (file_exists($root_dir . '/.env')) {
    $dotenv->load();
    $dotenv->required(['WP_HOME', 'WP_SITEURL']);
    if (!env('DATABASE_URL')) {
        $dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD']);
    }
}

$host = DATABASE_URL; /* Host name */
$user = DB_USER; /* User */
$password = DB_PASSWORD; /* Password */
$dbname = DB_NAME; /* Database name */

echo $host.'<br />';
echo $user.'<br />';
echo $password.'<br />';
echo $dbname.'<br />';