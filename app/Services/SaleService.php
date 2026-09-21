<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\TransactionRepository;
use App\Repositories\ProductRepository;
use App\Repositories\AuditRepository;
use Exception;

class SaleService
{
    public static function checkout(
        array $cart,
        array $paymentData,
        ?array $discountData = null,
        int $cashierId = 1,
        ?string $transactionCode = null
    ): array {
        if (empty($cart)) {
            throw new Exception("Keranjang belanja masih kosong.");
        }

        Database::beginTransaction();

        try {
            $subtotal = 0.0;
            $totalCogs = 0.0;
            $validatedItems = [];

            // 1. Concurrency Check & Row Locking (FOR UPDATE)
            foreach ($cart as $item) {
                $productId = (int)$item['product_id'];
                $qty = (int)$item['qty'];
                if ($qty <= 0) {
                    throw new Exception("Jumlah item tidak valid.");
                }

                $product = ProductRepository::findByIdForUpdate($productId);
                if (!$product || $product['status'] !== 'ACTIVE') {
                    throw new Exception("Produk " . ($item['name'] ?? 'ID #' . $productId) . " tidak ditemukan atau sudah nonaktif.");
                }

                $sellingPrice = (float)$product['selling_price'];
                $itemSubtotal = $sellingPrice * $qty;
                $subtotal += $itemSubtotal;

                // HPP dihitung langsung dari modal (cost_price) dikali qty (tanpa sistem stok)
                $itemCogs = (float)$product['cost_price'] * $qty;
                $totalCogs += $itemCogs;

                $validatedItems[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'selling_price' => $sellingPrice,
                    'cost_price' => (float)$product['cost_price'],
                    'subtotal' => $itemSubtotal,
                    'hpp' => $itemCogs,
                ];
            }

            // 2. Calculate Discount
            $discountType = $discountData['type'] ?? 'NONE';
            $discountValue = (float)($discountData['value'] ?? 0.0);
            $discountAmount = 0.0;

            if ($discountType === 'PERCENT') {
                $discountAmount = ($subtotal * $discountValue) / 100;
            } elseif ($discountType === 'FIXED') {
                $discountAmount = $discountValue;
            }

            $discountAmount = min($subtotal, max(0.0, $discountAmount));
            $grandTotal = max(0.0, $subtotal - $discountAmount);

            // 3. Validate Payment
            $method = strtoupper($paymentData['method'] ?? 'CASH');
            $receivedAmount = (float)($paymentData['received_amount'] ?? $grandTotal);
            $changeAmount = 0.0;

            if ($method === 'CASH') {
                if ($receivedAmount < $grandTotal) {
                    throw new Exception("Nominal uang tunai diterima kurang dari total belanja.");
                }
                $changeAmount = $receivedAmount - $grandTotal;
            } else {
                $receivedAmount = $grandTotal;
                $changeAmount = 0.0;
            }

            // 4. Generate Code & Insert Transaction
            $code = $transactionCode ?: TransactionRepository::generateTransactionCode();

            $transId = TransactionRepository::create([
                'transaction_code' => $code,
                'cashier_id' => $cashierId,
                'transaction_date' => date('Y-m-d H:i:s'),
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'discount_by' => ($discountAmount > 0) ? $cashierId : null,
                'grand_total' => $grandTotal,
                'total_cogs' => $totalCogs,
                'payment_method' => $method,
                'payment_proof' => $paymentData['payment_proof'] ?? null,
                'paid_amount' => $receivedAmount,
                'change_amount' => $changeAmount,
                'status' => 'PAID',
            ]);

            // 5. Insert Items & Deduct Stock
            foreach ($validatedItems as $v) {
                $prod = $v['product'];
                $qty = $v['qty'];

                TransactionRepository::addItem($transId, [
                    'product_id' => (int)$prod['id'],
                    'product_name' => $prod['name'],
                    'selling_price' => $v['selling_price'],
                    'cost_price' => $v['cost_price'],
                    'qty' => $qty,
                    'subtotal' => $v['subtotal'],
                    'hpp' => $v['hpp']
                ]);

            }

            // 6. Insert Payment Record
            TransactionRepository::addPayment($transId, [
                'payment_method' => $method,
                'provider' => $paymentData['provider'] ?? null,
                'amount' => $grandTotal,
                'reference_number' => $paymentData['reference_number'] ?? null,
                'payment_proof' => $paymentData['payment_proof'] ?? null,
                'received_amount' => $receivedAmount,
                'change_amount' => $changeAmount,
                'paid_at' => date('Y-m-d H:i:s'),
                'created_by' => $cashierId
            ]);

            // 7. Audit Log
            AuditRepository::log(
                $cashierId,
                'CREATE_TRANSACTION',
                'POS',
                'TRANSACTION',
                $transId,
                null,
                [
                    'code' => $code,
                    'grand_total' => $grandTotal,
                    'payment_method' => $method,
                    'item_count' => count($validatedItems)
                ]
            );

            Database::commit();

            return [
                'status' => true,
                'transaction_id' => $transId,
                'transaction_code' => $code,
                'grand_total' => $grandTotal,
                'change_amount' => $changeAmount
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function voidTransaction(int $transactionId, string $reason, int $userId): bool
    {
        Database::beginTransaction();

        try {
            $trans = TransactionRepository::findById($transactionId);
            if (!$trans) {
                throw new Exception("Transaksi tidak ditemukan.");
            }
            if ($trans['status'] !== 'PAID') {
                throw new Exception("Transaksi dengan status {$trans['status']} tidak dapat di-void.");
            }



            TransactionRepository::updateStatus($transactionId, 'VOID', $reason, $userId);

            AuditRepository::log(
                $userId,
                'VOID_TRANSACTION',
                'TRANSACTION',
                'TRANSACTION',
                $transactionId,
                ['status' => 'PAID'],
                ['status' => 'VOID', 'reason' => $reason]
            );

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function refundTransaction(
        int $transactionId,
        array $refundItems, // [itemId => refundQty]
        float $refundAmount,
        string $reason,
        bool $returnStock,
        int $userId
    ): bool {
        Database::beginTransaction();

        try {
            $trans = TransactionRepository::findById($transactionId);
            if (!$trans) {
                throw new Exception("Transaksi tidak ditemukan.");
            }
            if ($trans['status'] !== 'PAID' && $trans['status'] !== 'PARTIAL_REFUND') {
                throw new Exception("Transaksi tidak dapat direfund.");
            }

            $items = TransactionRepository::getItems($transactionId);
            $totalRemainingItems = 0;

            foreach ($items as $item) {
                $itemId = (int)$item['id'];
                $refundQty = (int)($refundItems[$itemId] ?? 0);

                if ($refundQty > 0) {
                    $availableToRefund = (int)$item['qty'] - (int)$item['refunded_qty'];
                    if ($refundQty > $availableToRefund) {
                        throw new Exception("Jumlah refund untuk {$item['product_name']} melebihi sisa pembelian.");
                    }

                    $newRefundedQty = (int)$item['refunded_qty'] + $refundQty;
                    Database::execute(
                        "UPDATE transaction_items SET refunded_qty = ? WHERE id = ?",
                        [$newRefundedQty, $itemId]
                    );


                }

                $remaining = (int)$item['qty'] - ((int)$item['refunded_qty'] + $refundQty);
                if ($remaining > 0) {
                    $totalRemainingItems++;
                }
            }

            $newStatus = ($totalRemainingItems === 0) ? 'REFUNDED' : 'PARTIAL_REFUND';
            TransactionRepository::updateStatus($transactionId, $newStatus, $reason, $userId);

            AuditRepository::log(
                $userId,
                'REFUND_TRANSACTION',
                'TRANSACTION',
                'TRANSACTION',
                $transactionId,
                ['status' => $trans['status']],
                ['status' => $newStatus, 'amount' => $refundAmount, 'reason' => $reason]
            );

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function holdCart(array $cart, ?string $note, int $cashierId): array
    {
        if (empty($cart)) {
            throw new Exception("Keranjang masih kosong.");
        }

        $code = TransactionRepository::generateTransactionCode();
        $subtotal = 0.0;
        foreach ($cart as $item) {
            $subtotal += (float)$item['price'] * (int)$item['qty'];
        }

        Database::beginTransaction();
        try {
            $transId = TransactionRepository::create([
                'transaction_code' => $code,
                'cashier_id' => $cashierId,
                'transaction_date' => date('Y-m-d H:i:s'),
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
                'status' => 'HELD',
                'hold_note' => $note ?: 'Held order'
            ]);

            foreach ($cart as $item) {
                TransactionRepository::addItem($transId, [
                    'product_id' => (int)$item['product_id'],
                    'product_name' => $item['name'],
                    'selling_price' => (float)$item['price'],
                    'qty' => (int)$item['qty'],
                    'subtotal' => (float)$item['price'] * (int)$item['qty']
                ]);
            }

            Database::commit();
            return ['status' => true, 'id' => $transId, 'code' => $code];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function updateFullTransaction(int $transactionId, array $data, int $editorId): array
    {
        Database::beginTransaction();

        try {
            $oldTrans = TransactionRepository::findById($transactionId);
            if (!$oldTrans) {
                throw new Exception("Transaksi #{$transactionId} tidak ditemukan.");
            }

            $oldItems = TransactionRepository::getItems($transactionId);
            $oldPayments = TransactionRepository::getPayments($transactionId);

            $oldSnapshot = [
                'transaction' => $oldTrans,
                'items' => $oldItems,
                'payments' => $oldPayments
            ];

            // 1. Process items
            $rawItems = $data['items'] ?? [];
            if (empty($rawItems)) {
                throw new Exception("Transaksi harus memiliki minimal 1 item penjualan.");
            }

            $subtotal = 0.0;
            $totalCogs = 0.0;
            $newItemsToInsert = [];

            foreach ($rawItems as $rawItem) {
                $qty = max(1, (int)($rawItem['qty'] ?? 1));
                $sellingPrice = (float)($rawItem['selling_price'] ?? 0);
                $costPrice = (float)($rawItem['cost_price'] ?? 0);
                $productId = !empty($rawItem['product_id']) ? (int)$rawItem['product_id'] : null;
                $productName = trim((string)($rawItem['product_name'] ?? 'Item'));

                if ($productId) {
                    $prod = ProductRepository::findById($productId);
                    if ($prod) {
                        if ($costPrice <= 0) {
                            $costPrice = (float)$prod['cost_price'];
                        }
                        if (empty($productName)) {
                            $productName = $prod['name'];
                        }
                    }
                }

                $itemSubtotal = $sellingPrice * $qty;
                $itemHpp = $costPrice * $qty;

                $subtotal += $itemSubtotal;
                $totalCogs += $itemHpp;

                $newItemsToInsert[] = [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'selling_price' => $sellingPrice,
                    'cost_price' => $costPrice,
                    'qty' => $qty,
                    'subtotal' => $itemSubtotal,
                    'hpp' => $itemHpp
                ];
            }



            // 3. Discount & Financial calculation
            $discountType = (string)($data['discount_type'] ?? 'NONE');
            $discountValue = (float)($data['discount_value'] ?? 0);
            $discountAmount = 0.0;

            if ($discountType === 'PERCENT') {
                $discountAmount = round(($subtotal * $discountValue) / 100, 2);
            } elseif ($discountType === 'FIXED') {
                $discountAmount = min($subtotal, $discountValue);
            }

            $grandTotal = max(0, $subtotal - $discountAmount);
            $grossProfit = $grandTotal - $totalCogs;

            $paidAmount = isset($data['paid_amount']) ? (float)$data['paid_amount'] : $grandTotal;
            $changeAmount = $paidAmount - $grandTotal;
            $paymentMethod = (string)($data['payment_method'] ?? $oldTrans['payment_method']);

            $transDate = !empty($data['transaction_date']) ? $data['transaction_date'] : $oldTrans['transaction_date'];
            $cashierId = !empty($data['cashier_id']) ? (int)$data['cashier_id'] : (int)$oldTrans['cashier_id'];
            $status = !empty($data['status']) ? (string)$data['status'] : $oldTrans['status'];
            $holdNote = array_key_exists('hold_note', $data) ? $data['hold_note'] : $oldTrans['hold_note'];

            // Payment proof handling
            $paymentProof = array_key_exists('payment_proof', $data) 
                ? $data['payment_proof'] 
                : ($oldTrans['payment_proof'] ?? null);
            if (!empty($data['delete_payment_proof'])) {
                $paymentProof = null;
            }

            // 4. Update transactions table
            TransactionRepository::update($transactionId, [
                'transaction_date' => $transDate,
                'created_at' => $transDate,
                'cashier_id' => $cashierId,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'grand_total' => $grandTotal,
                'total_cogs' => $totalCogs,
                'payment_method' => $paymentMethod,
                'payment_proof' => $paymentProof,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'status' => $status,
                'hold_note' => $holdNote
            ]);

            // 5. Replace items
            TransactionRepository::deleteItems($transactionId);
            foreach ($newItemsToInsert as $itemToInsert) {
                TransactionRepository::addItem($transactionId, $itemToInsert);
            }

            // 6. Sync Payment
            TransactionRepository::syncPayment($transactionId, [
                'payment_method' => $paymentMethod,
                'provider' => $data['provider'] ?? ($oldPayments[0]['provider'] ?? null),
                'amount' => $grandTotal,
                'reference_number' => $data['reference_number'] ?? ($oldPayments[0]['reference_number'] ?? null),
                'payment_proof' => $paymentProof,
                'received_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'paid_at' => $transDate,
                'created_by' => $cashierId
            ]);

            // 7. Audit log
            $newTrans = TransactionRepository::findById($transactionId);
            $newItems = TransactionRepository::getItems($transactionId);
            $newPayments = TransactionRepository::getPayments($transactionId);

            $newSnapshot = [
                'transaction' => $newTrans,
                'items' => $newItems,
                'payments' => $newPayments,
                'edit_reason' => $data['edit_reason'] ?? 'Koreksi transaksi oleh owner'
            ];

            AuditRepository::log(
                $editorId,
                'UPDATE_TRANSACTION',
                'TRANSACTION',
                'TRANSACTION',
                $transactionId,
                $oldSnapshot,
                $newSnapshot
            );

            Database::commit();

            return [
                'status' => true,
                'transaction_id' => $transactionId,
                'grand_total' => $grandTotal,
                'subtotal' => $subtotal
            ];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
