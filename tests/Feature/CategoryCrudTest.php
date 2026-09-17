<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        auth()->logout();

        $this->get(route('categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_lists_only_own_categories(): void
    {
        Category::create(['user_id' => $this->user->id, 'name' => 'Makan', 'type' => 'expense', 'color' => '#ef4444']);
        Category::create(['user_id' => $this->otherUser->id, 'name' => 'Secret', 'type' => 'income', 'color' => '#22c55e']);

        $response = $this->get(route('categories.index'))->assertOk();

        $categories = $response->inertiaProps('categories');
        $this->assertCount(1, $categories);
        $this->assertEquals('Makan', $categories[0]['name']);
    }

    public function test_create_page_is_accessible(): void
    {
        $this->get(route('categories.create'))->assertOk();
    }

    public function test_valid_category_can_be_stored(): void
    {
        $this->post(route('categories.store'), [
            'name' => 'Gaji',
            'type' => 'income',
            'color' => '#22c55e',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'name' => 'Gaji',
            'type' => 'income',
            'color' => '#22c55e',
        ]);
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->post(route('categories.store'), [
            'name' => 'Bad',
            'type' => 'transfer',
            'color' => '#000000',
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('categories', ['name' => 'Bad']);
    }

    public function test_oversized_color_is_rejected(): void
    {
        $this->post(route('categories.store'), [
            'name' => 'Bad',
            'type' => 'income',
            'color' => str_repeat('a', 8),
        ])->assertSessionHasErrors('color');

        $this->assertDatabaseMissing('categories', ['name' => 'Bad']);
    }

    public function test_mass_assignment_user_id_is_ignored(): void
    {
        $this->post(route('categories.store'), [
            'name' => 'Hijacked',
            'type' => 'expense',
            'color' => '#000000',
            'user_id' => $this->otherUser->id,
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Hijacked', 'user_id' => $this->user->id]);
        $this->assertDatabaseMissing('categories', ['name' => 'Hijacked', 'user_id' => $this->otherUser->id]);
    }

    public function test_edit_page_is_accessible(): void
    {
        $category = $this->user->categories()->create(['name' => 'Makan', 'type' => 'expense', 'color' => '#ef4444']);

        $this->get(route('categories.edit', $category->id))->assertOk();
    }

    public function test_category_can_be_updated(): void
    {
        $category = $this->user->categories()->create(['name' => 'Makan', 'type' => 'expense', 'color' => '#ef4444']);

        $this->put(route('categories.update', $category->id), [
            'name' => 'Makanan',
            'type' => 'expense',
            'color' => '#f97316',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Makanan',
            'color' => '#f97316',
        ]);
    }

    public function test_category_can_be_deleted(): void
    {
        $category = $this->user->categories()->create(['name' => 'Temp', 'type' => 'expense']);

        $this->delete(route('categories.destroy', $category->id))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_update_another_users_category(): void
    {
        $otherCategory = Category::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Milk',
            'type' => 'expense',
        ]);

        $this->put(route('categories.update', $otherCategory->id), [
            'name' => 'Hacked',
            'type' => 'expense',
        ])->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $otherCategory->id, 'name' => 'Milk']);
    }

    public function test_cannot_delete_another_users_category(): void
    {
        $otherCategory = Category::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Milk',
            'type' => 'expense',
        ]);

        $this->delete(route('categories.destroy', $otherCategory->id))->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $otherCategory->id]);
    }

    public function test_xss_script_in_name_is_stored_escaped_safely(): void
    {
        $payload = '<img src=x onerror=alert(1)>';

        $this->post(route('categories.store'), [
            'name' => $payload,
            'type' => 'expense',
            'color' => '#ef4444',
        ])->assertRedirect(route('categories.index'));

        $category = Category::where('user_id', $this->user->id)->first();
        $this->assertNotNull($category);
        $this->assertSame($payload, $category->name);
    }
}