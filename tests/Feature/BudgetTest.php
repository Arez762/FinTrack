<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Account $account;

    private Account $otherAccount;

    private Category $food;

    private Category $transport;

    private Category $salary;

    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->actingAs($this->user);

        $this->now = Carbon::now();

        $this->account = $this->accountFor($this->user, 'Cash');
        $this->otherAccount = $this->accountFor($this->otherUser, 'Other Cash');

        $this->food = $this->categoryFor($this->user, 'Makan', 'expense', '#ef4444');
        $this->transport = $this->categoryFor($this->user, 'Transport', 'expense', '#3b82f6');
        $this->salary = $this->categoryFor($this->user, 'Gaji', 'income', '#22c55e');
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        auth()->logout();

        $this->get(route('budgets.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_own_budgets_of_the_running_month(): void
    {
        $this->monthlyBudget();

        $this->monthlyBudget([
            'category' => $this->transport,
            'month' => $this->now->copy()->subMonthNoOverflow()->month,
            'year' => $this->now->copy()->subMonthNoOverflow()->year,
        ]);

        $this->yearlyBudget(['category' => $this->transport]);

        $otherCategory = $this->categoryFor($this->otherUser, 'Secret', 'expense');
        Budget::factory()->for($this->otherUser)->create([
            'category_id' => $otherCategory->id,
            'amount_limit' => 500_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ]);

        $response = $this->get(route('budgets.index'))->assertOk();

        $budgets = $response->inertiaProps('budgets');
        $this->assertCount(1, $budgets);
        $this->assertSame('Makan', $budgets[0]['category']['name']);
        $this->assertSame('month', $budgets[0]['period']);

        $response->assertInertia(fn ($page) => $page
            ->component('Budgets/Index')
            ->where('period', 'month')
            ->where('periodLabel', $this->now->copy()->translatedFormat('F Y'))
        );
    }

    public function test_index_computes_progress_from_category_expenses_in_the_period(): void
    {
        $this->monthlyBudget(['amount_limit' => 1_000_000]);
        $this->monthlyBudget(['category' => $this->transport]);

        $this->expense($this->food, 250_000);
        $this->expense($this->food, 50_000, $this->now->copy()->subMonthNoOverflow()->toDateString());
        $this->expense($this->transport, 750_000);

        $budgets = $this->get(route('budgets.index'))->assertOk()->inertiaProps('budgets');

        $this->assertCount(2, $budgets);

        $this->assertSame('Makan', $budgets[0]['category']['name']);
        $this->assertEquals(250_000, $budgets[0]['spent']);
        $this->assertEquals(1_000_000, $budgets[0]['amount_limit']);
        $this->assertEquals(750_000, $budgets[0]['remaining']);
        $this->assertEquals(25, $budgets[0]['percentage']);
        $this->assertSame('safe', $budgets[0]['status']);

        $this->assertSame('Transport', $budgets[1]['category']['name']);
        $this->assertEquals(750_000, $budgets[1]['spent']);
        $this->assertEquals(75, $budgets[1]['percentage']);
        $this->assertSame('warning', $budgets[1]['status']);
    }

    public function test_index_marks_budget_as_over_when_spending_exceeds_the_limit(): void
    {
        $this->monthlyBudget(['amount_limit' => 200_000]);
        $this->expense($this->food, 250_000);

        $budgets = $this->get(route('budgets.index'))->assertOk()->inertiaProps('budgets');

        $this->assertEquals(125, $budgets[0]['percentage']);
        $this->assertSame('over', $budgets[0]['status']);
        $this->assertEquals(-50_000, $budgets[0]['remaining']);
    }

    public function test_index_can_show_yearly_budgets_using_the_year_window(): void
    {
        $this->yearlyBudget(['amount_limit' => 1_000_000]);
        $this->monthlyBudget();

        $this->expense($this->food, 400_000, $this->now->copy()->startOfYear()->toDateString());

        $response = $this->get(route('budgets.index', ['period' => 'year']))->assertOk();

        $budgets = $response->inertiaProps('budgets');
        $this->assertCount(1, $budgets);
        $this->assertSame('year', $budgets[0]['period']);
        $this->assertNull($budgets[0]['month']);
        $this->assertEquals(400_000, $budgets[0]['spent']);
        $this->assertEquals(40, $budgets[0]['percentage']);

        $response->assertInertia(fn ($page) => $page
            ->where('period', 'year')
            ->where('periodLabel', (string) $this->now->year)
        );
    }

    public function test_create_page_only_exposes_expense_categories_of_the_user(): void
    {
        $this->categoryFor($this->otherUser, 'Hidden', 'expense');

        $response = $this->get(route('budgets.create'))->assertOk();

        $categories = $response->inertiaProps('categories');
        $this->assertCount(2, $categories);
        $this->assertSame(['Makan', 'Transport'], array_column($categories, 'name'));

        $response->assertInertia(fn ($page) => $page
            ->where('defaults.month', (int) $this->now->month)
            ->where('defaults.year', (int) $this->now->year)
        );
    }

    public function test_valid_budget_can_be_stored(): void
    {
        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 750_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertRedirect(route('budgets.index'));

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $this->food->id,
            'amount_limit' => 750_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ]);
    }

    public function test_yearly_budget_stores_a_null_month(): void
    {
        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 12_000_000,
            'period' => 'year',
            'month' => 5,
            'year' => $this->now->year,
        ])->assertRedirect(route('budgets.index'));

        $budget = Budget::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame('year', $budget->period);
        $this->assertNull($budget->month);
    }

    public function test_month_is_required_for_a_monthly_budget(): void
    {
        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'year' => $this->now->year,
        ])->assertSessionHasErrors('month');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_duplicate_budget_for_same_category_and_month_is_rejected(): void
    {
        $this->monthlyBudget();

        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 200_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('budgets', 1);
    }

    public function test_duplicate_budget_for_same_category_and_year_is_rejected(): void
    {
        $this->yearlyBudget();

        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 1_000_000,
            'period' => 'year',
            'year' => $this->now->year,
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('budgets', 1);
    }

    public function test_same_category_allows_budgets_in_different_months(): void
    {
        $this->monthlyBudget();

        $previous = $this->now->copy()->subMonthNoOverflow();

        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 300_000,
            'period' => 'month',
            'month' => $previous->month,
            'year' => $previous->year,
        ])->assertRedirect(route('budgets.index'));

        $this->assertDatabaseCount('budgets', 2);
    }

    public function test_same_category_allows_a_month_and_a_year_budget(): void
    {
        $this->monthlyBudget();

        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 6_000_000,
            'period' => 'year',
            'year' => $this->now->year,
        ])->assertRedirect(route('budgets.index'));

        $this->assertDatabaseCount('budgets', 2);
    }

    public function test_category_must_be_an_expense_category(): void
    {
        $this->post(route('budgets.store'), [
            'category_id' => $this->salary->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_cannot_budget_another_users_category(): void
    {
        $otherCategory = $this->categoryFor($this->otherUser, 'Hidden', 'expense');

        $this->post(route('budgets.store'), [
            'category_id' => $otherCategory->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_amount_limit_must_be_greater_than_zero(): void
    {
        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 0,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertSessionHasErrors('amount_limit');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_mass_assignment_user_id_is_ignored(): void
    {
        $this->post(route('budgets.store'), [
            'category_id' => $this->food->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
            'user_id' => $this->otherUser->id,
        ])->assertRedirect(route('budgets.index'));

        $this->assertDatabaseHas('budgets', [
            'category_id' => $this->food->id,
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseMissing('budgets', [
            'category_id' => $this->food->id,
            'user_id' => $this->otherUser->id,
        ]);
    }

    public function test_edit_page_is_accessible(): void
    {
        $budget = $this->monthlyBudget();

        $response = $this->get(route('budgets.edit', $budget->id))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Budgets/Edit')
            ->where('budget.id', $budget->id)
            ->where('budget.period', 'month')
            ->where('budget.month', $this->now->month)
            ->has('categories', 2)
        );
    }

    public function test_budget_can_be_updated(): void
    {
        $budget = $this->monthlyBudget();

        $this->put(route('budgets.update', $budget->id), [
            'category_id' => $this->transport->id,
            'amount_limit' => 2_000_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertRedirect(route('budgets.index'));

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'category_id' => $this->transport->id,
            'amount_limit' => 2_000_000,
        ]);
    }

    public function test_update_ignores_the_budget_being_updated_in_the_uniqueness_check(): void
    {
        $budget = $this->monthlyBudget();

        $this->put(route('budgets.update', $budget->id), [
            'category_id' => $this->food->id,
            'amount_limit' => 1_500_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertRedirect(route('budgets.index'));

        $this->assertDatabaseHas('budgets', ['id' => $budget->id, 'amount_limit' => 1_500_000]);
    }

    public function test_update_rejects_a_duplicate_of_another_budget(): void
    {
        $this->monthlyBudget();

        $transport = $this->monthlyBudget(['category' => $this->transport]);

        $this->put(route('budgets.update', $transport->id), [
            'category_id' => $this->food->id,
            'amount_limit' => 1_000_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseHas('budgets', [
            'id' => $transport->id,
            'category_id' => $this->transport->id,
        ]);
    }

    public function test_cannot_update_another_users_budget(): void
    {
        $otherCategory = $this->categoryFor($this->otherUser, 'Hidden', 'expense');
        $otherBudget = Budget::factory()->for($this->otherUser)->create([
            'category_id' => $otherCategory->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ]);

        $this->put(route('budgets.update', $otherBudget->id), [
            'category_id' => $otherCategory->id,
            'amount_limit' => 999_999,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ])->assertForbidden();

        $this->assertDatabaseHas('budgets', ['id' => $otherBudget->id, 'amount_limit' => 100_000]);
    }

    public function test_cannot_delete_another_users_budget(): void
    {
        $otherCategory = $this->categoryFor($this->otherUser, 'Hidden', 'expense');
        $otherBudget = Budget::factory()->for($this->otherUser)->create([
            'category_id' => $otherCategory->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ]);

        $this->delete(route('budgets.destroy', $otherBudget->id))->assertForbidden();

        $this->assertDatabaseHas('budgets', ['id' => $otherBudget->id]);
    }

    public function test_budget_can_be_deleted(): void
    {
        $budget = $this->monthlyBudget();

        $this->delete(route('budgets.destroy', $budget->id))
            ->assertRedirect(route('budgets.index'));

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    private function accountFor(User $user, string $name): Account
    {
        return Account::factory()->create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'cash',
            'initial_balance' => 0,
        ]);
    }

    private function categoryFor(User $user, string $name, string $type, ?string $color = null): Category
    {
        return Category::factory()->create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => $type,
            'color' => $color,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function monthlyBudget(array $overrides = []): Budget
    {
        $category = $overrides['category'] ?? $this->food;
        unset($overrides['category']);

        return Budget::factory()->for($this->user)->create(array_merge([
            'category_id' => $category->id,
            'amount_limit' => 1_000_000,
            'period' => 'month',
            'month' => $this->now->month,
            'year' => $this->now->year,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function yearlyBudget(array $overrides = []): Budget
    {
        $category = $overrides['category'] ?? $this->food;
        unset($overrides['category']);

        return Budget::factory()->for($this->user)->create(array_merge([
            'category_id' => $category->id,
            'amount_limit' => 12_000_000,
            'period' => 'year',
            'month' => null,
            'year' => $this->now->year,
        ], $overrides));
    }

    private function expense(Category $category, float $amount, ?string $date = null, ?User $user = null): Transaction
    {
        $user ??= $this->user;

        return Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $user->is($this->user) ? $this->account->id : $this->otherAccount->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => $amount,
            'transaction_date' => $date ?? $this->now->toDateString(),
        ]);
    }
}
