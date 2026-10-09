<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Services\ShopApiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, ShopApiService $shopApi): View
    {
        $page = (int) $request->query('page', 1);
        $categorySlug = $request->query('category');

        $result = $shopApi->getProducts($page, 12, $categorySlug);
        $rawCategories = $shopApi->getCategories();
        $categoryTree = $shopApi->buildCategoryTree($rawCategories);

        // Определяем текущую категорию для подсветки
        $currentCategorySlug = $categorySlug;

        return view('catalog.index', [
            'products'           => $result['data'] ?? [],
            'meta'               => $result['meta'] ?? [],
            'categoryTree'       => $categoryTree,
            'currentCategory'    => $currentCategorySlug,
        ]);
    }

    public function show(int|string $id, ShopApiService $shopApi)
    {
        $product = $shopApi->getProduct($id);

        if (!$product) {
            abort(404, 'Товар не найден');
        }

        return view('catalog.show', [
            'product' => $product,
        ]);
    }
}