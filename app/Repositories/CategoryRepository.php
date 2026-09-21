<?php

namespace App\Repositories;

use App\Core\Database;

class CategoryRepository
{
    public static function getAll(bool $activeOnly = false): array
    {
        $sql = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.deleted_at IS NULL) as product_count 
                FROM categories c 
                WHERE c.deleted_at IS NULL";
        if ($activeOnly) {
            $sql .= " AND c.status = 'ACTIVE'";
        }
        $sql .= " ORDER BY c.created_at DESC, c.id DESC";
        return Database::fetchAll($sql);
    }

    public static function findById(int $id): ?array
    {
        return Database::fetch("SELECT * FROM categories WHERE id = ? AND deleted_at IS NULL LIMIT 1", [$id]);
    }

    public static function create(string $name, string $status = 'ACTIVE'): int
    {
        Database::execute("INSERT INTO categories (name, status) VALUES (?, ?)", [$name, $status]);
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, string $name, string $status): bool
    {
        return Database::execute("UPDATE categories SET name = ?, status = ? WHERE id = ?", [$name, $status, $id]);
    }

    public static function softDelete(int $id): bool
    {
        return Database::execute("UPDATE categories SET deleted_at = NOW(), status = 'INACTIVE' WHERE id = ?", [$id]);
    }
}
