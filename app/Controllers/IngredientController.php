<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\IngredientRepository;
use App\Repositories\AuditRepository;

class IngredientController extends Controller
{
    public function index(): void
    {
        $ingredients = IngredientRepository::getAll();
        $this->view('ingredients.index', [
            'pageTitle' => 'Bahan Baku (Ingredients)',
            'ingredients' => $ingredients
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();

        $name = trim((string)$this->getPost('name'));
        $unit = (string)$this->getPost('unit', 'gram');
        $initialStock = (float)$this->getPost('current_stock', 0);
        $minStock = (float)$this->getPost('minimum_stock', 100);
        $averageCost = (float)$this->getPost('average_cost', 0);
        $status = (string)$this->getPost('status', 'ACTIVE');

        if (empty($name)) {
            $this->flash('danger', 'Nama bahan baku wajib diisi.');
            $this->redirect('/ingredients');
        }

        $id = IngredientRepository::create([
            'name' => $name,
            'unit' => $unit,
            'current_stock' => $initialStock,
            'minimum_stock' => $minStock,
            'average_cost' => $averageCost,
            'status' => $status
        ]);

        AuditRepository::log(auth_id(), 'CREATE_INGREDIENT', 'INGREDIENT', 'INGREDIENT', $id, null, [
            'name' => $name,
            'unit' => $unit,
            'current_stock' => $initialStock
        ]);

        $this->flash('success', "Bahan baku '{$name}' berhasil ditambahkan.");
        $this->redirect('/ingredients');
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $ingId = (int)$id;

        $name = trim((string)$this->getPost('name'));
        $unit = (string)$this->getPost('unit', 'gram');
        $minStock = (float)$this->getPost('minimum_stock', 100);
        $status = (string)$this->getPost('status', 'ACTIVE');

        if (empty($name)) {
            $this->flash('danger', 'Nama bahan baku wajib diisi.');
            $this->redirect('/ingredients');
        }

        $old = IngredientRepository::findById($ingId);
        IngredientRepository::update($ingId, [
            'name' => $name,
            'unit' => $unit,
            'minimum_stock' => $minStock,
            'status' => $status
        ]);

        AuditRepository::log(auth_id(), 'UPDATE_INGREDIENT', 'INGREDIENT', 'INGREDIENT', $ingId, $old, [
            'name' => $name,
            'unit' => $unit,
            'minimum_stock' => $minStock,
            'status' => $status
        ]);

        $this->flash('success', "Bahan baku '{$name}' berhasil diperbarui.");
        $this->redirect('/ingredients');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $ingId = (int)$id;

        $old = IngredientRepository::findById($ingId);
        if ($old) {
            IngredientRepository::softDelete($ingId);
            AuditRepository::log(auth_id(), 'DELETE_INGREDIENT', 'INGREDIENT', 'INGREDIENT', $ingId, $old, ['deleted_at' => date('Y-m-d H:i:s')]);
            $this->flash('success', "Bahan baku '{$old['name']}' berhasil dinonaktifkan.");
        }

        $this->redirect('/ingredients');
    }
}
