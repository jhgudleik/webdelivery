<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\User;
use Tests\TestCase;

class DeliveryTest extends TestCase
{

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ─── Index ───────────────────────────────────────────

    public function test_guest_cannot_see_deliveries(): void
    {
        $this->get(route('deliveries.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_see_deliveries_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('deliveries.index'))
            ->assertOk();
    }

    public function test_user_sees_only_own_deliveries(): void
    {
        $myDelivery = Delivery::factory()->create(['user_id' => $this->user->id]);
        $otherDelivery = Delivery::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('deliveries.index'));

        $response->assertSee($myDelivery->title);
        $response->assertDontSee($otherDelivery->title);
    }

    // ─── Create ──────────────────────────────────────────

    public function test_user_can_see_create_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('deliveries.create'))
            ->assertOk();
    }

    public function test_user_can_create_delivery(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('deliveries.store'), [
            'title'            => 'Тестовая доставка',
            'description'      => 'Описание тестовой доставки',
            'delivery_address' => 'г. Москва, ул. Тестовая, д. 1',
            'phone'            => '+7 (999) 000-00-00',
            'items'            => [
                [
                    'product_id'    => 1,
                    'product_title' => 'Тестовый товар',
                    'product_sku'   => 'TEST-001',
                    'quantity'      => 2,
                    'price'         => 1500.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('deliveries', [
            'user_id'          => $this->user->id,
            'title'            => 'Тестовая доставка',
            'delivery_address' => 'г. Москва, ул. Тестовая, д. 1',
            'phone'            => '+7 (999) 000-00-00',
            'status'           => 'new',
        ]);

        $delivery = Delivery::where('title', 'Тестовая доставка')->first();
        $this->assertDatabaseHas('delivery_items', [
            'delivery_id'  => $delivery->id,
            'product_id'   => 1,
            'product_title'=> 'Тестовый товар',
            'quantity'     => 2,
            'price'        => 1500.00,
        ]);
    }

    public function test_create_delivery_requires_title(): void
    {
        $this->actingAs($this->user)
            ->post(route('deliveries.store'), [
                'title'            => '',
                'delivery_address' => 'Адрес',
                'phone'            => '123',
                'items'            => [['product_title' => 'Товар', 'quantity' => 1, 'price' => 100]],
            ])
            ->assertSessionHasErrors('title');
    }

    public function test_create_delivery_requires_items(): void
    {
        $this->actingAs($this->user)
            ->post(route('deliveries.store'), [
                'title'            => 'Доставка',
                'delivery_address' => 'Адрес',
                'phone'            => '123',
                'items'            => [],
            ])
            ->assertSessionHasErrors('items');
    }

    // ─── Show ────────────────────────────────────────────

    public function test_user_can_see_own_delivery(): void
    {
        $delivery = Delivery::factory()->create(['user_id' => $this->user->id]);
        DeliveryItem::factory()->create(['delivery_id' => $delivery->id]);

        $this->actingAs($this->user)
            ->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee($delivery->title);
    }

    public function test_user_cannot_see_other_delivery(): void
    {
        $delivery = Delivery::factory()->create();

        $this->actingAs($this->user)
            ->get(route('deliveries.show', $delivery))
            ->assertStatus(403);
    }

    // ─── Edit ────────────────────────────────────────────

    public function test_user_can_edit_own_new_delivery(): void
    {
        $delivery = Delivery::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'new',
        ]);

        $this->actingAs($this->user)
            ->get(route('deliveries.edit', $delivery))
            ->assertOk();
    }

    public function test_user_cannot_edit_confirmed_delivery(): void
    {
        $delivery = Delivery::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
        ]);

        $this->actingAs($this->user)
            ->get(route('deliveries.edit', $delivery))
            ->assertRedirect();
    }

    // ─── Update ──────────────────────────────────────────

    public function test_user_can_update_own_new_delivery(): void
    {
        $delivery = Delivery::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'new',
        ]);

        $this->actingAs($this->user)
            ->put(route('deliveries.update', $delivery), [
                'title'            => 'Обновлённая доставка',
                'delivery_address' => 'Новый адрес',
                'phone'            => '+7 (000) 000-00-00',
                'items'            => [
                    ['product_title' => 'Новый товар', 'quantity' => 1, 'price' => 500],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('deliveries', [
            'id'    => $delivery->id,
            'title' => 'Обновлённая доставка',
        ]);
    }

    // ─── Delete ──────────────────────────────────────────

    public function test_user_can_delete_own_delivery(): void
    {
        $delivery = Delivery::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete(route('deliveries.destroy', $delivery))
            ->assertRedirect(route('deliveries.index'));

        $this->assertSoftDeleted('deliveries', ['id' => $delivery->id]);
    }

    // ─── Total price ─────────────────────────────────────

    public function test_delivery_recalculates_total(): void
    {
        $delivery = Delivery::factory()->create(['user_id' => $this->user->id]);

        DeliveryItem::create([
            'delivery_id'  => $delivery->id,
            'product_title'=> 'Товар 1',
            'quantity'     => 2,
            'price'        => 100,
        ]);
        DeliveryItem::create([
            'delivery_id'  => $delivery->id,
            'product_title'=> 'Товар 2',
            'quantity'     => 1,
            'price'        => 300,
        ]);

        $delivery->recalculateTotal();

        $this->assertEquals(500.00, (float) $delivery->fresh()->total_price);
    }
}
