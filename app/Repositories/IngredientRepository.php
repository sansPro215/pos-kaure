<?php

namespace App\Repositories;

use App\Core\Database;

class IngredientRepository
{
    public static function getAll(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM ingredients WHERE deleted_at IS NULL";
        if ($activeOnly) {
            $sql .= " AND status = 'ACTIVE'";
        }
        $sql .= " ORDER BY created_at DESC, id DESC";
        return Database::fetchAll($sql);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetch("SELECT * FROM ingredients WHERE id = ? AND deleted_at IS NULL LIMIT 1", [$id]);
    }

    public static function findByIdForUpdate(int $id): ?array
    {
        return Database::fetch("SELECT * FROM ingredients WHERE id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE", [$id]);
    }

    public static function getLowStock(): array
    {
        $sql = "SELECT * FROM ingredients 
                WHERE deleted_at IS NULL 
                  AND status = 'ACTIVE' 
                  AND current_stock <= minimum_stock 
                ORDER BY (current_stock / minimum_stock) ASC";
        return Database::fetchAll($sql);
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO ingredients (name, unit, current_stock, minimum_stock, average_cost, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        Database::execute($sql, [
            $data['name'],
            $data['unit'] ?? 'gram',
            $data['current_stock'] ?? 0.00,
            $data['minimum_stock'] ?? 100.00,
            $data['average_cost'] ?? 0.0000,
            $data['status'] ?? 'ACTIVE'
        ]);
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sql = "UPDATE ingredients SET 
                name = ?, 
                unit = ?, 
                minimum_stock = ?, 
                status = ? 
                WHERE id = ?";
        return Database::execute($sql, [
            $data['name'],
            $data['unit'] ?? 'gram',
            $data['minimum_stock'] ?? 100.00,
            $data['status'] ?? 'ACTIVE',
            $id
        ]);
    }

    public static function updateStockAndCost(int $id, float $newStock, float $newAverageCost): bool
    {
        $sql = "UPDATE ingredients SET current_stock = ?, average_cost = ? WHERE id = ?";
        return Database::execute($sql, [$newStock, $newAverageCost, $id]);
    }

    public static function updateStock(int $id, float $newStock): bool
    {
        $sql = "UPDATE ingredients SET current_stock = ? WHERE id = ?";
        return Database::execute($sql, [$newStock, $id]);
    }

    public static function softDelete(int $id): bool
    {
        return Database::execute("UPDATE ingredients SET deleted_at = NOW(), status = 'INACTIVE' WHERE id = ?", [$id]);
    }
}
