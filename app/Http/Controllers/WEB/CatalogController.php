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
        $categories = $shopApi->getCategories();

        return view('catalog.index', [
            'products'   => $result['data'] ?? [],
            'meta'       => $result['meta'] ?? [],
            'categories' => $categories,
            'currentCategory' => $categorySlug,
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
