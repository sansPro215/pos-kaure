<?php

namespace App\Repositories;

use App\Core\Database;

class ExpenseRepository
{
    public static function getAll(?string $startDate = null, ?string $endDate = null, ?string $category = null, ?int $createdBy = null): array
    {
        $where = ["e.deleted_at IS NULL"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "e.date >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "e.date <= ?";
            $params[] = $endDate;
        }
        if (!empty($category)) {
            $where[] = "e.category = ?";
            $params[] = $category;
        }
        if (!empty($createdBy)) {
            $where[] = "e.created_by = ?";
            $params[] = $createdBy;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT e.*, u.name as creator_name 
                FROM operational_expenses e 
                LEFT JOIN users u ON e.created_by = u.id 
                WHERE {$whereSql} 
                ORDER BY e.date DESC, e.created_at DESC, e.id DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetch("SELECT * FROM operational_expenses WHERE id = ? AND deleted_at IS NULL LIMIT 1", [$id]);
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO operational_expenses (date, category, amount, description, receipt_image, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        Database::execute($sql, [
            $data['date'],
            $data['category'],
            $data['amount'],
            $data['description'],
            $data['receipt_image'] ?? null,
            $data['created_by'] ?? null
        ]);
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [
            'date = ?',
            'category = ?',
            'amount = ?',
            'description = ?'
        ];
        $params = [
            $data['date'],
            $data['category'],
            $data['amount'],
            $data['description']
        ];

        if (array_key_exists('receipt_image', $data)) {
            $fields[] = 'receipt_image = ?';
            $params[] = $data['receipt_image'];
        }

        $params[] = $id;
        $sql = "UPDATE operational_expenses SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params);
    }

    public static function softDelete(int $id): bool
    {
        return Database::execute("UPDATE operational_expenses SET deleted_at = NOW() WHERE id = ?", [$id]);
    }

    public static function getTotal(?string $startDate = null, ?string $endDate = null, ?int $createdBy = null): float
    {
        $where = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "date >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "date <= ?";
            $params[] = $endDate;
        }
        if (!empty($createdBy)) {
            $where[] = "created_by = ?";
            $params[] = $createdBy;
        }

        $whereSql = implode(' AND ', $where);
        $total = Database::fetchColumn("SELECT SUM(amount) FROM operational_expenses WHERE {$whereSql}", $params);
        return (float)($total ?: 0.0);
    }
}
