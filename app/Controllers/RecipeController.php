<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ProductRepository;
use App\Repositories\IngredientRepository;
use App\Repositories\RecipeRepository;
use App\Repositories\AuditRepository;

class RecipeController extends Controller
{
    public function index(): void
    {
        $products = ProductRepository::getAll(null, null, false);
        $recipeProducts = array_filter($products, fn($p) => $p['stock_tracking_type'] === 'RECIPE');

        foreach ($recipeProducts as &$rp) {
            $rp['recipes'] = RecipeRepository::getByProductId((int)$rp['id']);
            $rp['calculated_hpp'] = RecipeRepository::calculateRecipeHpp((int)$rp['id']);
        }
        unset($rp);

        $this->view('recipes.index', [
            'pageTitle' => 'Resep Produk & BOM',
            'products' => $recipeProducts
        ]);
    }

    public function edit(string $id): void
    {
        $productId = (int)$id;
        $product = ProductRepository::findById($productId);
        if (!$product) {
            $this->flash('danger', 'Produk tidak ditemukan.');
            $this->redirect('/recipes');
        }

        $currentRecipes = RecipeRepository::getByProductId($productId);
        $allIngredients = IngredientRepository::getAll(true);
        $calculatedHpp = RecipeRepository::calculateRecipeHpp($productId);

        $this->view('recipes.edit', [
            'pageTitle' => 'Atur Resep BOM: ' . $product['name'],
            'product' => $product,
            'currentRecipes' => $currentRecipes,
            'ingredients' => $allIngredients,
            'calculatedHpp' => $calculatedHpp
        ]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $productId = (int)$id;

        $product = ProductRepository::findById($productId);
        if (!$product) {
            $this->flash('danger', 'Produk tidak ditemukan.');
            $this->redirect('/recipes');
        }

        $ingredientIds = (array)($this->getPost('ingredient_id') ?: []);
        $quantities = (array)($this->getPost('quantity') ?: []);

        $items = [];
        for ($i = 0; $i < count($ingredientIds); $i++) {
            $ingId = (int)($ingredientIds[$i] ?? 0);
            $qty = (float)($quantities[$i] ?? 0);
            if ($ingId > 0 && $qty > 0) {
                $items[] = [
                    'ingredient_id' => $ingId,
                    'quantity' => $qty
                ];
            }
        }

        RecipeRepository::syncRecipe($productId, $items);

        // Update product cost_price snapshot based on new calculated HPP
        $newHpp = RecipeRepository::calculateRecipeHpp($productId);
        ProductRepository::updateCostPrice($productId, $newHpp);

        AuditRepository::log(
            auth_id(),
            'UPDATE_RECIPE',
            'RECIPE',
            'PRODUCT',
            $productId,
            null,
            ['item_count' => count($items), 'new_calculated_hpp' => $newHpp]
        );

        $this->flash('success', "Resep untuk '{$product['name']}' berhasil disimpan. Estimasi HPP: Rp" . number_format($newHpp, 0, ',', '.'));
        $this->redirect('/recipes');
    }
}
