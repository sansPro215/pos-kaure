<?php

namespace App\Repositories;

use App\Core\Database;

class TransactionRepository
{
    public static function generateTransactionCode(): string
    {
        $todayPrefix = 'WK-' . date('Ymd') . '-';
        $sql = "SELECT transaction_code FROM transactions 
                WHERE transaction_code LIKE ? 
                ORDER BY id DESC LIMIT 1";
        $lastCode = Database::fetchColumn($sql, [$todayPrefix . '%']);

        if ($lastCode) {
            $lastNumber = (int)substr($lastCode, -4);
            $nextNumber = str_pad((string)($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $todayPrefix . $nextNumber;
    }

    public static function create(array $data): int
    {
        $sql = "INSERT INTO transactions 
                (transaction_code, cashier_id, transaction_date, subtotal, discount_type, discount_value, 
                 discount_amount, discount_by, grand_total, total_cogs, payment_method, payment_proof, paid_amount, 
                 change_amount, status, hold_note) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        Database::execute($sql, [
            $data['transaction_code'],
            $data['cashier_id'],
            $data['transaction_date'] ?? date('Y-m-d H:i:s'),
            $data['subtotal'] ?? 0.00,
            $data['discount_type'] ?? 'NONE',
            $data['discount_value'] ?? 0.00,
            $data['discount_amount'] ?? 0.00,
            $data['discount_by'] ?? null,
            $data['grand_total'] ?? 0.00,
            $data['total_cogs'] ?? 0.00,
            $data['payment_method'] ?? 'CASH',
            $data['payment_proof'] ?? null,
            $data['paid_amount'] ?? 0.00,
            $data['change_amount'] ?? 0.00,
            $data['status'] ?? 'PAID',
            $data['hold_note'] ?? null
        ]);

        return (int)Database::lastInsertId();
    }

    public static function addItem(int $transactionId, array $item): int
    {
        $sql = "INSERT INTO transaction_items 
                (transaction_id, product_id, product_name, selling_price, cost_price, qty, subtotal, hpp) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        Database::execute($sql, [
            $transactionId,
            $item['product_id'] ?? null,
            $item['product_name'],
            $item['selling_price'],
            $item['cost_price'] ?? 0.00,
            $item['qty'],
            $item['subtotal'],
            $item['hpp'] ?? 0.00
        ]);

        return (int)Database::lastInsertId();
    }

    public static function addPayment(int $transactionId, array $payment): int
    {
        $sql = "INSERT INTO payments 
                (transaction_id, payment_method, provider, amount, reference_number, payment_proof, received_amount, change_amount, paid_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        Database::execute($sql, [
            $transactionId,
            $payment['payment_method'],
            $payment['provider'] ?? null,
            $payment['amount'],
            $payment['reference_number'] ?? null,
            $payment['payment_proof'] ?? null,
            $payment['received_amount'] ?? $payment['amount'],
            $payment['change_amount'] ?? 0.00,
            $payment['paid_at'] ?? date('Y-m-d H:i:s'),
            $payment['created_by'] ?? null
        ]);

        return (int)Database::lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $sql = "SELECT t.*, u.name as cashier_name, u.username as cashier_username,
                       d.name as discount_by_name, v.name as void_by_name, r.name as refund_by_name
                FROM transactions t
                LEFT JOIN users u ON t.cashier_id = u.id
                LEFT JOIN users d ON t.discount_by = d.id
                LEFT JOIN users v ON t.void_by = v.id
                LEFT JOIN users r ON t.refund_by = r.id
                WHERE t.id = ? AND t.deleted_at IS NULL
                LIMIT 1";
        return Database::fetch($sql, [$id]);
    }

    public static function findByCode(string $code): ?array
    {
        $sql = "SELECT t.*, u.name as cashier_name, u.username as cashier_username 
                FROM transactions t 
                LEFT JOIN users u ON t.cashier_id = u.id 
                WHERE t.transaction_code = ? AND t.deleted_at IS NULL 
                LIMIT 1";
        return Database::fetch($sql, [$code]);
    }

    public static function getItems(int $transactionId): array
    {
        $sql = "SELECT ti.* 
                FROM transaction_items ti 
                WHERE ti.transaction_id = ? 
                ORDER BY ti.id ASC";
        return Database::fetchAll($sql, [$transactionId]);
    }

    public static function getPayments(int $transactionId): array
    {
        $sql = "SELECT * FROM payments WHERE transaction_id = ? ORDER BY id ASC";
        return Database::fetchAll($sql, [$transactionId]);
    }

    public static function getTransactions(
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $cashierId = null,
        ?string $status = null,
        ?string $paymentMethod = null,
        ?string $search = null,
        int $limit = 200
    ): array {
        $where = ["t.deleted_at IS NULL"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(t.transaction_date) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(t.transaction_date) <= ?";
            $params[] = $endDate;
        }
        if (!empty($cashierId)) {
            $where[] = "t.cashier_id = ?";
            $params[] = $cashierId;
        }
        if (!empty($status)) {
            $where[] = "t.status = ?";
            $params[] = $status;
        }
        if (!empty($paymentMethod)) {
            $where[] = "t.payment_method = ?";
            $params[] = $paymentMethod;
        }
        if (!empty($search)) {
            $where[] = "(t.transaction_code LIKE ? OR u.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT t.*, u.name as cashier_name 
                FROM transactions t 
                LEFT JOIN users u ON t.cashier_id = u.id 
                WHERE {$whereSql} 
                ORDER BY t.transaction_date DESC, t.id DESC 
                LIMIT ?";
        $params[] = $limit;

        return Database::fetchAll($sql, $params);
    }

    public static function getHeldTransactions(): array
    {
        $sql = "SELECT t.*, u.name as cashier_name 
                FROM transactions t 
                LEFT JOIN users u ON t.cashier_id = u.id 
                WHERE t.status = 'HELD' AND t.deleted_at IS NULL 
                ORDER BY t.transaction_date DESC, t.id DESC";
        return Database::fetchAll($sql);
    }

    public static function updateStatus(int $id, string $status, ?string $reason = null, ?int $userId = null): bool
    {
        if ($status === 'VOID') {
            $sql = "UPDATE transactions SET status = 'VOID', void_reason = ?, void_by = ?, void_at = NOW() WHERE id = ?";
            return Database::execute($sql, [$reason, $userId, $id]);
        } elseif ($status === 'REFUNDED' || $status === 'PARTIAL_REFUND') {
            $sql = "UPDATE transactions SET status = ?, refund_reason = ?, refund_by = ?, refund_at = NOW() WHERE id = ?";
            return Database::execute($sql, [$status, $reason, $userId, $id]);
        } else {
            $sql = "UPDATE transactions SET status = ? WHERE id = ?";
            return Database::execute($sql, [$status, $id]);
        }
    }

    public static function deleteItems(int $transactionId): bool
    {
        return Database::execute("DELETE FROM transaction_items WHERE transaction_id = ?", [$transactionId]);
    }

    public static function deletePayments(int $transactionId): bool
    {
        return Database::execute("DELETE FROM payments WHERE transaction_id = ?", [$transactionId]);
    }

    public static function update(int $id, array $data): bool
    {
        $sql = "UPDATE transactions SET 
                transaction_date = ?, 
                created_at = ?, 
                cashier_id = ?, 
                subtotal = ?, 
                discount_type = ?, 
                discount_value = ?, 
                discount_amount = ?, 
                grand_total = ?, 
                total_cogs = ?, 
                payment_method = ?, 
                payment_proof = ?, 
                paid_amount = ?, 
                change_amount = ?, 
                status = ?, 
                hold_note = ? 
                WHERE id = ?";

        $transDate = $data['transaction_date'] ?? date('Y-m-d H:i:s');
        $grandTotal = (float)($data['grand_total'] ?? 0.0);
        $totalCogs = (float)($data['total_cogs'] ?? 0.0);

        return Database::execute($sql, [
            $transDate,
            $data['created_at'] ?? $transDate,
            (int)($data['cashier_id'] ?? 1),
            (float)($data['subtotal'] ?? 0.0),
            $data['discount_type'] ?? 'NONE',
            (float)($data['discount_value'] ?? 0.0),
            (float)($data['discount_amount'] ?? 0.0),
            $grandTotal,
            $totalCogs,
            $data['payment_method'] ?? 'CASH',
            $data['payment_proof'] ?? null,
            (float)($data['paid_amount'] ?? 0.0),
            (float)($data['change_amount'] ?? 0.0),
            $data['status'] ?? 'PAID',
            $data['hold_note'] ?? null,
            $id
        ]);
    }

    public static function syncPayment(int $transactionId, array $paymentData): bool
    {
        $existing = Database::fetch("SELECT id FROM payments WHERE transaction_id = ? LIMIT 1", [$transactionId]);
        if ($existing) {
            $sql = "UPDATE payments SET 
                    payment_method = ?, 
                    provider = ?, 
                    amount = ?, 
                    reference_number = ?, 
                    payment_proof = ?, 
                    received_amount = ?, 
                    change_amount = ?, 
                    paid_at = ?, 
                    created_by = ? 
                    WHERE id = ?";
            return Database::execute($sql, [
                $paymentData['payment_method'] ?? 'CASH',
                $paymentData['provider'] ?? null,
                (float)($paymentData['amount'] ?? 0.0),
                $paymentData['reference_number'] ?? null,
                $paymentData['payment_proof'] ?? null,
                (float)($paymentData['received_amount'] ?? ($paymentData['amount'] ?? 0.0)),
                (float)($paymentData['change_amount'] ?? 0.0),
                $paymentData['paid_at'] ?? date('Y-m-d H:i:s'),
                !empty($paymentData['created_by']) ? (int)$paymentData['created_by'] : null,
                $existing['id']
            ]);
        } else {
            $sql = "INSERT INTO payments 
                    (transaction_id, payment_method, provider, amount, reference_number, payment_proof, received_amount, change_amount, paid_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            return Database::execute($sql, [
                $transactionId,
                $paymentData['payment_method'] ?? 'CASH',
                $paymentData['provider'] ?? null,
                (float)($paymentData['amount'] ?? 0.0),
                $paymentData['reference_number'] ?? null,
                $paymentData['payment_proof'] ?? null,
                (float)($paymentData['received_amount'] ?? ($paymentData['amount'] ?? 0.0)),
                (float)($paymentData['change_amount'] ?? 0.0),
                $paymentData['paid_at'] ?? date('Y-m-d H:i:s'),
                !empty($paymentData['created_by']) ? (int)$paymentData['created_by'] : null
            ]);
        }
    }
}
