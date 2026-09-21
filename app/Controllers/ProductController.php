<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\AuditRepository;

class ProductController extends Controller
{
    public function index(): void
    {
        $categoryId = $this->getQuery('category_id') ? (int)$this->getQuery('category_id') : null;
        $search = $this->getQuery('q');
        
        $products = ProductRepository::getAll($categoryId, $search);
        $categories = CategoryRepository::getAll(true);

        $this->view('products.index', [
            'pageTitle' => 'Manajemen Produk',
            'products' => $products,
            'categories' => $categories,
            'currentCat' => $categoryId,
            'search' => $search
        ]);
    }

    public function create(): void
    {
        $categories = CategoryRepository::getAll(true);
        foreach ($categories as &$c) {
            $c['suggested_sku'] = ProductRepository::generateSku((int)$c['id']);
        }
        unset($c);

        $suggestedSku = !empty($categories) ? $categories[0]['suggested_sku'] : ProductRepository::generateSku();

        $this->view('products.create', [
            'pageTitle' => 'Tambah Produk Baru',
            'categories' => $categories,
            'suggestedSku' => $suggestedSku
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();

        $categoryId = (int)$this->getPost('category_id');
        $name = trim((string)$this->getPost('name'));
        $sellingPrice = (float)$this->getPost('selling_price');
        $costPrice = (float)$this->getPost('cost_price', 0);

        $sku = trim((string)$this->getPost('sku'));
        if (empty($sku)) {
            $sku = ProductRepository::generateSku($categoryId);
        }

        if (empty($name) || $sellingPrice <= 0) {
            $this->flash('danger', 'Nama Produk dan Harga Jual wajib diisi dengan benar.');
            $this->redirect('/products/create');
        }

        if (!ProductRepository::isSkuUnique($sku)) {
            // If already taken, automatically make it unique
            $sku = ProductRepository::generateSku($categoryId) . '-' . rand(10, 99);
        }

        // Handle image upload
        $imageName = $this->handleImageUpload();

        $productId = ProductRepository::create([
            'category_id' => $categoryId,
            'sku' => $sku,
            'name' => $name,
            'image' => $imageName,
            'selling_price' => $sellingPrice,
            'cost_price' => $costPrice,
            'status' => 'ACTIVE'
        ]);

        AuditRepository::log(auth_id(), 'CREATE_PRODUCT', 'PRODUCT', 'PRODUCT', $productId, null, [
            'sku' => $sku,
            'name' => $name,
            'selling_price' => $sellingPrice
        ]);

        $this->flash('success', "Produk '{$name}' berhasil ditambahkan.");
        $this->redirect('/products');
    }

    public function edit(string $id): void
    {
        $productId = (int)$id;
        $product = ProductRepository::findById($productId);
        if (!$product) {
            $this->flash('danger', 'Produk tidak ditemukan.');
            $this->redirect('/products');
        }

        $categories = CategoryRepository::getAll(true);
        $this->view('products.edit', [
            'pageTitle' => 'Edit Produk #' . $product['sku'],
            'product' => $product,
            'categories' => $categories
        ]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $productId = (int)$id;

        $product = ProductRepository::findById($productId);
        if (!$product) {
            $this->flash('danger', 'Produk tidak ditemukan.');
            $this->redirect('/products');
        }

        $categoryId = (int)$this->getPost('category_id');
        $name = trim((string)$this->getPost('name'));
        $sellingPrice = (float)$this->getPost('selling_price');
        $costPrice = (float)$this->getPost('cost_price', 0);

        $sku = trim((string)$this->getPost('sku'));
        if (empty($sku)) {
            $sku = ProductRepository::generateSku($categoryId, $productId);
        }

        if (empty($name) || $sellingPrice <= 0) {
            $this->flash('danger', 'Nama Produk dan Harga Jual wajib diisi dengan benar.');
            $this->redirect('/products/' . $productId . '/edit');
        }

        if (!ProductRepository::isSkuUnique($sku, $productId)) {
            $this->flash('danger', "SKU '{$sku}' sudah digunakan oleh produk lain.");
            $this->redirect('/products/' . $productId . '/edit');
        }

        $updateData = [
            'category_id' => $categoryId,
            'sku' => $sku,
            'name' => $name,
            'selling_price' => $sellingPrice,
            'cost_price' => $costPrice,
            'status' => 'ACTIVE'
        ];

        // Handle image upload if new photo provided
        $newImage = $this->handleImageUpload();
        if ($newImage !== null) {
            $updateData['image'] = $newImage;
        }

        ProductRepository::update($productId, $updateData);

        AuditRepository::log(auth_id(), 'UPDATE_PRODUCT', 'PRODUCT', 'PRODUCT', $productId, $product, $updateData);

        $this->flash('success', "Produk '{$name}' berhasil diperbarui.");
        $this->redirect('/products');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $productId = (int)$id;

        $product = ProductRepository::findById($productId);
        if ($product) {
            ProductRepository::delete($productId);
            AuditRepository::log(auth_id(), 'DELETE_PRODUCT', 'PRODUCT', 'PRODUCT', $productId, $product, null);
            $this->flash('success', "Produk '{$product['name']}' berhasil dihapus.");
        }

        $this->redirect('/products');
    }

    private function handleImageUpload(): ?string
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['image'];
        $appConfig = require __DIR__ . '/../../config/app.php';

        // Check size (2MB)
        if ($file['size'] > $appConfig['max_upload_size']) {
            throw new \Exception("Ukuran gambar terlalu besar. Maksimal 2 MB.");
        }

        // Check mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $appConfig['allowed_image_mimes'], true)) {
            throw new \Exception("Format gambar tidak valid. Gunakan JPG, PNG, atau WEBP.");
        }

        // Extension check
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $appConfig['allowed_image_exts'], true)) {
            throw new \Exception("Ekstensi file tidak diizinkan.");
        }

        // Random safe filename
        $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destDir = __DIR__ . '/../../public/uploads/products';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $randomName)) {
            throw new \Exception("Gagal menyimpan file gambar.");
        }

        return $randomName;
    }
}
