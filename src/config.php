<?php
/**
 * Buku Tu Internet - Configuration
 * Auto-generated for shared hosting deployment
 */

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'REPLACE_WITH_DB_USER');
define('DB_PASSWORD', 'REPLACE_WITH_DB_PASS');
define('DB_NAME', 'REPLACE_WITH_DB_NAME');

// Application URL
define('APP_URL', 'https://test.africanpishonsafaris.co.tz/');

// Timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Application stage: Live or Demo
$_app_stage = 'Live';

// Path
define('PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// DO NOT EDIT BELOW unless you know what you're doing
