<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ProductRepository;
use App\Repositories\StockRepository;
use App\Services\InventoryService;
use Exception;

class InventoryController extends Controller
{
    public function index(): void
    {
        $products = ProductRepository::getAll(null, null, true);

        $this->view('inventory.index', [
            'pageTitle' => 'Kelola Stok Produk',
            'products' => $products
        ]);
    }

    public function stockIn(): void
    {
        $this->validateCsrf();

        $itemId = (int)$this->getPost('item_id');
        $qty = (float)$this->getPost('qty');
        $cost = (float)$this->getPost('cost', 0);
        $note = (string)$this->getPost('note');

        try {
            InventoryService::stockIn('PRODUCT', $itemId, $qty, $cost, $note, auth_id());
            $this->flash('success', 'Stok produk berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/inventory');
    }

    public function stockOut(): void
    {
        $this->validateCsrf();

        $itemId = (int)$this->getPost('item_id');
        $qty = (float)$this->getPost('qty');
        $reason = (string)$this->getPost('reason');

        try {
            InventoryService::stockOut('PRODUCT', $itemId, $qty, $reason, auth_id());
            $this->flash('success', 'Stok keluar/pengurangan berhasil dicatat.');
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/inventory');
    }

    public function resetStock(): void
    {
        $this->validateCsrf();

        $productId = (int)$this->getPost('product_id');
        $reason = (string)$this->getPost('reason', 'Hapus / Kosongkan Stok Produk');

        try {
            InventoryService::resetStock($productId, $reason, auth_id());
            $this->flash('success', 'Stok produk berhasil dikosongkan/dihapus menjadi 0 pcs.');
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/inventory');
    }

    public function adjust(): void
    {
        $this->validateCsrf();

        $itemId = (int)$this->getPost('item_id');
        $physicalStock = (float)$this->getPost('physical_stock');
        $reason = (string)$this->getPost('reason');

        try {
            InventoryService::adjustStock('PRODUCT', $itemId, $physicalStock, $reason, auth_id());
            $this->flash('success', 'Penyesuaian stok produk berhasil disimpan.');
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/inventory');
    }
}
