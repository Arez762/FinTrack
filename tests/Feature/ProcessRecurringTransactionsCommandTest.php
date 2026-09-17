<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProcessRecurringTransactionsCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-17 09:00:00'));

        $this->user = User::factory()->create();

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 0,
        ]);

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Tagihan',
            'type' => 'expense',
            'color' => '#3b82f6',
        ]);
    }

    public function test_it_creates_a_transaction_for_a_due_template_and_advances_the_schedule(): void
    {
        $template = $this->templateFor([
            'frequency' => 'daily',
            'next_run_date' => '2026-09-17',
            'description' => 'Bayar langganan',
        ]);

        $this->artisan('transactions:process-recurring')
            ->expectsOutputToContain('created 1 transaction(s)')
            ->assertSuccessful();

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 1_000_000,
            'description' => 'Bayar langganan',
            'transaction_date' => '2026-09-17 00:00:00',
        ]);

        $this->assertSame('2026-09-18', $template->fresh()->next_run_date->toDateString());
    }

    public function test_it_advances_the_next_run_date_according_to_each_frequency(): void
    {
        $weekly = $this->templateFor(['frequency' => 'weekly']);
        $monthly = $this->templateFor(['frequency' => 'monthly']);
        $yearly = $this->templateFor(['frequency' => 'yearly']);

        $this->artisan('transactions:process-recurring')->assertSuccessful();

        $this->assertSame('2026-09-24', $weekly->fresh()->next_run_date->toDateString());
        $this->assertSame('2026-10-17', $monthly->fresh()->next_run_date->toDateString());
        $this->assertSame('2027-09-17', $yearly->fresh()->next_run_date->toDateString());
        $this->assertDatabaseCount('transactions', 3);
    }

    public function test_it_uses_no_overflow_when_advancing_monthly_templates(): void
    {
        $this->travelTo(Carbon::parse('2026-01-31 09:00:00'));

        $template = $this->templateFor(['next_run_date' => '2026-01-31']);

        $this->artisan('transactions:process-recurring')->assertSuccessful();

        $this->assertSame('2026-02-28', $template->fresh()->next_run_date->toDateString());
    }

    public function test_it_skips_paused_templates(): void
    {
        $template = $this->templateFor(['is_active' => false]);

        $this->artisan('transactions:process-recurring')->assertSuccessful();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('2026-09-17', $template->fresh()->next_run_date->toDateString());
    }

    public function test_it_skips_templates_that_are_not_due_yet(): void
    {
        $this->templateFor(['next_run_date' => '2026-09-18']);

        $this->artisan('transactions:process-recurring')->assertSuccessful();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_it_catches_up_every_missed_occurrence(): void
    {
        $template = $this->templateFor([
            'frequency' => 'daily',
            'next_run_date' => '2026-09-14',
        ]);

        $this->artisan('transactions:process-recurring')->assertSuccessful();

        $dates = Transaction::orderBy('transaction_date')
            ->pluck('transaction_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $this->assertSame(['2026-09-14', '2026-09-15', '2026-09-16', '2026-09-17'], $dates);
        $this->assertSame('2026-09-18', $template->fresh()->next_run_date->toDateString());
    }

    public function test_it_processes_templates_of_every_user(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Cash',
            'type' => 'cash',
            'initial_balance' => 0,
        ]);
        $otherCategory = Category::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Tagihan',
            'type' => 'expense',
            'color' => '#ef4444',
        ]);

        $this->templateFor();

        $otherUser->recurringTransactions()->create([
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id,
            'type' => 'expense',
            'amount' => 250_000,
            'frequency' => 'monthly',
            'start_date' => '2026-09-17',
            'next_run_date' => '2026-09-17',
            'is_active' => true,
        ]);

        $this->artisan('transactions:process-recurring')->assertSuccessful();

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', ['user_id' => $this->user->id]);
        $this->assertDatabaseHas('transactions', ['user_id' => $otherUser->id, 'amount' => 250_000]);
    }

    public function test_it_does_nothing_when_no_template_is_due(): void
    {
        $this->templateFor(['is_active' => false]);
        $this->templateFor(['next_run_date' => '2026-12-31']);

        $this->artisan('transactions:process-recurring')
            ->expectsOutputToContain('created 0 transaction(s)')
            ->assertSuccessful();

        $this->assertDatabaseCount('transactions', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function templateFor(array $overrides = []): RecurringTransaction
    {
        return $this->user->recurringTransactions()->create(array_merge([
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 1_000_000,
            'frequency' => 'monthly',
            'start_date' => '2026-09-17',
            'next_run_date' => '2026-09-17',
            'is_active' => true,
        ], $overrides));
    }
}
