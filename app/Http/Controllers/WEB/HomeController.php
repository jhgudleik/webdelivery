<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Services\ShopApiService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(ShopApiService $shopApi): View
    {
        $result = $shopApi->getProducts(1, 6);
        $products = $result['data'] ?? [];

        return view('home', [
            'products' => $products,
        ]);
    }
}
