<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Services\ShopApiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Каталог товаров из внешнего магазина.
 *
 * Товары НЕ хранятся в локальной БД — каждый запрос идёт в API магазина
 * через ShopApiService. Категории также приходят из магазина.
 */
class CatalogController extends Controller
{
    /**
     * GET /catalog
     *
     * Параметры:
     *   - page: номер страницы (по умолчанию 1)
     *   - category: slug категории для фильтрации (опционально)
     *
     * Если передана категория, вызывается getProductsFiltered() —
     * магазин не умеет фильтровать, поэтому грузятся все товары
     * и фильтруются локально.
     *
     * Возвращает вьюху catalog.index с:
     *   - products: массив товаров текущей страницы
     *   - meta: пагинация (current_page, last_page, per_page, total)
     *   - categoryTree: дерево категорий (до 4 уровней)
     *   - currentCategory: текущий slug для подсветки в сайдбаре
     */
    public function index(Request $request, ShopApiService $shopApi): View
    {
        $page = (int) $request->query('page', 1);
        $categorySlug = $request->query('category');

        // getProducts сам решит: если есть категория — фильтрует локально
        $result = $shopApi->getProducts($page, 12, $categorySlug);

        // Категории нужны для сайдбара — строим дерево
        $rawCategories = $shopApi->getCategories();
        $categoryTree = $shopApi->buildCategoryTree($rawCategories);

        // Текущая категория для подсветки активной в сайдбаре
        $currentCategorySlug = $categorySlug;

        return view('catalog.index', [
            'products'           => $result['data'] ?? [],
            'meta'               => $result['meta'] ?? [],
            'categoryTree'       => $categoryTree,
            'currentCategory'    => $currentCategorySlug,
        ]);
    }

    /**
     * GET /catalog/{id}
     *
     * Карточка одного товара. ID — ID товара во внешнем магазине.
     * Если магазин вернул 404 или ошибку — abort(404).
     */
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