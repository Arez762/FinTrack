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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $incomeCategory;

    private Category $foodCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 500_000,
        ]);
        $this->incomeCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Gaji',
            'type' => 'income',
            'color' => '#22c55e',
        ]);
        $this->foodCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Makan',
            'type' => 'expense',
            'color' => '#ef4444',
        ]);
    }

    public function test_dashboard_renders_chart_data(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 1_000_000,
            'transaction_date' => $now->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 250_000,
            'transaction_date' => $now->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 80_000,
            'transaction_date' => $now->copy()->subMonth()->toDateString(),
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('monthLabel', $now->copy()->format('F Y'))
            ->has('monthly', 12)
            ->where('monthly.11.income', 1_000_000)
            ->where('monthly.11.expense', 250_000)
            ->where('monthly.10.expense', 80_000)
            ->where('category_expense.0.name', 'Makan')
            ->where('category_expense.0.color', '#ef4444')
            ->where('category_expense.0.total', 250_000)
            ->has('category_expense', 1)
            ->has('budgets', 0)
        );
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_exposes_budget_alerts_at_or_above_ninety_percent(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();
        $transportCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Transport',
            'type' => 'expense',
            'color' => '#3b82f6',
        ]);

        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->foodCategory->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'month' => $now->month,
            'year' => $now->year,
        ]);
        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $transportCategory->id,
            'amount_limit' => 1_000_000,
            'period' => 'year',
            'month' => null,
            'year' => $now->year,
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 95_000,
            'transaction_date' => $now->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $transportCategory->id,
            'type' => 'expense',
            'amount' => 950_000,
            'transaction_date' => $now->toDateString(),
        ]);

        $otherUser = User::factory()->create();
        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Cash',
            'type' => 'cash',
            'initial_balance' => 0,
        ]);
        $otherCategory = Category::create([
            'user_id' => $otherUser->id,
            'name' => 'Hidden',
            'type' => 'expense',
        ]);
        Budget::create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'month' => $now->month,
            'year' => $now->year,
        ]);
        Transaction::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id,
            'type' => 'expense',
            'amount' => 99_000,
            'transaction_date' => $now->toDateString(),
        ]);

        $response = $this->get(route('dashboard'))->assertOk();

        $alerts = $response->inertiaProps('budget_alerts');

        $this->assertCount(2, $alerts);
        $this->assertSame('Makan', $alerts[0]['category']['name']);
        $this->assertEquals(95, $alerts[0]['percentage']);
        $this->assertSame('warning', $alerts[0]['status']);
        $this->assertSame('Transport', $alerts[1]['category']['name']);
        $this->assertEquals(95, $alerts[1]['percentage']);
    }

    public function test_dashboard_exposes_budget_summary_for_running_period(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();
        $transportCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Transport',
            'type' => 'expense',
            'color' => '#3b82f6',
        ]);

        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->foodCategory->id,
            'amount_limit' => 300_000,
            'period' => 'month',
            'month' => $now->month,
            'year' => $now->year,
        ]);
        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $transportCategory->id,
            'amount_limit' => 1_200_000,
            'period' => 'year',
            'month' => null,
            'year' => $now->year,
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 150_000,
            'transaction_date' => $now->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $transportCategory->id,
            'type' => 'expense',
            'amount' => 960_000,
            'transaction_date' => $now->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 100_000,
            'transaction_date' => $now->copy()->subMonth()->toDateString(),
        ]);

        $response = $this->get(route('dashboard'))->assertOk();

        $budgets = $response->inertiaProps('budgets');

        $this->assertCount(2, $budgets);
        $this->assertSame('Makan', $budgets[0]['category']['name']);
        $this->assertSame('#ef4444', $budgets[0]['category']['color']);
        $this->assertEquals(300_000, $budgets[0]['amount_limit']);
        $this->assertEquals(150_000, $budgets[0]['spent']);
        $this->assertEquals(50, $budgets[0]['percentage']);
        $this->assertSame('safe', $budgets[0]['status']);
        $this->assertSame('month', $budgets[0]['period']);
        $this->assertSame('Transport', $budgets[1]['category']['name']);
        $this->assertEquals(80, $budgets[1]['percentage']);
        $this->assertSame('year', $budgets[1]['period']);
    }

    public function test_dashboard_omits_budget_alerts_below_the_threshold(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        Budget::create([
            'user_id' => $this->user->id,
            'category_id' => $this->foodCategory->id,
            'amount_limit' => 100_000,
            'period' => 'month',
            'month' => $now->month,
            'year' => $now->year,
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 50_000,
            'transaction_date' => $now->toDateString(),
        ]);

        $response = $this->get(route('dashboard'))->assertOk();

        $this->assertCount(0, $response->inertiaProps('budget_alerts'));
    }

    public function test_dashboard_respects_range_filter(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 1_000_000,
            'transaction_date' => $now->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'type' => 'expense',
            'amount' => 250_000,
            'transaction_date' => $now->toDateString(),
        ]);

        $this->get(route('dashboard', ['range' => 'year', 'category_range' => 'year']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('range', 'year')
                ->where('categoryRange', 'year')
                ->has('monthly', 5)
                ->where('monthly.4.income', 1_000_000)
                ->where('monthly.4.expense', 250_000)
                ->where('category_expense.0.total', 250_000)
            );
    }
}
