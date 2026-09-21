<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\SettingRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\UserRepository;
use App\Services\SaleService;
use Exception;

class PosController extends Controller
{
    public function index(): void
    {
        // Role CASHIER must clock in before using POS module
        if (!is_owner()) {
            $todayAttendance = AttendanceRepository::getToday(auth_id());
            if (empty($todayAttendance) || empty($todayAttendance['clock_in'])) {
                $settings = SettingRepository::get();
                $this->view('pos.attendance_required', [
                    'pageTitle' => 'Absen Masuk Diperlukan — POS Kasir',
                    'settings' => $settings
                ], 'pos_layout');
                return;
            }

            // Peringatan jika kasir sudah absen pulang hari ini
            if (!empty($todayAttendance['clock_out']) || ($todayAttendance['status'] ?? '') === 'SELESAI') {
                $settings = SettingRepository::get();
                $this->view('pos.shift_completed', [
                    'pageTitle' => 'Shift Selesai — Akun Sudah Absen Pulang',
                    'settings' => $settings,
                    'todayAttendance' => $todayAttendance
                ], 'pos_layout');
                return;
            }
        }

        $categories = CategoryRepository::getAll(true);
        $products = ProductRepository::getAll(null, null, true);
        $settings = SettingRepository::get();
        $heldList = TransactionRepository::getHeldTransactions();

        // Pass cashiers list specifically for Owner role (only CASHIER role, Owner excluded)
        $cashiers = [];
        if (is_owner()) {
            $allUsers = UserRepository::getAll();
            $cashiers = array_values(array_filter($allUsers, function ($u) {
                return $u['role'] === 'CASHIER' && strtoupper((string)($u['status'] ?? 'ACTIVE')) === 'ACTIVE';
            }));
            if (empty($cashiers)) {
                $cashiers = array_values(array_filter($allUsers, function ($u) {
                    return $u['role'] === 'CASHIER';
                }));
            }
        }


        $this->view('pos.index', [
            'pageTitle' => 'Point of Sale (POS)',
            'categories' => $categories,
            'products' => $products,
            'settings' => $settings,
            'heldList' => $heldList,
            'cashiers' => $cashiers
        ], 'pos_layout');
    }

