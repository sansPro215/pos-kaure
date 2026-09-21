<?php

namespace App\Repositories;

use App\Core\Database;

class StockRepository
{
    public static function recordMovement(
        string $referenceType,
        ?int $referenceId,
        string $itemType, // 'PRODUCT' | 'INGREDIENT'
        int $itemId,
        string $movementType, // OPENING, IN, OUT, SALE, SALE_REVERSAL, ADJUSTMENT, VOID, REFUND, WASTE
        float $qty,
        float $stockBefore,
        float $stockAfter,
        string $unit = 'pcs',
        ?string $note = null,
        ?int $createdBy = null
    ): int {
        $sql = "INSERT INTO stock_movements 
                (reference_type, reference_id, item_type, item_id, movement_type, qty, stock_before, stock_after, unit, note, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        Database::execute($sql, [
            $referenceType,
            $referenceId,
            $itemType,
            $itemId,
            $movementType,
            $qty,
            $stockBefore,
            $stockAfter,
            $unit,
            $note,
            $createdBy
        ]);

        return (int)Database::lastInsertId();
    }

    public static function getMovements(
        ?string $itemType = null,
        ?int $itemId = null,
        ?string $movementType = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $limit = 200
    ): array {
        $where = [];
        $params = [];

        if (!empty($itemType)) {
            $where[] = "sm.item_type = ?";
            $params[] = $itemType;
        }
        if (!empty($itemId)) {
            $where[] = "sm.item_id = ?";
            $params[] = $itemId;
        }
        if (!empty($movementType)) {
            $where[] = "sm.movement_type = ?";
            $params[] = $movementType;
        }
        if (!empty($startDate)) {
            $where[] = "DATE(sm.created_at) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(sm.created_at) <= ?";
            $params[] = $endDate;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $sql = "SELECT sm.*, 
                       u.name as creator_name,
                       CASE 
                           WHEN sm.item_type = 'PRODUCT' THEN p.name 
                           WHEN sm.item_type = 'INGREDIENT' THEN i.name 
                           ELSE 'Unknown' 
                       END as item_name
                FROM stock_movements sm 
                LEFT JOIN users u ON sm.created_by = u.id 
                LEFT JOIN products p ON (sm.item_type = 'PRODUCT' AND sm.item_id = p.id)
                LEFT JOIN ingredients i ON (sm.item_type = 'INGREDIENT' AND sm.item_id = i.id)
                {$whereSql} 
                ORDER BY sm.created_at DESC, sm.id DESC 
                LIMIT ?";
        $params[] = $limit;

        return Database::fetchAll($sql, $params);
    }
}
