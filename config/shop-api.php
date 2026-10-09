<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External Shop API Configuration
    |--------------------------------------------------------------------------
    |
    | Подключение к внешнему магазину для получения товаров.
    | ShopApiService автоматически логинится по email/password,
    | кеширует JWT-токен и обновляет при истечении.
    |
    */

    'base_url' => env('SHOP_API_URL', 'http://localhost:8001'),

    'email'    => env('SHOP_API_EMAIL'),
    'password' => env('SHOP_API_PASSWORD'),

    'timeout'  => env('SHOP_API_TIMEOUT', 10),

];
