<?php

declare(strict_types=1);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Load App Config
$appConfig = require __DIR__ . '/../config/app.php';

// Error reporting based on environment
if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../storage/logs/error.log');
    error_reporting(0);
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Autoloader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load all helper files
$helperFiles = glob(__DIR__ . '/../app/Helpers/*.php');
foreach ($helperFiles as $helperFile) {
    require_once $helperFile;
}

// Load routes
require_once __DIR__ . '/../routes/web.php';

// Run router
\App\Core\Router::resolve();
