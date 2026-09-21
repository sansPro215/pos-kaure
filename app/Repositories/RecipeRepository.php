<?php

namespace App\Repositories;

use App\Core\Database;

class RecipeRepository
{
    public static function getByProductId(int $productId): array
    {
        $sql = "SELECT pr.*, i.name as ingredient_name, i.unit, i.average_cost, i.current_stock 
                FROM product_recipes pr 
                JOIN ingredients i ON pr.ingredient_id = i.id 
                WHERE pr.product_id = ? AND i.deleted_at IS NULL 
                ORDER BY i.name ASC";
        return Database::fetchAll($sql, [$productId]);
    }

    public static function calculateRecipeHpp(int $productId): float
    {
        $items = self::getByProductId($productId);
        $totalHpp = 0.0;
        foreach ($items as $item) {
            $totalHpp += (float)$item['quantity'] * (float)$item['average_cost'];
        }
        return round($totalHpp, 2);
    }

    public static function syncRecipe(int $productId, array $ingredientsWithQty): void
    {
        Database::execute("DELETE FROM product_recipes WHERE product_id = ?", [$productId]);

        $sql = "INSERT INTO product_recipes (product_id, ingredient_id, quantity) VALUES (?, ?, ?)";
        foreach ($ingredientsWithQty as $row) {
            $ingredientId = (int)$row['ingredient_id'];
            $qty = (float)$row['quantity'];
            if ($ingredientId > 0 && $qty > 0) {
                Database::execute($sql, [$productId, $ingredientId, $qty]);
            }
        }
    }
}
