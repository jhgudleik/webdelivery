<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }

    public function test_products_list(): void
    {
        $response = $this->getJson(route('api.products.index'));

        $response->assertOk();
    }

    public function test_product_can_be_created_via_api(): void
    {
        $slug = 'api-product-' . Str::uuid();

        $response = $this->postJson(route('api.products.store'), [
            'title' => 'API Test Product',
            'slug' => $slug,
            'description' => 'Product created by API test',
            'price' => 199.99,
            'quantity' => 10,
            'active' => true,
            'category_id' => null,
            'image' => null,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'title' => 'API Test Product',
            'slug' => $slug,
        ]);
    }

    public function test_product_can_be_shown_via_api(): void
    {
        $product = $this->createProduct();

        $response = $this->getJson(
            route('api.products.show', $product)
        );

        $response->assertOk()
            ->assertJsonFragment([
                'title' => $product->title,
                'slug' => $product->slug,
            ]);
    }

    public function test_product_can_be_updated_via_api(): void
    {
        $product = $this->createProduct();

        $response = $this->putJson(
            route('api.products.update', $product),
            [
                'title' => 'Updated API Product',
                'slug' => 'updated-api-product-' . Str::uuid(),
                'description' => 'Updated description',
                'price' => 249.99,
                'quantity' => 20,
                'active' => true,
                'category_id' => null,
                'image' => null,
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'Updated API Product',
        ]);
    }

    public function test_product_can_be_deleted_via_api(): void
    {
        $product = $this->createProduct();

        $response = $this->deleteJson(
            route('api.products.destroy', $product)
        );

        $response->assertSuccessful();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    private function createProduct(): Product
    {
        return Product::create([
            'category_id' => null,
            'title' => 'Test Product',
            'slug' => 'test-product-' . Str::uuid(),
            'description' => 'Test description',
            'price' => 99.99,
            'quantity' => 5,
            'active' => true,
            'image' => null,
        ]);
    }
}