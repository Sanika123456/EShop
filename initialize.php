<?php
$dev_data = array('id' => '-1', 'firstname' => 'Developer', 'lastname' => '', 'username' => 'dev_oretnom', 'password' => '5da283a2d990e8d8512cf967df5bc0d0', 'last_login' => '', 'date_updated' => '', 'date_added' => '');

// Dynamically determine base URL or use environment variables
if (!defined('base_url')) {
    $env_url = getenv('BASE_URL') ?: getenv('RAILWAY_STATIC_URL');
    if ($env_url) {
        if (strpos($env_url, 'http') !== 0) {
            $env_url = 'https://' . $env_url;
        }
        define('base_url', rtrim($env_url, '/') . '/');
    } else {
        $http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $dir = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', __DIR__));
        $dir = trim($dir, '/');
        define('base_url', $http . "://" . $host . "/" . ($dir ? $dir . '/' : ''));
    }
}

if (!defined('base_app')) define('base_app', str_replace('\\', '/', __DIR__) . '/');

// Database configuration supporting Environment Variables (e.g. Railway, Clever Cloud, etc.)
if (!defined('DB_SERVER')) define('DB_SERVER', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: "localhost");
if (!defined('DB_USERNAME')) define('DB_USERNAME', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: "root");
if (!defined('DB_PASSWORD')) {
    $db_pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: getenv('DB_PASSWORD');
    define('DB_PASSWORD', ($db_pass !== false && $db_pass !== null) ? $db_pass : "");
}
if (!defined('DB_NAME')) define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: "eshop_db");
if (!defined('DB_PORT')) define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: "3306");

