<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoload
require __DIR__.'/../vendor/autoload.php';

// Bootstrap
$app = require_once __DIR__.'/../bootstrap/app.php';

// Run Laravel properly
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);