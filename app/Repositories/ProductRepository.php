<?php

namespace App\Repositories;

use App\Core\Database;

class ProductRepository
{
    public static function getAll(?int $categoryId = null, ?string $search = null, bool $activeOnly = false): array
    {
        $where = ["p.deleted_at IS NULL"];
        $params = [];

        if ($activeOnly) {
            $where[] = "p.status = 'ACTIVE'";
        }

        if (!empty($categoryId)) {
            $where[] = "p.category_id = ?";
            $params[] = $categoryId;
        }

        if (!empty($search)) {
            $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE {$whereSql} 
                ORDER BY p.created_at DESC, p.id DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? AND p.deleted_at IS NULL 
                LIMIT 1";
        return Database::fetch($sql, [$id]);
    }

    public static function findByIdForUpdate(int $id): ?array
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? AND p.deleted_at IS NULL 
                LIMIT 1 FOR UPDATE";
        return Database::fetch($sql, [$id]);
    }

    public static function findBySku(string $sku): ?array
    {
        return Database::fetch("SELECT * FROM products WHERE sku = ? AND deleted_at IS NULL LIMIT 1", [$sku]);
    }

    public static function isSkuUnique(string $sku, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $count = Database::fetchColumn(
                "SELECT COUNT(*) FROM products WHERE sku = ? AND id != ? AND deleted_at IS NULL",
                [$sku, $excludeId]
            );
        } else {
            $count = Database::fetchColumn(
                "SELECT COUNT(*) FROM products WHERE sku = ? AND deleted_at IS NULL",
                [$sku]
            );
        }
        return (int)$count === 0;
    }

    public static function generateSku(?int $categoryId = null, ?int $productId = null): string
    {
        $catName = 'UMUM';
        if (!empty($categoryId)) {
            $cat = CategoryRepository::findById($categoryId);
            if ($cat && !empty($cat['name'])) {
                $clean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $cat['name']));
                if (!empty($clean)) {
                    $catName = substr($clean, 0, 10);
                }
            }
        }

        $prefix = "PD-{$catName}-";

        // Cari seluruh nomor SKU yang sudah terpakai di kategori ini
        $where = ["deleted_at IS NULL"];
        $params = [];
        if (!empty($categoryId)) {
            $where[] = "(category_id = ? OR sku LIKE ?)";
            $params[] = $categoryId;
            $params[] = "{$prefix}%";
        } else {
            $where[] = "sku LIKE ?";
            $params[] = "{$prefix}%";
        }

        if ($productId) {
            $where[] = "id != ?";
            $params[] = $productId;
        }

        $sql = "SELECT sku FROM products WHERE " . implode(' AND ', $where);
        $rows = Database::fetchAll($sql, $params);

        $usedNumbers = [];
        foreach ($rows as $r) {
            $skuVal = (string)$r['sku'];
            // Ambil angka urutan di akhir SKU (misal: PD-KOPI-1 atau WK-KOP-01)
            if (preg_match('/-(\d+)$/', $skuVal, $m)) {
                $usedNumbers[(int)$m[1]] = true;
            }
        }

        // Cari nomor urut positif terendah (1, 2, 3...) yang tersedia / belum dipakai di kategori ini
        $seq = 1;
        while (isset($usedNumbers[$seq])) {
            $seq++;
        }

        return "{$prefix}{$seq}";
    }

    public static function getLowStock(): array
    {
        return [];
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO products 
                (category_id, sku, name, image, selling_price, cost_price, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        Database::execute($sql, [
            $data['category_id'] ?: null,
            $data['sku'],
            $data['name'],
            $data['image'] ?? null,
            $data['selling_price'] ?? 0.00,
            $data['cost_price'] ?? 0.00,
            $data['status'] ?? 'ACTIVE'
        ]);
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [
            'category_id = ?',
            'sku = ?',
            'name = ?',
            'selling_price = ?',
            'cost_price = ?',
            'status = ?'
        ];
        $params = [
            $data['category_id'] ?: null,
            $data['sku'],
            $data['name'],
            $data['selling_price'] ?? 0.00,
            $data['cost_price'] ?? 0.00,
            $data['status'] ?? 'ACTIVE'
        ];

        if (array_key_exists('image', $data)) {
            $fields[] = 'image = ?';
            $params[] = $data['image'];
        }

        $params[] = $id;
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params);
    }

    public static function updateStock(int $id, int $newStock): bool
    {
        return true;
    }

    public static function updateCostPrice(int $id, float $costPrice): bool
    {
        return Database::execute("UPDATE products SET cost_price = ? WHERE id = ?", [$costPrice, $id]);
    }

    public static function delete(int $id): bool
    {
        $product = self::findById($id);
        if ($product) {
            if (!empty($product['image'])) {
                $imagePath = __DIR__ . '/../../public/uploads/products/' . $product['image'];
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            return Database::execute("DELETE FROM products WHERE id = ?", [$id]);
        }
        return false;
    }

    public static function softDelete(int $id): bool
    {
        return self::delete($id);
    }
}
