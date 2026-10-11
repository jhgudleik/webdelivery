<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\User;
use Tests\TestCase;

/**
 * Тесты REST API заявок на доставку (JWT-авторизация).
 *
 * Guard: 'api' (driver jwt). setUp() авторизует пользователя
 * через actingAs(..., 'api').
 *
 * Покрывает CRUD API с проверками прав доступа,
 * повторяющими веб-версию.
 */
class DeliveryApiTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    /**
     * GET /api/deliveries — список своих заявок.
     * Проверяет структуру JSON-ответа (paginator).
     */
    public function test_deliveries_list(): void
    {
        Delivery::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/deliveries');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'status', 'items'],
                    ],
                ],
            ]);
    }

    /**
     * POST /api/deliveries — создание через API.
     * Проверяет 201, запись в БД и ответ с items.
     */
    public function test_delivery_can_be_created_via_api(): void
    {
        $response = $this->postJson('/api/deliveries', [
            'title'            => 'API доставка',
            'description'      => 'Создано через API',
            'delivery_address' => 'г. Москва, ул. API, д. 1',
            'phone'            => '+7 (999) 111-22-33',
            'items'            => [
                [
                    'product_id'    => 1,
                    'product_title' => 'API товар',
                    'quantity'      => 3,
                    'price'         => 500.00,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('deliveries', [
            'user_id' => $this->user->id,
            'title'   => 'API доставка',
            'status'  => 'new',
        ]);

        $delivery = Delivery::where('title', 'API доставка')->first();
        $this->assertDatabaseHas('delivery_items', [
            'delivery_id' => $delivery->id,
            'product_id'  => 1,
            'quantity'    => 3,
        ]);
    }

    /**
     * GET /api/deliveries/{id} — просмотр через API.
     */
    public function test_delivery_can_be_shown_via_api(): void
    {
        $delivery = Delivery::factory()->create(['user_id' => $this->user->id]);
        DeliveryItem::factory()->create(['delivery_id' => $delivery->id]);

        $response = $this->getJson("/api/deliveries/{$delivery->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'title'  => $delivery->title,
                'status' => $delivery->status,
            ]);
    }

    /**
     * PUT /api/deliveries/{id} — обновление через API.
     * Только владелец и только статус "new".
     */
    public function test_delivery_can_be_updated_via_api(): void
    {
        $delivery = Delivery::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'new',
        ]);

        $response = $this->putJson("/api/deliveries/{$delivery->id}", [
            'title'            => 'Обновлено через API',
            'delivery_address' => 'Новый адрес API',
            'phone'            => '+7 (000) 000-00-00',
            'items'            => [
                ['product_title' => 'Обновлённый товар', 'quantity' => 1, 'price' => 999],
            ],
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('deliveries', [
            'id'    => $delivery->id,
            'title' => 'Обновлено через API',
        ]);
    }

    /**
     * Обновление заявки НЕ в статусе "new" возвращает 422.
     */
    public function test_delivery_cannot_be_updated_if_not_new(): void
    {
        $delivery = Delivery::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
        ]);

        $response = $this->putJson("/api/deliveries/{$delivery->id}", [
            'title'            => 'Попытка',
            'delivery_address' => 'Адрес',
            'phone'            => '123',
            'items'            => [['product_title' => 'Т', 'quantity' => 1, 'price' => 1]],
        ]);

        $response->assertStatus(422);
    }

    /**
     * DELETE /api/deliveries/{id} — удаление через API.
     */
    public function test_delivery_can_be_deleted_via_api(): void
    {
        $delivery = Delivery::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/deliveries/{$delivery->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('deliveries', ['id' => $delivery->id]);
    }

    /**
     * Нельзя посмотреть чужую заявку через API (403).
     */
    public function test_user_cannot_see_other_users_delivery(): void
    {
        $delivery = Delivery::factory()->create();

        $response = $this->getJson("/api/deliveries/{$delivery->id}");

        $response->assertStatus(403);
    }
}