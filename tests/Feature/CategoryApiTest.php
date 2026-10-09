<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\User;


class CategoryApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->actingAs($user, 'api');
    }


    public function test_categories_list(): void
    {
        $this->getJson(route('api.categories.index'))
            ->assertOk();
    }

    public function test_category_can_be_created_via_api(): void
    {
        $data = [
            'title' => 'API test category',
            'slug' => 'api-test-' . Str::uuid(),
            'active' => true,
            'sort_order' => 0,
        ];

        $response = $this->postJson(
            route('api.categories.store'),
            $data
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'title' => $data['title'],
            'slug' => $data['slug'],
        ]);
    }

    public function test_category_can_be_shown_via_api(): void
    {
        $category = $this->createCategory();

        $this->getJson(
            route('api.categories.show', $category)
        )
            ->assertOk()
            ->assertJsonFragment([
                'title' => $category->title,
                'slug' => $category->slug,
            ]);
    }

    public function test_category_can_be_updated_via_api(): void
    {
        $category = $this->createCategory();

        $response = $this->putJson(
            route('api.categories.update', $category),
            [
                'title' => 'Updated API category',
                'slug' => 'updated-api-category',
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'title' => 'Updated API category',
        ]);
    }

    public function test_category_can_be_deleted_via_api(): void
    {
        $category = $this->createCategory();
        $categoryId = $category->id;

        $response = $this->deleteJson(
            route('api.categories.destroy', $category)
        );

        $response->assertSuccessful();

        $this->assertDatabaseMissing('categories', [
            'id' => $categoryId,
        ]);
    }

    private function createCategory(): Category
    {
        return Category::query()->create([
            'title' => 'API test category',
            'slug' => 'api-test-' . Str::uuid(),
            'active' => true,
            'sort_order' => 0,
        ]);
    }
}
