<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\User;
use Tests\TestCase;

class AdminDeliveryTest extends TestCase
{

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->user = User::factory()->create(['is_admin' => false]);
    }

    // ─── Access control ──────────────────────────────────

    public function test_guest_cannot_access_admin(): void
    {
        $this->get(route('admin.deliveries.index'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.deliveries.index'))
            ->assertRedirect('/');
    }

    public function test_admin_can_access_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.deliveries.index'))
            ->assertOk();
    }

    // ─── Index ───────────────────────────────────────────

    public function test_admin_can_see_all_deliveries(): void
    {
        $delivery1 = Delivery::factory()->create(['user_id' => $this->user->id]);
        $delivery2 = Delivery::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.deliveries.index'));

        $response->assertOk()
            ->assertSee($delivery1->title)
            ->assertSee($delivery2->title);
    }

    public function test_admin_can_filter_by_status(): void
    {
        Delivery::factory()->create(['status' => 'new']);
        Delivery::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.deliveries.index', ['status' => 'new']));

        $response->assertOk();
    }

    // ─── Show ────────────────────────────────────────────

    public function test_admin_can_show_delivery(): void
    {
        $delivery = Delivery::factory()->create(['user_id' => $this->user->id]);
        DeliveryItem::factory()->create(['delivery_id' => $delivery->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.deliveries.show', $delivery))
            ->assertOk()
            ->assertSee($delivery->title);
    }

    // ─── Update status ───────────────────────────────────

    public function test_admin_can_change_status(): void
    {
        $delivery = Delivery::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'new',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.deliveries.status', $delivery), [
                'status'        => 'confirmed',
                'admin_comment' => 'Подтверждено',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('deliveries', [
            'id'            => $delivery->id,
            'status'        => 'confirmed',
            'admin_comment' => 'Подтверждено',
        ]);
    }

    public function test_admin_can_cancel_delivery(): void
    {
        $delivery = Delivery::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.deliveries.status', $delivery), [
                'status' => 'cancelled',
            ]);

        $this->assertDatabaseHas('deliveries', [
            'id'     => $delivery->id,
            'status' => 'cancelled',
        ]);
    }

    // ─── Delete ──────────────────────────────────────────

    public function test_admin_can_delete_delivery(): void
    {
        $delivery = Delivery::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.deliveries.destroy', $delivery))
            ->assertRedirect(route('admin.deliveries.index'));

        $this->assertSoftDeleted('deliveries', ['id' => $delivery->id]);
    }
}
