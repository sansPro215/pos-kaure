<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\SettingRepository;
use App\Services\SaleService;
use App\Repositories\ProductRepository;
use App\Helpers\XlsxWriter;
use Exception;

class TransactionController extends Controller
{
    public function index(): void
    {
        $startDate = $this->getQuery('start_date');
        $endDate = $this->getQuery('end_date');
        $cashierId = $this->getQuery('cashier_id') ? (int)$this->getQuery('cashier_id') : null;
        $status = $this->getQuery('status');
        $paymentMethod = $this->getQuery('payment_method');
        $search = $this->getQuery('q');

        // Cashiers can only view their own transactions unless owner
        if (is_cashier()) {
            $cashierId = auth_id();
        }

        $transactions = TransactionRepository::getTransactions(
            $startDate,
            $endDate,
            $cashierId,
            $status,
            $paymentMethod,
            $search,
            200
        );

        $cashiers = UserRepository::getAll();

        $this->view('transactions.index', [
            'pageTitle' => 'Riwayat Transaksi',
            'transactions' => $transactions,
            'cashiers' => $cashiers,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'cashierId' => $cashierId,
            'status' => $status,
            'paymentMethod' => $paymentMethod,
            'search' => $search
        ]);
    }

    public function exportXlsx(): void
    {
        $startDate = $this->getQuery('start_date');
        $endDate = $this->getQuery('end_date');
        $cashierId = $this->getQuery('cashier_id') ? (int)$this->getQuery('cashier_id') : null;
        $status = $this->getQuery('status');
        $paymentMethod = $this->getQuery('payment_method');
        $search = $this->getQuery('q');

        if (is_cashier()) {
            $cashierId = auth_id();
        }

        $transactions = TransactionRepository::getTransactions(
            $startDate,
            $endDate,
            $cashierId,
            $status,
            $paymentMethod,
            $search,
            10000
        );

        // Resolve human-readable filter labels
        $cashierLabel = 'Semua Kasir';
        if ($cashierId) {
            $user = UserRepository::findById($cashierId);
            if ($user) {
                $cashierLabel = $user['name'];
            }
        }

        $periodLabel = (!empty($startDate) ? date('d/m/Y', strtotime($startDate)) : 'Awal') . ' s/d ' . (!empty($endDate) ? date('d/m/Y', strtotime($endDate)) : date('d/m/Y'));
        $statusLabel = !empty($status) ? $status : 'Semua Status';
        $methodLabel = !empty($paymentMethod) ? $paymentMethod : 'Semua Metode';

        $xlsx = new XlsxWriter('Laporan Transaksi');
        // 17 Columns with tailored widths
        $xlsx->setColumnWidths([6, 22, 20, 18, 16, 16, 14, 16, 18, 16, 18, 16, 16, 14, 24, 18, 48]);
        // Freeze header pane at row 6
        $xlsx->setFreezePanes(6);

        // Calculate Totals & Stats
        $sumSubtotal = 0;
        $sumDiscount = 0;
        $sumGrandTotal = 0;
        $sumCogs = 0;
        $sumGrossProfit = 0;
        $sumPaid = 0;
        $sumChange = 0;

        foreach ($transactions as $t) {
            $sub = (float)($t['subtotal'] ?? 0);
            $disc = (float)($t['discount_amount'] ?? 0);
            $grand = (float)($t['grand_total'] ?? 0);
            $cogs = (float)($t['total_cogs'] ?? 0);
            $gross = $grand - $cogs;
            $paid = (float)($t['paid_amount'] ?? 0);
            $change = (float)($t['change_amount'] ?? 0);

            $sumSubtotal += $sub;
            $sumDiscount += $disc;
            $sumGrandTotal += $grand;
            $sumCogs += $cogs;
            $sumGrossProfit += $gross;
            $sumPaid += $paid;
            $sumChange += $change;
        }

        // ROW 1: Banner Judul (14pt Bold Navy)
        $shopTitle = strtoupper(shop_name()) . ' — LAPORAN TRANSAKSI PENJUALAN';
        $xlsx->addRow([
            ['v' => $shopTitle, 's' => 1]
        ], 28);
        $xlsx->addMerge('A1:Q1');

        // ROW 2: Filter Parameters
        $filterDesc = 'Periode: ' . $periodLabel . '   |   Kasir: ' . $cashierLabel . '   |   Status: ' . $statusLabel . '   |   Metode: ' . $methodLabel . '   |   Pencarian: ' . ($search ?: '-');
        $xlsx->addRow([
            ['v' => $filterDesc, 's' => 2]
        ], 18);
        $xlsx->addMerge('A2:Q2');

        // ROW 3: Export Metadata
        $curUser = auth_user();
        $metaDesc = 'Waktu Ekspor: ' . date('d/m/Y H:i:s') . ' WIB   |   Dicetak oleh: ' . ($curUser['name'] ?? 'Sistem') . '   |   Total Data: ' . count($transactions) . ' Transaksi';
        $xlsx->addRow([
            ['v' => $metaDesc, 's' => 2]
        ], 18);
        $xlsx->addMerge('A3:Q3');

        // ROW 4: KPI Summary Cards (Row height 24)
        $xlsx->addRow([
            ['v' => ' Total Omset Penjualan:', 's' => 19],
            ['v' => (int)$sumGrandTotal, 's' => 20, 't' => 'n'],
            ['v' => '', 's' => 0],
            ['v' => ' Total HPP Modal:', 's' => 19],
            ['v' => (int)$sumCogs, 's' => 20, 't' => 'n'],
            ['v' => '', 's' => 0],
            ['v' => ' Estimasi Laba Kotor:', 's' => 19],
            ['v' => (int)$sumGrossProfit, 's' => 21, 't' => 'n'],
            ['v' => '', 's' => 0],
            ['v' => ' Total Transaksi:', 's' => 19],
            ['v' => count($transactions), 's' => 22, 't' => 'n']
        ], 24);

        // ROW 5: Spacing Row
        $xlsx->addRow([], 10);

        // ROW 6: Table Headers (Dark Fill, White Text, Height 26)
        $xlsx->addRow([
            ['v' => 'No.', 's' => 4],
            ['v' => 'No. Transaksi', 's' => 4],
            ['v' => 'Tanggal & Waktu', 's' => 4],
            ['v' => 'Kasir', 's' => 3],
            ['v' => 'Metode Bayar', 's' => 4],
            ['v' => 'Subtotal', 's' => 5],
            ['v' => 'Tipe Diskon', 's' => 4],
            ['v' => 'Diskon (Rp)', 's' => 5],
            ['v' => 'Grand Total', 's' => 5],
            ['v' => 'Total HPP', 's' => 5],
            ['v' => 'Estimasi Laba', 's' => 5],
            ['v' => 'Jumlah Bayar', 's' => 5],
            ['v' => 'Kembalian', 's' => 5],
            ['v' => 'Status', 's' => 4],
            ['v' => 'Bukti Bayar Non-Tunai', 's' => 3],
            ['v' => 'Catatan / Meja', 's' => 3],
            ['v' => 'Rincian Menu Produk', 's' => 3]
        ], 26);
        $xlsx->setAutoFilter('A6:Q6');

        // ROW 7..N: Transaction Rows
        $no = 1;
        foreach ($transactions as $t) {
            $isZebra = ($no % 2 === 0);
            $textStyle = $isZebra ? 10 : 6;
            $centerStyle = $isZebra ? 11 : 7;
            $currencyStyle = $isZebra ? 12 : 8;

            $items = TransactionRepository::getItems((int)$t['id']);
            $itemDetails = [];
            foreach ($items as $item) {
                $itemDetails[] = $item['product_name'] . ' (' . (int)$item['qty'] . 'x @' . number_format((float)$item['selling_price'], 0, ',', '.') . ')';
            }
            $itemStr = !empty($itemDetails) ? implode(', ', $itemDetails) : '-';

            $cogs = (float)($t['total_cogs'] ?? 0);
            $grand = (float)($t['grand_total'] ?? 0);
            $grossProfit = $grand - $cogs;

            $proofStr = '-';
            if (!empty($t['payment_proof'])) {
                $proofStr = url('/uploads/payments/' . $t['payment_proof']);
            }

            $statusText = strtoupper((string)($t['status'] ?? 'PAID'));
            $statusStyle = $centerStyle;
            if ($statusText === 'PAID') {
                $statusStyle = $isZebra ? 23 : 17;
            } elseif (in_array($statusText, ['CANCELLED', 'REFUND', 'VOID'], true)) {
                $statusStyle = $isZebra ? 24 : 18;
            }

            $xlsx->addRow([
                ['v' => $no, 's' => $centerStyle, 't' => 'n'],
                ['v' => $t['transaction_code'], 's' => $centerStyle],
                ['v' => date('d/m/Y H:i', strtotime($t['transaction_date'])), 's' => $centerStyle],
                ['v' => $t['cashier_name'] ?? 'Kasir', 's' => $textStyle],
                ['v' => $t['payment_method'], 's' => $centerStyle],
                ['v' => (int)$t['subtotal'], 's' => $currencyStyle, 't' => 'n'],
                ['v' => $t['discount_type'] ?: '-', 's' => $centerStyle],
                ['v' => (int)$t['discount_amount'], 's' => $currencyStyle, 't' => 'n'],
                ['v' => (int)$grand, 's' => $currencyStyle, 't' => 'n'],
                ['v' => (int)$cogs, 's' => $currencyStyle, 't' => 'n'],
                ['v' => (int)$grossProfit, 's' => $currencyStyle, 't' => 'n'],
                ['v' => (int)$t['paid_amount'], 's' => $currencyStyle, 't' => 'n'],
                ['v' => (int)$t['change_amount'], 's' => $currencyStyle, 't' => 'n'],
                ['v' => $statusText, 's' => $statusStyle],
                ['v' => $proofStr, 's' => $textStyle],
                ['v' => $t['hold_note'] ?? '-', 's' => $textStyle],
                ['v' => $itemStr, 's' => $textStyle]
            ], 22);

            $no++;
        }

        // Summary Row (TOTAL KESELURUHAN)
        $xlsx->addRow([
            ['v' => '', 's' => 13],
            ['v' => '', 's' => 13],
            ['v' => '', 's' => 13],
            ['v' => '', 's' => 13],
            ['v' => 'TOTAL KESELURUHAN', 's' => 13],
            ['v' => (int)$sumSubtotal, 's' => 15, 't' => 'n'],
            ['v' => '', 's' => 14],
            ['v' => (int)$sumDiscount, 's' => 15, 't' => 'n'],
            ['v' => (int)$sumGrandTotal, 's' => 15, 't' => 'n'],
            ['v' => (int)$sumCogs, 's' => 15, 't' => 'n'],
            ['v' => (int)$sumGrossProfit, 's' => 16, 't' => 'n'],
            ['v' => (int)$sumPaid, 's' => 15, 't' => 'n'],
            ['v' => (int)$sumChange, 's' => 15, 't' => 'n'],
            ['v' => count($transactions) . ' TRX', 's' => 14],
            ['v' => '', 's' => 13],
            ['v' => '', 's' => 13],
            ['v' => '', 's' => 13]
        ], 24);

        $filename = 'laporan_transaksi_' . date('Ymd_His') . '.xlsx';
        $xlsx->download($filename);
    }

