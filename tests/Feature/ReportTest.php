<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $incomeCategory;

    private Category $foodCategory;

    private Category $transportCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 0,
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
        $this->transportCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Transport',
            'type' => 'expense',
            'color' => '#f97316',
        ]);
    }

    private function createTransaction(
        string $type,
        int $amount,
        Category $category,
        Carbon $date,
    ): void {
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $type === 'transfer' ? null : $category->id,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $date->toDateString(),
        ]);
    }

    public function test_reports_page_renders_with_chart_data(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        $this->createTransaction('income', 1_000_000, $this->incomeCategory, $now->copy());
        $this->createTransaction('expense', 300_000, $this->foodCategory, $now->copy());
        $this->createTransaction('expense', 200_000, $this->transportCategory, $now->copy());
        $this->createTransaction('transfer', 500_000, $this->foodCategory, $now->copy());

        $this->createTransaction('income', 500_000, $this->incomeCategory, $now->copy()->subMonth());
        $this->createTransaction('expense', 150_000, $this->foodCategory, $now->copy()->subMonth());

        $response = $this->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('monthLabel', $now->copy()->format('F Y'))
            ->has('monthly', 12)
            ->where('monthly.11.income', 1_000_000)
            ->where('monthly.11.expense', 500_000)
            ->where('monthly.10.income', 500_000)
            ->where('monthly.10.expense', 150_000)
            ->where('monthly.0.income', 0)
            ->where('monthly.0.expense', 0)
            ->where('monthly.11.period', $now->copy()->format('Y-m'))
            ->where('monthly.11.label', $now->copy()->format('M Y'))
            ->where('categoryExpense.0.name', 'Makan')
            ->where('categoryExpense.0.color', '#ef4444')
            ->where('categoryExpense.0.total', 300_000)
            ->where('categoryExpense.1.name', 'Transport')
            ->where('categoryExpense.1.total', 200_000)
        );
    }

    public function test_reports_monthly_aggregation_covers_last_12_months(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        $this->createTransaction('income', 100_000, $this->incomeCategory, $now->copy()->subMonths(11));
        $this->createTransaction('expense', 40_000, $this->foodCategory, $now->copy()->subMonths(11));

        $response = $this->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->has('monthly', 12)
            ->where('monthly.0.income', 100_000)
            ->where('monthly.0.expense', 40_000)
            ->has('categoryExpense', 0)
        );
    }

    public function test_category_expense_only_groups_current_month_expenses(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        $this->createTransaction('income', 900_000, $this->incomeCategory, $now->copy());

        $this->createTransaction('expense', 75_000, $this->foodCategory, $now->copy());
        $this->createTransaction('expense', 25_000, $this->foodCategory, $now->copy());
        $this->createTransaction('expense', 60_000, $this->transportCategory, $now->copy());
        $this->createTransaction('expense', 999_999, $this->foodCategory, $now->copy()->subMonth());

        $response = $this->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('categoryExpense.0.name', 'Makan')
            ->where('categoryExpense.0.total', 100_000)
            ->where('categoryExpense.1.name', 'Transport')
            ->where('categoryExpense.1.total', 60_000)
            ->has('categoryExpense', 2)
        );
    }

    public function test_reports_page_requires_authentication(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_reports_weekly_range_aggregates_by_week(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        $this->createTransaction('income', 400_000, $this->incomeCategory, $now->copy());
        $this->createTransaction('expense', 25_000, $this->foodCategory, $now->copy());
        $this->createTransaction('expense', 10_000, $this->foodCategory, $now->copy()->subDays(8));

        $response = $this->get(route('reports.index', ['range' => 'week', 'category_range' => 'week']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('range', 'week')
            ->where('categoryRange', 'week')
            ->has('monthly', 12)
            ->where('monthly.11.income', 400_000)
            ->where('monthly.11.expense', 25_000)
            ->where('monthly.10.expense', 10_000)
            ->where('categoryExpense.0.name', 'Makan')
            ->where('categoryExpense.0.total', 25_000)
        );
    }

    public function test_reports_yearly_range_aggregates_by_year(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        $this->createTransaction('income', 500_000, $this->incomeCategory, $now->copy());
        $this->createTransaction('expense', 300_000, $this->foodCategory, $now->copy());
        $this->createTransaction('expense', 100_000, $this->foodCategory, $now->copy()->subYear());

        $response = $this->get(route('reports.index', ['range' => 'year', 'category_range' => 'year']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('range', 'year')
            ->where('categoryRange', 'year')
            ->has('monthly', 5)
            ->where('monthly.4.income', 500_000)
            ->where('monthly.4.expense', 300_000)
            ->where('monthly.3.expense', 100_000)
            ->where('categoryExpense.0.name', 'Makan')
            ->where('categoryExpense.0.total', 300_000)
        );
    }

    public function test_reports_rejects_invalid_range(): void
    {
        $this->actingAs($this->user);

        $this->get(route('reports.index', ['range' => 'decade']))
            ->assertSessionHasErrors('range');

        $this->get(route('reports.index', ['category_range' => 'decade']))
            ->assertSessionHasErrors('category_range');
    }

    public function test_reports_bar_and_donut_ranges_are_independent(): void
    {
        $this->actingAs($this->user);

        $now = Carbon::now();

        $this->createTransaction('income', 400_000, $this->incomeCategory, $now->copy());
        $this->createTransaction('expense', 25_000, $this->foodCategory, $now->copy());
        $this->createTransaction('expense', 10_000, $this->foodCategory, $now->copy()->subDays(8));

        $this->get(route('reports.index', ['range' => 'month', 'category_range' => 'week']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Index')
                ->where('range', 'month')
                ->where('categoryRange', 'week')
                ->where('monthly.11.income', 400_000)
                ->where('monthly.11.expense', 35_000)
                ->where('categoryExpense.0.total', 25_000)
            );

        $this->get(route('reports.index', ['range' => 'week', 'category_range' => 'month']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Index')
                ->where('range', 'week')
                ->where('categoryRange', 'month')
                ->where('monthly.11.expense', 25_000)
                ->where('monthly.10.expense', 10_000)
                ->where('categoryExpense.0.total', 35_000)
            );
    }
}