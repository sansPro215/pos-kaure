<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\ProductRepository;
use App\Repositories\IngredientRepository;
use App\Repositories\StockRepository;
use App\Repositories\AuditRepository;
use Exception;

class InventoryService
{
    public static function stockIn(
        string $itemType, // 'PRODUCT' | 'INGREDIENT'
        int $itemId,
        float $incomingQty,
        float $incomingCost,
        ?string $note = null,
        ?int $userId = null
    ): bool {
        if ($incomingQty <= 0) {
            throw new Exception("Jumlah stok masuk harus lebih dari 0.");
        }

        Database::beginTransaction();

        try {
            if ($itemType === 'PRODUCT') {
                $product = ProductRepository::findByIdForUpdate($itemId);
                if (!$product) throw new Exception("Produk tidak ditemukan.");

                $stockBefore = (float)$product['stock'];
                $stockAfter = $stockBefore + $incomingQty;

                // Update product stock and optionally cost_price
                ProductRepository::updateStock($itemId, (int)$stockAfter);
                if ($incomingCost > 0) {
                    ProductRepository::updateCostPrice($itemId, $incomingCost);
                }
                Database::execute("UPDATE products SET stock_tracking_type = 'DIRECT' WHERE id = ? AND stock_tracking_type = 'NONE'", [$itemId]);

                StockRepository::recordMovement(
                    'STOCK_IN',
                    null,
                    'PRODUCT',
                    $itemId,
                    'IN',
                    $incomingQty,
                    $stockBefore,
                    $stockAfter,
                    'pcs',
                    $note ?: "Stok masuk manual produk",
                    $userId
                );

                AuditRepository::log(
                    $userId,
                    'STOCK_IN',
                    'INVENTORY',
                    'PRODUCT',
                    $itemId,
                    ['stock' => $stockBefore],
                    ['stock' => $stockAfter, 'incoming_qty' => $incomingQty, 'cost' => $incomingCost]
                );
            } elseif ($itemType === 'INGREDIENT') {
                $ingredient = IngredientRepository::findByIdForUpdate($itemId);
                if (!$ingredient) throw new Exception("Bahan baku tidak ditemukan.");

                $oldQty = (float)$ingredient['current_stock'];
                $oldAvgCost = (float)$ingredient['average_cost'];
                $stockAfter = $oldQty + $incomingQty;

                // Weighted Average Cost Formula:
                // new_avg_cost = (old_qty * old_avg_cost + incoming_qty * incoming_cost) / (old_qty + incoming_qty)
                if ($stockAfter > 0 && ($oldQty > 0 || $incomingCost > 0)) {
                    $totalOldValue = max(0, $oldQty) * $oldAvgCost;
                    $totalIncomingValue = $incomingQty * $incomingCost;
                    $newAvgCost = ($totalOldValue + $totalIncomingValue) / $stockAfter;
                } else {
                    $newAvgCost = $incomingCost > 0 ? $incomingCost : $oldAvgCost;
                }

                IngredientRepository::updateStockAndCost($itemId, $stockAfter, round($newAvgCost, 4));

                StockRepository::recordMovement(
                    'STOCK_IN',
                    null,
                    'INGREDIENT',
                    $itemId,
                    'IN',
                    $incomingQty,
                    $oldQty,
                    $stockAfter,
                    $ingredient['unit'],
                    $note ?: "Stok masuk bahan baku (WAC: Rp" . number_format($newAvgCost, 2) . ")",
                    $userId
                );

                AuditRepository::log(
                    $userId,
                    'STOCK_IN',
                    'INVENTORY',
                    'INGREDIENT',
                    $itemId,
                    ['stock' => $oldQty, 'average_cost' => $oldAvgCost],
                    ['stock' => $stockAfter, 'incoming_qty' => $incomingQty, 'new_average_cost' => $newAvgCost]
                );
            }

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function stockOut(
        string $itemType,
        int $itemId,
        float $outQty,
        string $reason,
        ?int $userId = null
    ): bool {
        if ($outQty <= 0) {
            throw new Exception("Jumlah stok keluar harus lebih dari 0.");
        }

        Database::beginTransaction();

        try {
            if ($itemType === 'PRODUCT') {
                $product = ProductRepository::findByIdForUpdate($itemId);
                if (!$product) throw new Exception("Produk tidak ditemukan.");

                $stockBefore = (float)$product['stock'];
                if ($stockBefore < $outQty) {
                    throw new Exception("Stok tidak mencukupi untuk pengurangan.");
                }
                $stockAfter = $stockBefore - $outQty;

                ProductRepository::updateStock($itemId, (int)$stockAfter);
                Database::execute("UPDATE products SET stock_tracking_type = 'DIRECT' WHERE id = ? AND stock_tracking_type = 'NONE'", [$itemId]);

                StockRepository::recordMovement(
                    'STOCK_OUT',
                    null,
                    'PRODUCT',
                    $itemId,
                    'OUT',
                    -$outQty,
                    $stockBefore,
                    $stockAfter,
                    'pcs',
                    $reason,
                    $userId
                );
            } elseif ($itemType === 'INGREDIENT') {
                $ingredient = IngredientRepository::findByIdForUpdate($itemId);
                if (!$ingredient) throw new Exception("Bahan baku tidak ditemukan.");

                $stockBefore = (float)$ingredient['current_stock'];
                if ($stockBefore < $outQty) {
                    throw new Exception("Stok bahan tidak mencukupi untuk pengurangan.");
                }
                $stockAfter = $stockBefore - $outQty;

                IngredientRepository::updateStock($itemId, $stockAfter);

                StockRepository::recordMovement(
                    'STOCK_OUT',
                    null,
                    'INGREDIENT',
                    $itemId,
                    'OUT',
                    -$outQty,
                    $stockBefore,
                    $stockAfter,
                    $ingredient['unit'],
                    $reason,
                    $userId
                );
            }

            AuditRepository::log(
                $userId,
                'STOCK_OUT',
                'INVENTORY',
                $itemType,
                $itemId,
                ['qty_out' => $outQty, 'reason' => $reason]
            );

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function adjustStock(
        string $itemType,
        int $itemId,
        float $physicalStock,
        string $reason,
        ?int $userId = null
    ): bool {
        if ($physicalStock < 0) {
            throw new Exception("Stok fisik tidak boleh bernilai negatif.");
        }

        Database::beginTransaction();

        try {
            if ($itemType === 'PRODUCT') {
                $product = ProductRepository::findByIdForUpdate($itemId);
                if (!$product) throw new Exception("Produk tidak ditemukan.");

                $stockBefore = (float)$product['stock'];
                $diff = $physicalStock - $stockBefore;
                $stockAfter = $physicalStock;

                ProductRepository::updateStock($itemId, (int)$stockAfter);
                Database::execute("UPDATE products SET stock_tracking_type = 'DIRECT' WHERE id = ? AND stock_tracking_type = 'NONE'", [$itemId]);

                StockRepository::recordMovement(
                    'ADJUSTMENT',
                    null,
                    'PRODUCT',
                    $itemId,
                    'ADJUSTMENT',
                    $diff,
                    $stockBefore,
                    $stockAfter,
                    'pcs',
                    "Koreksi stok: {$reason} (Selisih: {$diff})",
                    $userId
                );
            } elseif ($itemType === 'INGREDIENT') {
                $ingredient = IngredientRepository::findByIdForUpdate($itemId);
                if (!$ingredient) throw new Exception("Bahan baku tidak ditemukan.");

                $stockBefore = (float)$ingredient['current_stock'];
                $diff = $physicalStock - $stockBefore;
                $stockAfter = $physicalStock;

                IngredientRepository::updateStock($itemId, $stockAfter);

                StockRepository::recordMovement(
                    'ADJUSTMENT',
                    null,
                    'INGREDIENT',
                    $itemId,
                    'ADJUSTMENT',
                    $diff,
                    $stockBefore,
                    $stockAfter,
                    $ingredient['unit'],
                    "Koreksi stok: {$reason} (Selisih: {$diff})",
                    $userId
                );
            }

            AuditRepository::log(
                $userId,
                'STOCK_ADJUSTMENT',
                'INVENTORY',
                $itemType,
                $itemId,
                ['stock_before' => $stockBefore],
                ['stock_after' => $stockAfter, 'reason' => $reason]
            );

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function resetStock(
        int $productId,
        string $reason = 'Hapus / Kosongkan Stok Produk',
        ?int $userId = null
    ): bool {
        Database::beginTransaction();

        try {
            $product = ProductRepository::findByIdForUpdate($productId);
            if (!$product) throw new Exception("Produk tidak ditemukan.");

            $stockBefore = (float)$product['stock'];
            $diff = -$stockBefore;
            $stockAfter = 0;

            ProductRepository::updateStock($productId, 0);
            Database::execute("UPDATE products SET stock_tracking_type = 'DIRECT' WHERE id = ? AND stock_tracking_type = 'NONE'", [$productId]);

            if ($stockBefore != 0) {
                StockRepository::recordMovement(
                    'STOCK_RESET',
                    null,
                    'PRODUCT',
                    $productId,
                    'OUT',
                    $diff,
                    $stockBefore,
                    $stockAfter,
                    'pcs',
                    $reason ?: 'Hapus / Kosongkan Stok Produk ke 0',
                    $userId
                );

                AuditRepository::log(
                    $userId,
                    'STOCK_RESET',
                    'INVENTORY',
                    'PRODUCT',
                    $productId,
                    ['stock_before' => $stockBefore],
                    ['stock_after' => 0, 'reason' => $reason]
                );
            }

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
