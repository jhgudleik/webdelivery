<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Services\ShopApiService;
use Illuminate\View\View;

/**
 * Главная страница — витрина сервиса.
 *
 * Показывает 6 товаров из магазина (первая страница API)
 * и три шага: "Выбрать товар → Оформить доставку → Получить".
 */
class HomeController extends Controller
{
    /**
     * GET /
     *
     * Берёт первые 6 товаров через ShopApiService и отдаёт вьюху home.
     * ShopApiService сам залогинется в магазине и подтянет токен.
     */
    public function index(ShopApiService $shopApi): View
    {
        $result = $shopApi->getProducts(1, 6);
        $products = $result['data'] ?? [];

        return view('home', [
            'products' => $products,
        ]);
    }
}