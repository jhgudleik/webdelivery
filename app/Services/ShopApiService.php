<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Клиент для работы с API внешнего интернет-магазина.
 *
 * Зона ответственности:
 *   1. Авторизация: логинится по email/password из .env,
 *      кеширует JWT-токен и автоматически обновляет его при 401.
 *   2. Каталог: категории (дерево до 4 уровней) и товары
 *      с нормализацией URL картинок.
 *   3. Списание остатков: при подтверждении заявки уменьшает
 *      quantity товаров в магазине через PUT /api/products/{id}.
 *
 * Зарегистрирован как singleton в AppServiceProvider,
 * поэтому внедряется в контроллеры через DI.
 */
class ShopApiService
{
    /** Базовый URL магазина без завершающего слеша (например, http://localhost:8001) */
    private string $baseUrl;

    /** Учётные данные для логина в магазин (из .env: SHOP_API_EMAIL / SHOP_API_PASSWORD) */
    private ?string $email;
    private ?string $password;

    /** Таймаут HTTP-запросов в секундах */
    private int $timeout;

    /** Ключ, под которым JWT-токен хранится в кеше */
    private const TOKEN_CACHE_KEY = 'shop_api_jwt_token';

    /** Время жизни токена в кеше (мин). Чуть меньше TTL самого JWT (обычно 60 мин),
     *  чтобы не поймать протухший токен на границе */
    private const TOKEN_CACHE_TTL = 55;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('shop-api.base_url'), '/');
        $this->email    = config('shop-api.email');
        $this->password = config('shop-api.password');
        $this->timeout  = (int) config('shop-api.timeout', 10);
    }

    // ─── Авторизация ────────────────────────────────────

    /**
     * Получить JWT-токен: сначала из кеша, иначе — залогиниться заново.
     *
     * @return string|null  токен или null, если логин невозможен
     */
    protected function getToken(): ?string
    {
        $token = Cache::get(self::TOKEN_CACHE_KEY);

        if ($token) {
            return $token;
        }

        return $this->login();
    }

    /**
     * Залогиниться в магазине (POST /api/auth/login) и закешировать токен.
     *
     * Поддерживает два формата ответа: { access_token } и { token }.
     * При любой ошибке пишет в лог и возвращает null — вызывающий код
     * в этом случае просто покажет «каталог недоступен».
     */
    protected function login(): ?string
    {
        if (!$this->email || !$this->password) {
            Log::error('ShopAPI: email/password not configured');
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->post("{$this->baseUrl}/api/auth/login", [
                    'email'    => $this->email,
                    'password' => $this->password,
                ]);

            if ($response->successful()) {
                $data  = $response->json();
                $token = $data['access_token'] ?? $data['token'] ?? null;

                if ($token) {
                    // Кладём токен в кеш на 55 минут
                    Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addMinutes(self::TOKEN_CACHE_TTL));
                    Log::info('ShopAPI: login successful, token cached');
                    return $token;
                }
            }

            Log::warning('ShopAPI: login failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            // Сюда попадаем, если магазин вообще недоступен (таймаут, connection refused)
            Log::error('ShopAPI: login error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Удалить токен из кеша. Вызывается при ответе 401 —
     * после этого getToken() выполнит свежий логин.
     */
    protected function invalidateToken(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    // ─── HTTP-клиент ────────────────────────────────────

    /**
     * GET-запрос к магазину с Bearer-токеном и одним retry при 401.
     *
     * Схема: токен из кеша → запрос → если 401, сбрасываем кеш,
     * логинимся заново и повторяем запрос один раз.
     *
     * @return \Illuminate\Http\Client\Response|null  null — если обе попытки не удались
     */
    protected function get(string $url, array $params = []): \Illuminate\Http\Client\Response|null
    {
        $token = $this->getToken();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $client = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json']);

            if ($token) {
                $client = $client->withToken($token);
            }

            $response = $client->get($url, $params);

            if ($response->status() === 401 && $attempt === 0) {
                // Токен протух (или был отозван) — перелогиниваемся и повторяем
                $this->invalidateToken();
                $token = $this->login();
                continue;
            }

            return $response;
        }

        return null;
    }

    // ─── Нормализация данных ─────────────────────────────

    /**
     * Преобразовать относительный путь картинки в абсолютный URL магазина.
     *
     * Контекст: Laravel хранит файлы в storage/app/public/, а раздаёт
     * через симлинк public/storage/. API магазина возвращает внутренний
     * путь вроде "products/xxx.jpg", а браузеру нужен полный URL:
     *   "products/xxx.jpg" → "http://localhost:8001/storage/products/xxx.jpg"
     */
    protected function resolveImageUrl(?string $image): ?string
    {
        if (!$image) {
            return null;
        }

        // Уже абсолютный URL — возвращаем как есть
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        $image = ltrim($image, '/');

        // Если путь уже начинается с storage/ — просто добавляем базовый URL
        if (str_starts_with($image, 'storage/')) {
            return $this->baseUrl . '/' . $image;
        }

        // Иначе это путь относительно storage/app/public/ — добавляем префикс storage/
        return $this->baseUrl . '/storage/' . $image;
    }

    /**
     * Нормализовать один товар: заменить относительный путь картинки на абсолютный.
     */
    protected function normalizeProduct(array $product): array
    {
        if (isset($product['image'])) {
            $product['image'] = $this->resolveImageUrl($product['image']);
        }

        return $product;
    }

    /**
     * Нормализовать массив товаров (применить normalizeProduct к каждому).
     */
    protected function normalizeProducts(array $products): array
    {
        return array_map(fn ($p) => $this->normalizeProduct($p), $products);
    }

    /**
     * Построить дерево категорий из ответа API.
     *
     * API может вернуть два формата:
     *   1. Плоский массив с полем parent_id — собираем дерево по ссылкам.
     *   2. Уже вложенный массив с children — только обрезаем глубину.
     *
     * Глубина ограничена $maxDepth (по ТЗ — 4 уровня).
     */
    public function buildCategoryTree(array $categories, int $maxDepth = 4): array
    {
        // Формат 2: children уже есть — просто ограничиваем глубину
        if (isset($categories[0]['children'])) {
            return $this->limitTreeDepth($categories, $maxDepth);
        }

        // Формат 1: индексируем категории по id (или slug, если id нет)
        $indexed = [];
        foreach ($categories as $cat) {
            $id = $cat['id'] ?? $cat['slug'] ?? null;
            if ($id !== null) {
                $cat['children'] = [];
                $indexed[$id] = $cat;
            }
        }

        // Раскладываем: у кого есть родитель — в его children, остальные — в корень.
        // Работаем по ссылке (&$cat), чтобы children заполнялись «на месте».
        $tree = [];
        foreach ($indexed as &$cat) {
            $parentId = $cat['parent_id'] ?? null;
            if ($parentId && isset($indexed[$parentId])) {
                $indexed[$parentId]['children'][] = &$cat;
            } else {
                $tree[] = &$cat;
            }
        }
        unset($cat); // обязательно сбрасываем ссылку после foreach by-reference

        return $this->limitTreeDepth($tree, $maxDepth);
    }

    /**
     * Рекурсивно обрезать вложенность дерева до $maxDepth уровней.
     * Узлы на максимальной глубине лишаются children.
     */
    protected function limitTreeDepth(array $tree, int $maxDepth, int $current = 1): array
    {
        if ($current >= $maxDepth) {
            foreach ($tree as &$cat) {
                $cat['children'] = [];
            }
            unset($cat);
            return $tree;
        }

        foreach ($tree as &$cat) {
            if (!empty($cat['children'])) {
                $cat['children'] = $this->limitTreeDepth($cat['children'], $maxDepth, $current + 1);
            }
        }
        unset($cat);

        return $tree;
    }

    // ─── API методы ─────────────────────────────────────

    /**
     * Получить список категорий из магазина (плоский, как отдал API).
     * При ошибке возвращает пустой массив — сайдбар просто будет пустым.
     */
    public function getCategories(): array
    {
        try {
            $response = $this->get("{$this->baseUrl}/api/categories");

            if ($response && $response->successful()) {
                $data = $response->json();
                // API может оборачивать массив в ключ data — разворачиваем
                return $data['data'] ?? $data;
            }

            Log::warning('ShopAPI: failed to fetch categories', [
                'status' => $response?->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('ShopAPI: categories error', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Получить список товаров с пагинацией.
     *
     * Возвращает структуру ['data' => [...товары], 'meta' => [...пагинация]].
     *
     * Особенность: API магазина игнорирует параметр фильтрации по категории,
     * поэтому при переданном $categorySlug используется getProductsFiltered() —
     * загрузка всех товаров и фильтрация на нашей стороне.
     */
    public function getProducts(int $page = 1, int $perPage = 12, ?string $categorySlug = null): array
    {
        try {
            // Фильтрация — отдельная ветка с локальной обработкой
            if ($categorySlug) {
                return $this->getProductsFiltered($categorySlug, $page, $perPage);
            }

            $params = [
                'page'     => $page,
                'per_page' => $perPage,
            ];

            $response = $this->get("{$this->baseUrl}/api/products", $params);

            if ($response && $response->successful()) {
                $data = $response->json();

                // Формат 1: пагинация Laravel на верхнем уровне
                // { data: [...], current_page, last_page, per_page, total }
                if (isset($data['data']) && isset($data['current_page'])) {
                    return [
                        'data' => $this->normalizeProducts($data['data']),
                        'meta' => [
                            'current_page' => $data['current_page'],
                            'last_page'    => $data['last_page'],
                            'per_page'     => $data['per_page'],
                            'total'        => $data['total'],
                        ],
                    ];
                }

                // Формат 2: пагинация вложена внутрь data
                // { data: { data: [...], current_page, ... } }
                if (isset($data['data']['data'])) {
                    return [
                        'data' => $this->normalizeProducts($data['data']['data']),
                        'meta' => [
                            'current_page' => $data['data']['current_page'] ?? 1,
                            'last_page'    => $data['data']['last_page'] ?? 1,
                            'per_page'     => $data['data']['per_page'] ?? $perPage,
                            'total'        => $data['data']['total'] ?? 0,
                        ],
                    ];
                }

                // Формат 3: просто массив без метаданных
                return [
                    'data' => $this->normalizeProducts($data['data'] ?? $data),
                    'meta' => [],
                ];
            }

            Log::warning('ShopAPI: failed to fetch products', [
                'status' => $response?->status(),
            ]);

            return ['data' => [], 'meta' => []];
        } catch (\Exception $e) {
            Log::error('ShopAPI: products error', ['message' => $e->getMessage()]);
            return ['data' => [], 'meta' => []];
        }
    }

    /**
     * Фильтрация по категории на стороне WebDelivery.
     *
     * API магазина не поддерживает фильтр, поэтому:
     *   1. Выгружаем ВСЕ товары постранично (per_page=100, пока не кончатся страницы).
     *   2. Оставляем товары, у которых category.slug совпадает с запрошенным.
     *   3. Применяем пагинацию вручную (array_slice).
     *
     * Ограничение: товар подкатегории не попадёт в фильтр родительской
     * категории — API отдаёт товару только его прямую категорию.
     */
    protected function getProductsFiltered(string $categorySlug, int $page, int $perPage): array
    {
        // Шаг 1: выгружаем все страницы API
        $allProducts = [];
        $apiPage = 1;

        do {
            $response = $this->get("{$this->baseUrl}/api/products", [
                'page'     => $apiPage,
                'per_page' => 100,
            ]);

            if (!$response || !$response->successful()) {
                break;
            }

            $data = $response->json();
            $items = $data['data'] ?? $data;
            if (isset($items['data'])) {
                $items = $items['data']; // вложенный формат
            }

            if (empty($items)) {
                break;
            }

            $allProducts = array_merge($allProducts, $items);
            $lastPage = $data['last_page'] ?? $data['data']['last_page'] ?? 1;
            $apiPage++;
        } while ($apiPage <= $lastPage);

        // Шаг 2: фильтруем по slug категории товара
        $filtered = array_values(array_filter($allProducts, function ($product) use ($categorySlug) {
            $catSlug = $product['category']['slug'] ?? $product['category_slug'] ?? '';
            return $catSlug === $categorySlug;
        }));

        // Шаг 3: ручная пагинация отфильтрованного массива
        $total = count($filtered);
        $lastPageNum = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPageNum); // защита от page > last_page
        $sliced = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        return [
            'data' => $this->normalizeProducts($sliced),
            'meta' => [
                'current_page' => $page,
                'last_page'    => $lastPageNum,
                'per_page'     => $perPage,
                'total'        => $total,
            ],
        ];
    }

    /**
     * Получить один товар по ID (для карточки товара и списания остатков).
     * Возвращает null, если товар не найден или магазин недоступен.
     */
    public function getProduct(int|string $id): ?array
    {
        try {
            $response = $this->get("{$this->baseUrl}/api/products/{$id}");

            if ($response && $response->successful()) {
                $data = $response->json();
                $product = $data['data'] ?? $data;
                return $this->normalizeProduct($product);
            }

            Log::warning('ShopAPI: failed to fetch product', [
                'id'     => $id,
                'status' => $response?->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('ShopAPI: product error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    // ─── HTTP PUT ────────────────────────────────────────

    /**
     * PUT-запрос к магазину. Логика токена и retry идентична get().
     * Используется для списания остатков.
     */
    protected function put(string $url, array $data = []): \Illuminate\Http\Client\Response|null
    {
        $token = $this->getToken();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $client = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ]);

            if ($token) {
                $client = $client->withToken($token);
            }

            $response = $client->put($url, $data);

            if ($response->status() === 401 && $attempt === 0) {
                $this->invalidateToken();
                $token = $this->login();
                continue;
            }

            return $response;
        }

        return null;
    }

    /**
     * Уменьшить остаток одного товара в магазине.
     *
     * Алгоритм:
     *   1. GET — получить текущий товар и его quantity.
     *   2. newQty = max(0, текущий − списываемый).
     *   3. PUT — отправить товар ЦЕЛИКОМ с новым quantity.
     *
     * Почему целиком: API магазина требует title и price как обязательные
     * поля при PUT, частичное обновление возвращает 422.
     *
     * @return bool  true — остаток успешно уменьшен
     */
    public function reduceProductQuantity(int $productId, int $quantity): bool
    {
        $product = $this->getProduct($productId);

        if (!$product) {
            Log::warning("ShopAPI: product #{$productId} not found for stock reduction");
            return false;
        }

        $currentQty = (int) ($product['quantity'] ?? 0);
        $newQty = max(0, $currentQty - $quantity);

        // API требует title и price — отправляем все поля товара
        $updateData = [
            'title'       => $product['title'],
            'price'       => $product['price'],
            'quantity'    => $newQty,
            'category_id' => $product['category_id'] ?? $product['category']['id'] ?? null,
            'active'      => $product['active'] ?? true,
        ];

        if (!empty($product['description'])) {
            $updateData['description'] = $product['description'];
        }

        $response = $this->put("{$this->baseUrl}/api/products/{$productId}", $updateData);

        if ($response && $response->successful()) {
            Log::info("ShopAPI: product #{$productId} quantity reduced {$currentQty} → {$newQty}");
            return true;
        }

        Log::error("ShopAPI: failed to reduce product #{$productId} quantity", [
            'status' => $response?->status(),
            'body'   => $response?->body(),
        ]);

        return false;
    }

    /**
     * Списать остатки по всем позициям заявки на доставку.
     *
     * Вызывается из Admin\DeliveryController::updateStatus() при переходе
     * заявки в статус "confirmed". Ошибки по отдельным товарам собираются
     * в массив — обработка остальных продолжается.
     *
     * @return array{success: bool, errors: string[]}
     */
    public function reduceDeliveryStock(\App\Models\Delivery $delivery): array
    {
        $errors = [];

        foreach ($delivery->items as $item) {
            // Позиция без product_id не связана с магазином — списывать нечего
            if (!$item->product_id) {
                $errors[] = "Товар «{$item->product_title}» не привязан к магазину (нет product_id)";
                continue;
            }

            $ok = $this->reduceProductQuantity($item->product_id, $item->quantity);

            if (!$ok) {
                $errors[] = "Не удалось списать остатки для «{$item->product_title}» (ID: {$item->product_id})";
            }
        }

        return [
            'success' => empty($errors),
            'errors'  => $errors,
        ];
    }
}
