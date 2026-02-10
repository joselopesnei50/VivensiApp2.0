<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// --- ASAAS INTERCEPTOR START ---
// Force 200 OK for Asaas Webhooks to prevent penalties, then process in background.
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/webhooks/asaas') !== false) {
    // 1. Send Success Response Immediately
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'received_early_ack']);
    
    // 2. Close Connection (if PHP-FPM)
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        // Fallback for Apache/Other
        ob_start();
        echo json_encode(['status' => 'received_early_ack']);
        $size = ob_get_length();
        header("Content-Length: $size");
        header('Connection: close');
        ob_end_flush();
        ob_flush();
        flush();
    }
    
    // 3. Simple Log to confirm we hit this code
    try {
        file_put_contents(__DIR__.'/../storage/logs/asaas_interceptor.log', date('Y-m-d H:i:s') . " - Intercepted request.\n", FILE_APPEND);
    } catch (Exception $e) {}
}
// --- ASAAS INTERCEPTOR END ---

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