    public function checkout(): void
    {
        $this->validateCsrf();

        // Guard: Cashier must clock in and must not have clocked out
        if (!is_owner()) {
            $todayAttendance = AttendanceRepository::getToday(auth_id());
            if (empty($todayAttendance) || empty($todayAttendance['clock_in'])) {
                $this->json(['status' => false, 'message' => 'Akses checkout terkunci. Anda harus melakukan Absen Masuk terlebih dahulu.'], 403);
                return;
            }
            if (!empty($todayAttendance['clock_out']) || ($todayAttendance['status'] ?? '') === 'SELESAI') {
                $this->json(['status' => false, 'message' => 'Akun anda sudah absen pulang, tidak bisa melakukan transaksi. Jika ada kesalahan silahkan hubungi owner.'], 403);
                return;
            }
        }

        $cart = json_decode((string)$this->getPost('cart_json'), true);
        if (empty($cart)) {
            $this->json(['status' => false, 'message' => 'Keranjang masih kosong.'], 400);
        }

        $paymentMethod = (string)$this->getPost('payment_method', 'CASH');
        $provider = (string)$this->getPost('payment_provider');
        $receivedAmount = (float)$this->getPost('received_amount', 0);
        $referenceNumber = (string)$this->getPost('reference_number');

        $discountType = (string)$this->getPost('discount_type', 'NONE');
        $discountValue = (float)$this->getPost('discount_value', 0);

        $cashierId = auth_id() ?: 1;
        // Role OWNER must transact on behalf of a CASHIER
        if (is_owner()) {
            $selectedId = (int)$this->getPost('selected_cashier_id');
            if ($selectedId > 0) {
                $targetUser = UserRepository::findById($selectedId);
                if ($targetUser && $targetUser['role'] === 'CASHIER') {
                    $cashierId = $selectedId;
                }
            }
            // If still Owner, fallback to the first active cashier in database
            if ($cashierId === (int)auth_id()) {
                $allUsers = UserRepository::getAll();
                foreach ($allUsers as $u) {
                    if ($u['role'] === 'CASHIER' && strtoupper((string)($u['status'] ?? 'ACTIVE')) === 'ACTIVE') {
                        $cashierId = (int)$u['id'];
                        break;
                    }
                }
                if ($cashierId === (int)auth_id()) {
                    foreach ($allUsers as $u) {
                        if ($u['role'] === 'CASHIER') {
                            $cashierId = (int)$u['id'];
                            break;
                        }
                    }
                }
            }
        }

        // Handle optional payment proof photo for cashless transactions
        $paymentProofFilename = null;
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
            }
        }

        try {
            $result = SaleService::checkout(
                $cart,
                [
                    'method' => $paymentMethod,
                    'provider' => $provider,
                    'received_amount' => $receivedAmount,
                    'reference_number' => $referenceNumber,
                    'payment_proof' => $paymentProofFilename
                ],
                [
                    'type' => $discountType,
                    'value' => $discountValue
                ],
                $cashierId
            );

            $this->json([
                'status' => true,
                'message' => 'Transaksi berhasil!',
                'transaction_id' => $result['transaction_id'],
                'transaction_code' => $result['transaction_code'],
                'grand_total' => $result['grand_total'],
                'change_amount' => $result['change_amount'],
                'receipt_url' => url('/transactions/' . $result['transaction_id'] . '/receipt')
            ]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function hold(): void
    {
        $this->validateCsrf();

        // Guard: Cashier must clock in and must not have clocked out
        if (!is_owner()) {
            $todayAttendance = AttendanceRepository::getToday(auth_id());
            if (empty($todayAttendance) || empty($todayAttendance['clock_in'])) {
                $this->json(['status' => false, 'message' => 'Akses terkunci. Anda harus melakukan Absen Masuk terlebih dahulu.'], 403);
                return;
            }
            if (!empty($todayAttendance['clock_out']) || ($todayAttendance['status'] ?? '') === 'SELESAI') {
                $this->json(['status' => false, 'message' => 'Akun anda sudah absen pulang, tidak bisa melakukan transaksi. Jika ada kesalahan silahkan hubungi owner.'], 403);
                return;
            }
        }

        $cart = json_decode((string)$this->getPost('cart_json'), true);
        $note = (string)$this->getPost('hold_note');
        $cashierId = auth_id() ?: 1;

        if (is_owner()) {
            $selectedId = (int)$this->getPost('selected_cashier_id');
            if ($selectedId > 0) {
                $targetUser = UserRepository::findById($selectedId);
                if ($targetUser && $targetUser['role'] === 'CASHIER') {
                    $cashierId = $selectedId;
                }
            }
            if ($cashierId === (int)auth_id()) {
                $allUsers = UserRepository::getAll();
                foreach ($allUsers as $u) {
                    if ($u['role'] === 'CASHIER' && strtoupper((string)($u['status'] ?? 'ACTIVE')) === 'ACTIVE') {
                        $cashierId = (int)$u['id'];
                        break;
                    }
                }
                if ($cashierId === (int)auth_id()) {
                    foreach ($allUsers as $u) {
                        if ($u['role'] === 'CASHIER') {
                            $cashierId = (int)$u['id'];
                            break;
                        }
                    }
                }
            }
        }

        try {
            $res = SaleService::holdCart($cart, $note, $cashierId);
            $this->json(['status' => true, 'message' => 'Pesanan berhasil disimpan (Hold).', 'code' => $res['code']]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function resume(string $id): void
    {
        $transId = (int)$id;
        $trans = TransactionRepository::findById($transId);
        if (!$trans || $trans['status'] !== 'HELD') {
            $this->json(['status' => false, 'message' => 'Data pesanan yang di-hold tidak ditemukan.'], 404);
        }

        $items = TransactionRepository::getItems($transId);
        $cart = [];
        foreach ($items as $item) {
            $cart[] = [
                'product_id' => (int)$item['product_id'],
                'name' => $item['product_name'],
                'price' => (float)$item['selling_price'],
                'qty' => (int)$item['qty']
            ];
        }

        // Delete the held transaction now that it is resumed into active cart
        TransactionRepository::deleteItems($transId);
        TransactionRepository::updateStatus($transId, 'VOID', 'Resumed to cart', auth_id());

        $this->json([
            'status' => true,
            'cart' => $cart,
            'note' => $trans['hold_note']
        ]);
    }

    public function cancelHold(string $id): void
    {
        $this->validateCsrf();
        $transId = (int)$id;
        TransactionRepository::updateStatus($transId, 'VOID', 'Cancelled hold', auth_id());
        $this->json(['status' => true, 'message' => 'Pesanan held berhasil dibatalkan.']);
    }

    public function receipt(string $id): void
    {
        $transId = (int)$id;
        $trans = TransactionRepository::findById($transId);
        if (!$trans) {
            $this->flash('danger', 'Transaksi tidak ditemukan.');
            $this->redirect('/pos');
        }

        $items = TransactionRepository::getItems($transId);
        $payments = TransactionRepository::getPayments($transId);
        $settings = SettingRepository::get();

        // Check if this is a reprint (already viewed before or older transaction)
        $isReprint = isset($_GET['reprint']) || (time() - strtotime($trans['created_at']) > 300);

        $this->view('pos.receipt', [
            'pageTitle' => 'Struk #' . $trans['transaction_code'],
            'transaction' => $trans,
            'items' => $items,
            'payments' => $payments,
            'settings' => $settings,
            'isReprint' => $isReprint
        ], null); // Render without standard layout for clean thermal printing
    }
}
