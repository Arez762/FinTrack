<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SavingsGoalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->account = Account::factory()
            ->for($this->user)
            ->cash()
            ->create(['initial_balance' => 1_000_000]);

        $this->actingAs($this->user);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        auth()->logout();

        $this->get(route('savings-goals.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_own_goals_with_computed_progress(): void
    {
        SavingsGoal::factory()->for($this->user)->create([
            'name' => 'Dana Darurat',
            'target_amount' => 1_000_000,
            'current_amount' => 250_000,
            'target_date' => Carbon::now()->addDays(45)->toDateString(),
        ]);

        SavingsGoal::factory()->for($this->otherUser)->create(['name' => 'Rahasia']);

        $response = $this->get(route('savings-goals.index'))->assertOk();

        $goals = $response->inertiaProps('savingsGoals');

        $this->assertCount(1, $goals);
        $this->assertSame('Dana Darurat', $goals[0]['name']);
        $this->assertEquals(25, $goals[0]['percentage']);
        $this->assertEquals(45, $goals[0]['days_left']);
        $this->assertFalse($goals[0]['is_completed']);
        $this->assertEquals(1_000_000, $goals[0]['target_amount']);
    }

    public function test_index_exposes_account_link_when_goal_is_linked(): void
    {
        SavingsGoal::factory()->for($this->user)
            ->withAccount($this->account)
            ->create(['name' => 'Liburan']);

        $goals = $this->get(route('savings-goals.index'))
            ->assertOk()
            ->inertiaProps('savingsGoals');

        $this->assertSame(
            ['id' => $this->account->id, 'name' => $this->account->name],
            $goals[0]['account'],
        );
    }

    public function test_completed_goal_has_zero_days_left(): void
    {
        SavingsGoal::factory()->for($this->user)->completed()->create([
            'name' => 'Tercapai',
            'target_date' => Carbon::now()->addDays(10)->toDateString(),
        ]);

        $goals = $this->get(route('savings-goals.index'))
            ->assertOk()
            ->inertiaProps('savingsGoals');

        $this->assertTrue($goals[0]['is_completed']);
        $this->assertEquals(0, $goals[0]['days_left']);
        $this->assertEquals(100, $goals[0]['percentage']);
    }

    public function test_valid_goal_can_be_stored(): void
    {
        $this->post(route('savings-goals.store'), [
            'name' => 'Liburan Bali',
            'target_amount' => 5_000_000,
            'target_date' => Carbon::now()->addMonths(3)->toDateString(),
            'account_id' => $this->account->id,
            'icon' => '🏖️',
            'color' => '#0ea5e9',
        ])->assertRedirect(route('savings-goals.index'));

        $this->assertDatabaseHas('savings_goals', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'name' => 'Liburan Bali',
            'target_amount' => 5_000_000,
            'current_amount' => 0,
            'icon' => '🏖️',
            'color' => '#0ea5e9',
            'is_completed' => false,
        ]);
    }

    public function test_mass_assignment_of_user_id_is_ignored(): void
    {
        $this->post(route('savings-goals.store'), [
            'name' => 'Hijacked',
            'target_amount' => 1_000_000,
            'user_id' => $this->otherUser->id,
        ])->assertRedirect(route('savings-goals.index'));

        $this->assertDatabaseHas('savings_goals', [
            'name' => 'Hijacked',
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseMissing('savings_goals', [
            'name' => 'Hijacked',
            'user_id' => $this->otherUser->id,
        ]);
    }

    public function test_target_amount_must_be_greater_than_zero(): void
    {
        $this->post(route('savings-goals.store'), [
            'name' => 'Gagal',
            'target_amount' => 0,
        ])->assertSessionHasErrors('target_amount');

        $this->assertDatabaseCount('savings_goals', 0);
    }

    public function test_negative_target_amount_is_rejected(): void
    {
        $this->post(route('savings-goals.store'), [
            'name' => 'Gagal',
            'target_amount' => -100,
        ])->assertSessionHasErrors('target_amount');

        $this->assertDatabaseCount('savings_goals', 0);
    }

    public function test_name_is_required(): void
    {
        $this->post(route('savings-goals.store'), [
            'target_amount' => 1_000_000,
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('savings_goals', 0);
    }

    public function test_account_must_belong_to_the_user(): void
    {
        $otherAccount = Account::factory()->for($this->otherUser)->create();

        $this->post(route('savings-goals.store'), [
            'name' => 'Gagal',
            'target_amount' => 1_000_000,
            'account_id' => $otherAccount->id,
        ])->assertSessionHasErrors('account_id');

        $this->assertDatabaseCount('savings_goals', 0);
    }

    public function test_goal_can_be_updated(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)->create([
            'name' => 'Dana Darurat',
            'target_amount' => 1_000_000,
            'current_amount' => 400_000,
        ]);

        $this->put(route('savings-goals.update', $goal->id), [
            'name' => 'Dana Darurat Plus',
            'target_amount' => 2_000_000,
            'target_date' => null,
            'account_id' => $this->account->id,
            'icon' => '💪',
            'color' => '#3b82f6',
        ])->assertRedirect(route('savings-goals.index'));

        $this->assertDatabaseHas('savings_goals', [
            'id' => $goal->id,
            'name' => 'Dana Darurat Plus',
            'target_amount' => 2_000_000,
            'current_amount' => 400_000,
            'account_id' => $this->account->id,
        ]);
    }

    public function test_cannot_update_another_users_goal(): void
    {
        $otherGoal = SavingsGoal::factory()->for($this->otherUser)->create([
            'name' => 'Milk',
            'target_amount' => 100_000,
        ]);

        $this->put(route('savings-goals.update', $otherGoal->id), [
            'name' => 'Hacked',
            'target_amount' => 100_000,
        ])->assertForbidden();

        $this->assertDatabaseHas('savings_goals', [
            'id' => $otherGoal->id,
            'name' => 'Milk',
        ]);
    }

    public function test_goal_can_be_deleted(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)->create(['name' => 'Hapus']);

        $this->delete(route('savings-goals.destroy', $goal->id))
            ->assertRedirect(route('savings-goals.index'));

        $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
    }

    public function test_cannot_delete_another_users_goal(): void
    {
        $otherGoal = SavingsGoal::factory()->for($this->otherUser)->create();

        $this->delete(route('savings-goals.destroy', $otherGoal->id))
            ->assertForbidden();

        $this->assertDatabaseHas('savings_goals', ['id' => $otherGoal->id]);
    }

    public function test_add_funds_increases_current_amount(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)->create([
            'name' => 'Dana Darurat',
            'target_amount' => 1_000_000,
            'current_amount' => 200_000,
        ]);

        $this->post(route('savings-goals.addFunds', $goal->id), ['amount' => 300_000])
            ->assertRedirect(route('savings-goals.index'));

        $this->assertDatabaseHas('savings_goals', [
            'id' => $goal->id,
            'current_amount' => 500_000,
            'is_completed' => false,
        ]);
    }

    public function test_add_funds_to_linked_account_records_expense_transaction(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)
            ->withAccount($this->account)
            ->create(['name' => 'Dana Darurat', 'target_amount' => 1_000_000]);

        $this->post(route('savings-goals.addFunds', $goal->id), ['amount' => 250_000])
            ->assertRedirect(route('savings-goals.index'));

        $category = Category::where('user_id', $this->user->id)
            ->where('name', 'Tabungan')
            ->where('type', 'expense')
            ->first();

        $this->assertNotNull($category);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 250_000,
            'description' => 'Menabung untuk Dana Darurat',
        ]);

        $account = collect($this->get(route('accounts.index'))
            ->assertOk()
            ->inertiaProps('accounts'))
            ->keyBy('id');

        $this->assertEquals(750_000, $account[$this->account->id]['balance']);
    }

    public function test_add_funds_reuses_existing_tabungan_category(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)
            ->withAccount($this->account)
            ->create(['name' => 'Liburan']);

        Category::factory()->for($this->user)->create([
            'name' => 'Tabungan',
            'type' => 'expense',
            'color' => '#f472b6',
        ]);

        $this->post(route('savings-goals.addFunds', $goal->id), ['amount' => 10_000])
            ->assertRedirect(route('savings-goals.index'));

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'name' => 'Tabungan',
            'type' => 'expense',
            'color' => '#f472b6',
        ]);
    }

    public function test_add_funds_marks_goal_completed_and_sets_flash(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)->create([
            'name' => 'Dana Darurat',
            'target_amount' => 1_000_000,
            'current_amount' => 800_000,
        ]);

        $response = $this->post(route('savings-goals.addFunds', $goal->id), ['amount' => 300_000])
            ->assertRedirect(route('savings-goals.index'));

        $response->assertSessionHas('savings_goal_completed', 'Dana Darurat');

        $this->assertDatabaseHas('savings_goals', [
            'id' => $goal->id,
            'current_amount' => 1_100_000,
            'is_completed' => true,
        ]);
    }

    public function test_add_funds_to_completed_goal_does_not_set_completion_flash(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)
            ->completed()
            ->create(['name' => 'Dana Darurat', 'target_amount' => 1_000_000]);

        $response = $this->post(route('savings-goals.addFunds', $goal->id), ['amount' => 100_000])
            ->assertRedirect(route('savings-goals.index'));

        $response->assertSessionMissing('savings_goal_completed');
    }

    public function test_add_funds_amount_must_be_greater_than_zero(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)->create(['target_amount' => 1_000_000]);

        $this->post(route('savings-goals.addFunds', $goal->id), ['amount' => 0])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('savings_goals', [
            'id' => $goal->id,
            'current_amount' => 0,
        ]);
    }

    public function test_cannot_add_funds_to_another_users_goal(): void
    {
        $otherGoal = SavingsGoal::factory()->for($this->otherUser)
            ->completed()
            ->create(['name' => 'Milik Orang Lain']);

        $this->post(route('savings-goals.addFunds', $otherGoal->id), ['amount' => 50_000])
            ->assertForbidden();

        $goal = $otherGoal->fresh();

        $this->assertTrue($goal->is_completed);
        $this->assertEquals($goal->target_amount, $goal->current_amount);
    }

    public function test_create_page_exposes_user_accounts(): void
    {
        $this->get(route('savings-goals.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('SavingsGoals/Create')
                ->has('accounts', 1));
    }

    public function test_edit_page_exposes_own_goal_and_accounts(): void
    {
        $goal = SavingsGoal::factory()->for($this->user)->create([
            'name' => 'Dana Darurat',
            'target_amount' => 1_000_000,
            'current_amount' => 250_000,
        ]);

        $this->get(route('savings-goals.edit', $goal->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('SavingsGoals/Edit')
                ->where('savingsGoal.id', $goal->id)
                ->where('savingsGoal.name', 'Dana Darurat')
                ->where('savingsGoal.current_amount', 250_000)
                ->where('savingsGoal.is_completed', false)
                ->has('accounts', 1));
    }

    public function test_cannot_view_edit_page_for_another_users_goal(): void
    {
        $otherGoal = SavingsGoal::factory()->for($this->otherUser)->create();

        $this->get(route('savings-goals.edit', $otherGoal->id))->assertForbidden();
    }
}
