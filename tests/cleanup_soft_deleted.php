<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$softDeleted = Database::fetchAll("SELECT id, sku, name FROM products WHERE deleted_at IS NOT NULL");
echo "Found " . count($softDeleted) . " soft-deleted products to purge:\n";
foreach ($softDeleted as $p) {
    echo " - #{$p['id']} [{$p['sku']}] {$p['name']}\n";
    Database::execute("DELETE FROM product_recipes WHERE product_id = ?", [$p['id']]);
    Database::execute("DELETE FROM stock_movements WHERE item_type = 'PRODUCT' AND item_id = ?", [$p['id']]);
    Database::execute("DELETE FROM products WHERE id = ?", [$p['id']]);
}

$remaining = Database::fetchColumn("SELECT COUNT(*) FROM products");
echo "Done! Total active products remaining in DB: {$remaining}\n";
