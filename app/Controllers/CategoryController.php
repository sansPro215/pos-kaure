<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\AuditRepository;

class CategoryController extends Controller
{
    public function index(): void
    {
        $categories = CategoryRepository::getAll();
        $this->view('categories.index', [
            'pageTitle' => 'Kategori Produk',
            'categories' => $categories
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();

        $name = trim((string)$this->getPost('name'));
        $status = (string)$this->getPost('status', 'ACTIVE');

        if (empty($name)) {
            $this->flash('danger', 'Nama kategori wajib diisi.');
            $this->redirect('/categories');
        }

        $id = CategoryRepository::create($name, $status);
        AuditRepository::log(auth_id(), 'CREATE_CATEGORY', 'CATEGORY', 'CATEGORY', $id, null, ['name' => $name]);

        $this->flash('success', 'Kategori baru berhasil ditambahkan.');
        $this->redirect('/categories');
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $catId = (int)$id;

        $name = trim((string)$this->getPost('name'));
        $status = (string)$this->getPost('status', 'ACTIVE');

        if (empty($name)) {
            $this->flash('danger', 'Nama kategori wajib diisi.');
            $this->redirect('/categories');
        }

        $old = CategoryRepository::findById($catId);
        CategoryRepository::update($catId, $name, $status);
        AuditRepository::log(auth_id(), 'UPDATE_CATEGORY', 'CATEGORY', 'CATEGORY', $catId, $old, ['name' => $name, 'status' => $status]);

        $this->flash('success', 'Kategori berhasil diperbarui.');
        $this->redirect('/categories');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $catId = (int)$id;

        $old = CategoryRepository::findById($catId);
        CategoryRepository::softDelete($catId);
        AuditRepository::log(auth_id(), 'DELETE_CATEGORY', 'CATEGORY', 'CATEGORY', $catId, $old, ['deleted_at' => date('Y-m-d H:i:s')]);

        $this->flash('success', 'Kategori berhasil dihapus.');
        $this->redirect('/categories');
    }
}
