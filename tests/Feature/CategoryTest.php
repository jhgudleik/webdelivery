<?php

namespace Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_categories_page(): void
    {
        $this->get(route('admin.categories.index'))
            ->assertOk();
    }

    public function test_category_can_be_created(): void
    {
        $category = Category::query()->create([
            'title' => 'Test category',
            'slug' => 'test-category-' . uniqid(),
            'active' => true,
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'title' => 'Test category',
            'slug' => $category->slug,
        ]);
    }

    public function test_category_can_be_shown(): void
    {
        $category = Category::query()->create([
            'title' => 'Test category',
            'slug' => 'test-category-' . uniqid(),
            'active' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('admin.categories.show', $category))
            ->assertOk();
    }

    public function test_category_can_be_updated(): void
    {
        $category = Category::query()->create([
            'title' => 'Old title',
            'slug' => 'old-title-' . uniqid(),
            'active' => true,
            'sort_order' => 0,
        ]);

        $category->update([
            'title' => 'New title',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'title' => 'New title',
        ]);
    }

    public function test_category_can_be_deleted(): void
    {
        $category = Category::query()->create([
            'title' => 'Test category',
            'slug' => 'test-category-' . uniqid(),
            'active' => true,
            'sort_order' => 0,
        ]);

        $categoryId = $category->id;

        $category->delete();

        $this->assertDatabaseMissing('categories', [
            'id' => $categoryId,
        ]);
    }

    public function test_category_can_have_parent(): void
    {
        $parent = Category::query()->create([
            'title' => 'Parent category',
            'slug' => 'parent-' . uniqid(),
            'active' => true,
            'sort_order' => 0,
        ]);

        $child = Category::query()->create([
            'parent_id' => $parent->id,
            'title' => 'Child category',
            'slug' => 'child-' . uniqid(),
            'active' => true,
            'sort_order' => 1,
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
    }

    public function test_category_has_default_active_status(): void
    {
        $category = Category::query()->create([
            'title' => 'Test category',
            'slug' => 'test-category-' . uniqid(),
        ]);

        $this->assertTrue((bool) $category->fresh()->active);
    }
}
