<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$u = Database::fetchAll('DESCRIBE users');
echo "USERS TABLE:\n";
foreach ($u as $c) echo "- " . $c['Field'] . " (" . $c['Type'] . ")\n";

$a = Database::fetchAll('DESCRIBE audit_logs');
echo "\nAUDIT_LOGS TABLE:\n";
foreach ($a as $c) echo "- " . $c['Field'] . " (" . $c['Type'] . ")\n";