    public function exportCsv(): void
    {
        $this->exportXlsx();
    }

    public function show(string $id): void
    {
        $transId = (int)$id;
        $trans = TransactionRepository::findById($transId);
        if (!$trans) {
            $this->flash('danger', 'Transaksi tidak ditemukan.');
            $this->redirect('/transactions');
            return;
        }

        // Cashiers can only view their own transactions
        if (is_cashier() && (int)$trans['cashier_id'] !== auth_id()) {
            $this->flash('danger', 'Anda tidak memiliki akses ke transaksi ini.');
            $this->redirect('/transactions');
            return;
        }

        $items = TransactionRepository::getItems($transId);
        $payments = TransactionRepository::getPayments($transId);
        $cashiers = is_owner() ? UserRepository::getAll() : [];
        $availableProducts = is_owner() ? ProductRepository::getAll(null, null, true) : [];

        $this->view('transactions.show', [
            'pageTitle' => 'Detail Transaksi #' . $trans['transaction_code'],
            'trans' => $trans,
            'items' => $items,
            'payments' => $payments,
            'cashiers' => $cashiers,
            'availableProducts' => $availableProducts
        ]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $transId = (int)$id;

        $trans = TransactionRepository::findById($transId);
        if (!$trans) {
            $this->flash('danger', 'Transaksi tidak ditemukan.');
            $this->redirect('/transactions');
            return;
        }

        // Parse form payload
        $transactionDate = (string)$this->getPost('transaction_date');
        if (!empty($transactionDate)) {
            $transactionDate = date('Y-m-d H:i:s', strtotime($transactionDate));
        } else {
            $transactionDate = $trans['transaction_date'];
        }

        $cashierId = (int)$this->getPost('cashier_id', $trans['cashier_id']);
        $status = (string)$this->getPost('status', $trans['status']);
        $holdNote = trim((string)$this->getPost('hold_note'));
        $paymentMethod = (string)$this->getPost('payment_method', $trans['payment_method']);
        $provider = trim((string)$this->getPost('provider'));
        $referenceNumber = trim((string)$this->getPost('reference_number'));
        $paidAmount = (float)$this->getPost('paid_amount', 0);
        $discountType = (string)$this->getPost('discount_type', 'NONE');
        $discountValue = (float)$this->getPost('discount_value', 0);
        $adjustStock = (bool)$this->getPost('adjust_stock', false);
        $editReason = trim((string)$this->getPost('edit_reason'));

        // Parse items from form arrays
        $itemProductIds = (array)$this->getPost('item_product_id', []);
        $itemProductNames = (array)$this->getPost('item_product_name', []);
        $itemQtys = (array)$this->getPost('item_qty', []);
        $itemPrices = (array)$this->getPost('item_price', []);

        $items = [];
        foreach ($itemProductNames as $idx => $name) {
            $name = trim((string)$name);
            $qty = isset($itemQtys[$idx]) ? (int)$itemQtys[$idx] : 1;
            $price = isset($itemPrices[$idx]) ? (float)$itemPrices[$idx] : 0;
            $prodId = isset($itemProductIds[$idx]) && !empty($itemProductIds[$idx]) ? (int)$itemProductIds[$idx] : null;

            if (!empty($name) && $qty > 0) {
                $items[] = [
                    'product_id' => $prodId,
                    'product_name' => $name,
                    'qty' => $qty,
                    'selling_price' => $price,
                    'cost_price' => 0
                ];
            }
        }

        if (empty($items)) {
            $this->flash('danger', 'Transaksi harus memiliki minimal 1 item produk.');
            $this->redirect('/transactions/' . $transId . '?edit=1');
            return;
        }

        // Handle optional payment proof photo upload/camera for cashless
        $paymentProofFilename = null;
        $hasNewProof = false;
        if (!empty($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['payment_proof']['tmp_name'];
            $origName = $_FILES['payment_proof']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $paymentProofFilename = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetDir = __DIR__ . '/../../public/uploads/payments';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                move_uploaded_file($tmpPath, $targetDir . '/' . $paymentProofFilename);
                $hasNewProof = true;
            }
        }
        $deletePaymentProof = (bool)$this->getPost('delete_payment_proof', false);

        $updatePayload = [
            'transaction_date' => $transactionDate,
            'cashier_id' => $cashierId,
            'status' => $status,
            'hold_note' => $holdNote,
            'payment_method' => $paymentMethod,
            'provider' => $provider,
            'reference_number' => $referenceNumber,
            'paid_amount' => $paidAmount,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'adjust_stock' => $adjustStock,
            'delete_payment_proof' => $deletePaymentProof,
            'edit_reason' => $editReason ?: 'Koreksi transaksi oleh owner',
            'items' => $items
        ];

        if ($hasNewProof) {
            $updatePayload['payment_proof'] = $paymentProofFilename;
        }

        try {
            SaleService::updateFullTransaction($transId, $updatePayload, auth_id());

            $this->flash('success', "Detail transaksi #{$trans['transaction_code']} berhasil diperbarui oleh Owner.");
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal memperbarui transaksi: ' . $e->getMessage());
            $this->redirect('/transactions/' . $transId . '?edit=1');
            return;
        }

        $this->redirect('/transactions/' . $transId);
    }

    public function void(string $id): void
    {
        $this->validateCsrf();
        $transId = (int)$id;
        $reason = trim((string)$this->getPost('void_reason'));

        if (empty($reason)) {
            $this->flash('danger', 'Alasan pembatalan (void) wajib diisi.');
            $this->redirect('/transactions/' . $transId);
        }

        try {
            SaleService::voidTransaction($transId, $reason, auth_id());
            $this->flash('success', 'Transaksi berhasil dibatalkan (VOID).');
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/transactions/' . $transId);
    }

    public function refund(string $id): void
    {
        $this->validateCsrf();
        $transId = (int)$id;

        $refundItems = (array)$this->getPost('refund_qty', []);
        $refundAmount = (float)$this->getPost('refund_amount', 0);
        $reason = trim((string)$this->getPost('refund_reason'));
        $returnStock = (bool)$this->getPost('return_stock');

        if (empty($reason)) {
            $this->flash('danger', 'Alasan refund wajib diisi.');
            $this->redirect('/transactions/' . $transId);
        }

        try {
            SaleService::refundTransaction($transId, $refundItems, $refundAmount, $reason, $returnStock, auth_id());
            $this->flash('success', 'Proses refund berhasil dicatat.');
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/transactions/' . $transId);
    }
}
