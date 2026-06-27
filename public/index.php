<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Suppress PHP 8.5+ deprecation notices (PDO::MYSQL_ATTR_SSL_CA and similar)
// to prevent them from breaking HTML output (e.g. Filament admin panel)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
