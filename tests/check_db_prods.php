<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Core/Database.php';

$rows = App\Core\Database::fetchAll("SELECT id, sku, name, status, deleted_at FROM products");
echo "Total products in DB: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "#{$r['id']} [{$r['sku']}] {$r['name']} | Status: {$r['status']} | Deleted: " . ($r['deleted_at'] ?? 'NULL') . "\n";
}
