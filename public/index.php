<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance
<<<<<<< HEAD
if (file_exists($maintenance = __DIR__.'/quran_api/storage/framework/maintenance.php')) {
=======
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
>>>>>>> fe43a2e (first commit)
    require $maintenance;
}

// Autoload
<<<<<<< HEAD
require __DIR__.'/quran_api/vendor/autoload.php';

// Bootstrap
$app = require_once __DIR__.'/quran_api/bootstrap/app.php';

// Run
$app->handleRequest(Request::capture());
=======
require __DIR__.'/../vendor/autoload.php';

// Bootstrap
$app = require_once __DIR__.'/../bootstrap/app.php';

// Run Laravel properly
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
>>>>>>> fe43a2e (first commit)
