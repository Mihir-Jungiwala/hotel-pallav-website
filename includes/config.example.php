<?php
/**
 * Central config — copy this file to config.php and fill in real values.
 * config.php is gitignored on purpose: it holds live database credentials
 * and must never be committed. Everything else in the app reads from it;
 * nothing else hardcodes credentials.
 */

// ===== Database =====
define('DB_HOST', '127.0.0.1');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

// ===== App =====
define('APP_URL', 'http://127.0.0.1:8080'); // change to your live domain, no trailing slash
define('APP_NAME', 'Hotel Pallav');
define('APP_TIMEZONE', 'Asia/Kolkata');
define('SESSION_LIFETIME_MINUTES', 30); // admin session auto-expiry

// ===== Paths =====
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('UPLOADS_URL', APP_URL . '/uploads');

date_default_timezone_set(APP_TIMEZONE);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // set to '1' temporarily if you need to debug on a live box
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/error.log');
