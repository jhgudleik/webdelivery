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
     */
    public function getProducts(int $page = 1, int $perPage = 12, ?string $categorySlug = null): array
    {
        try {
            $params = [
                'page'     => $page,
                'per_page' => $perPage,
            ];

            if ($categorySlug) {
                $params['category'] = $categorySlug;
            }

            $response = $this->get("{$this->baseUrl}/api/products", $params);

            if ($response && $response->successful()) {
                $data = $response->json();

                // Нормализуем ответ (магазин может отдавать по-разному)
                if (isset($data['data']) && isset($data['current_page'])) {
                    return [
                        'data' => $data['data'],
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
                        'data' => $data['data']['data'],
                        'meta' => [
                            'current_page' => $data['data']['current_page'] ?? 1,
                            'last_page'    => $data['data']['last_page'] ?? 1,
                            'per_page'     => $data['data']['per_page'] ?? $perPage,
                            'total'        => $data['data']['total'] ?? 0,
                        ],
                    ];
                }

                return [
                    'data' => $data['data'] ?? $data,
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
     * Один товар по ID.
     */
    public function getProduct(int|string $id): ?array
    {
        try {
            $response = $this->get("{$this->baseUrl}/api/products/{$id}");

            if ($response && $response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
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
}
