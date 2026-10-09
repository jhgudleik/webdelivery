<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopApiService
{
    private string $baseUrl;
    private ?string $email;
    private ?string $password;
    private int $timeout;

    private const TOKEN_CACHE_KEY = 'shop_api_jwt_token';
    private const TOKEN_CACHE_TTL = 55; // минут (JWT обычно живёт 60 мин)

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('shop-api.base_url'), '/');
        $this->email    = config('shop-api.email');
        $this->password = config('shop-api.password');
        $this->timeout  = (int) config('shop-api.timeout', 10);
    }

    // ─── Авторизация ────────────────────────────────────

    /**
     * Получить JWT-токен (из кеша или через логин).
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
     * Залогиниться в магазине и закешировать токен.
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
            Log::error('ShopAPI: login error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Сбросить кеш токена (при 401).
     */
    protected function invalidateToken(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    // ─── HTTP-клиент ────────────────────────────────────

    /**
     * Выполнить GET-запрос с автоматическим логином и retry при 401.
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
                // Токен протух — логинимся заново
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
     * Laravel хранит файлы в storage/app/public/, а отдаёт через симлинк public/storage/.
     * API возвращает путь вроде "products/xxx.jpg", а нужен URL "storage/products/xxx.jpg".
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

        // Иначе — это путь относительно storage/app/public/,
        // добавляем префикс storage/
        return $this->baseUrl . '/storage/' . $image;
    }

    /**
     * Нормализовать данные товара (подставить абсолютные URL картинок).
     */
    protected function normalizeProduct(array $product): array
    {
        if (isset($product['image'])) {
            $product['image'] = $this->resolveImageUrl($product['image']);
        }

        return $product;
    }

    /**
     * Нормализовать массив товаров.
     */
    protected function normalizeProducts(array $products): array
    {
        return array_map(fn ($p) => $this->normalizeProduct($p), $products);
    }

    /**
     * Построить дерево категорий из плоского массива API.
     *
     * API может вернуть:
     * - плоский массив с parent_id (рекурсия до 4 уровней)
     * - уже вложенный массив с children
     */
    public function buildCategoryTree(array $categories, int $maxDepth = 4): array
    {
        // Если категории уже содержат children — возвращаем как есть (обрезаем глубину)
        if (isset($categories[0]['children'])) {
            return $this->limitTreeDepth($categories, $maxDepth);
        }

        // Строим дерево из плоского массива по parent_id
        $indexed = [];
        foreach ($categories as $cat) {
            $id = $cat['id'] ?? $cat['slug'] ?? null;
            if ($id !== null) {
                $cat['children'] = [];
                $indexed[$id] = $cat;
            }
        }

        $tree = [];
        foreach ($indexed as &$cat) {
            $parentId = $cat['parent_id'] ?? null;
            if ($parentId && isset($indexed[$parentId])) {
                $indexed[$parentId]['children'][] = &$cat;
            } else {
                $tree[] = &$cat;
            }
        }
        unset($cat);

        return $this->limitTreeDepth($tree, $maxDepth);
    }

    /**
     * Обрезать вложенность дерева до N уровней.
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
     * Категории из внешнего магазина.
     */
    public function getCategories(): array
    {
        try {
            $response = $this->get("{$this->baseUrl}/api/categories");

            if ($response && $response->successful()) {
                $data = $response->json();
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
     * Список товаров с пагинацией.
     *
     * Если API магазина не поддерживает фильтрацию по категории,
     * фильтрация выполняется локально.
     */
    public function getProducts(int $page = 1, int $perPage = 12, ?string $categorySlug = null): array
    {
        try {
            // Если нужна фильтрация — загружаем все товары и фильтруем локально
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
     * Загрузить все товары, отфильтровать по категории и вернуть с пагинацией.
     */
    protected function getProductsFiltered(string $categorySlug, int $page, int $perPage): array
    {
        // Загружаем все товары (по страницам API)
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
                $items = $items['data'];
            }

            if (empty($items)) {
                break;
            }

            $allProducts = array_merge($allProducts, $items);
            $lastPage = $data['last_page'] ?? $data['data']['last_page'] ?? 1;
            $apiPage++;
        } while ($apiPage <= $lastPage);

        // Фильтруем по slug категории (включая подкатегории — проверяем category.slug)
        $filtered = array_values(array_filter($allProducts, function ($product) use ($categorySlug) {
            $catSlug = $product['category']['slug'] ?? $product['category_slug'] ?? '';
            return $catSlug === $categorySlug;
        }));

        // Локальная пагинация
        $total = count($filtered);
        $lastPageNum = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPageNum);
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
     * Один товар по ID.
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
     * Выполнить PUT-запрос с автоматическим логином и retry при 401.
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
     * Уменьшить количество товара на складе внешнего магазина.
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
     * Списать остатки по всем товарам доставки.
     */
    public function reduceDeliveryStock(\App\Models\Delivery $delivery): array
    {
        $errors = [];

        foreach ($delivery->items as $item) {
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
