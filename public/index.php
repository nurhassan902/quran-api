<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance
if (file_exists($maintenance = __DIR__.'/quran_api/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoload
require __DIR__.'/quran_api/vendor/autoload.php';

// Bootstrap
$app = require_once __DIR__.'/quran_api/bootstrap/app.php';

// Run
$app->handleRequest(Request::capture());