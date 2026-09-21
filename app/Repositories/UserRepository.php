<?php

namespace App\Repositories;

use App\Core\Database;

class UserRepository
{
    public static function findById(int $id): ?array
    {
        return Database::fetch("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1", [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::fetch("SELECT * FROM users WHERE username = ? AND deleted_at IS NULL LIMIT 1", [$username]);
    }

    public static function getAll(): array
    {
        return Database::fetchAll("SELECT * FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC, id DESC");
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO users (name, username, password, role, phone, hourly_rate, overtime_rate, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        Database::execute($sql, [
            $data['name'],
            $data['username'],
            $data['password'],
            $data['role'],
            $data['phone'] ?? null,
            $data['hourly_rate'] ?? 0.00,
            $data['overtime_rate'] ?? 5000.00,
            $data['status'] ?? 'ACTIVE'
        ]);
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sql = "UPDATE users SET 
                name = ?, 
                username = ?, 
                role = ?, 
                phone = ?, 
                hourly_rate = ?, 
                overtime_rate = ?, 
                status = ? 
                WHERE id = ?";
        return Database::execute($sql, [
            $data['name'],
            $data['username'],
            $data['role'],
            $data['phone'] ?? null,
            $data['hourly_rate'] ?? 0.00,
            $data['overtime_rate'] ?? 5000.00,
            $data['status'] ?? 'ACTIVE',
            $id
        ]);
    }

    public static function updatePassword(int $id, string $hashedPassword): bool
    {
        return Database::execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $id]);
    }

    public static function updateLastLogin(int $id): bool
    {
        return Database::execute("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$id]);
    }

    public static function softDelete(int $id): bool
    {
        return Database::execute("UPDATE users SET deleted_at = NOW(), status = 'INACTIVE' WHERE id = ?", [$id]);
    }

    public static function hardDelete(int $id): bool
    {
        return Database::execute("DELETE FROM users WHERE id = ?", [$id]);
    }

    public static function isUsernameUnique(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $count = Database::fetchColumn(
                "SELECT COUNT(*) FROM users WHERE username = ? AND id != ? AND deleted_at IS NULL",
                [$username, $excludeId]
            );
        } else {
            $count = Database::fetchColumn(
                "SELECT COUNT(*) FROM users WHERE username = ? AND deleted_at IS NULL",
                [$username]
            );
        }
        return (int)$count === 0;
    }
}
