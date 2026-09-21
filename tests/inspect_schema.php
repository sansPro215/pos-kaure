<?php
require_once __DIR__ . '/../config/app.php';
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});
foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $f) require_once $f;

use App\Core\Database;

$t = Database::fetchAll('DESCRIBE transactions');
echo "TRANSACTIONS:\n";
foreach ($t as $c) echo "- " . $c['Field'] . " (" . $c['Type'] . ")\n";

$p = Database::fetchAll('DESCRIBE payments');
echo "\nPAYMENTS:\n";
foreach ($p as $c) echo "- " . $c['Field'] . " (" . $c['Type'] . ")\n";
