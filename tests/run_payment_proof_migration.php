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

// 1. Add payment_proof to transactions table if not exists
$tCols = Database::fetchAll("SHOW COLUMNS FROM transactions LIKE 'payment_proof'");
if (empty($tCols)) {
    Database::execute("ALTER TABLE transactions ADD COLUMN payment_proof VARCHAR(255) NULL AFTER payment_method");
    echo "Added payment_proof column to transactions table.\n";
} else {
    echo "payment_proof column already exists in transactions table.\n";
}

// 2. Add payment_proof to payments table if not exists
$pCols = Database::fetchAll("SHOW COLUMNS FROM payments LIKE 'payment_proof'");
if (empty($pCols)) {
    Database::execute("ALTER TABLE payments ADD COLUMN payment_proof VARCHAR(255) NULL AFTER reference_number");
    echo "Added payment_proof column to payments table.\n";
} else {
    echo "payment_proof column already exists in payments table.\n";
}

// 3. Ensure public/uploads/payments directory exists
$uploadDir = __DIR__ . '/../public/uploads/payments';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "Created directory: {$uploadDir}\n";
} else {
    echo "Directory already exists: {$uploadDir}\n";
}

echo "Migration finished successfully.\n";
