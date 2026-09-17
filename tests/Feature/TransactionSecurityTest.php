<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Account $account;

    private Account $foreignAccount;

    private Category $incomeCategory;

    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 1_000_000,
        ]);
        $this->foreignAccount = Account::create([
            'user_id' => $this->otherUser->id,
            'name' => 'E-Wallet',
            'type' => 'ewallet',
            'initial_balance' => 0,
        ]);
        $this->incomeCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Gaji',
            'type' => 'income',
            'color' => '#22c55e',
        ]);
        $this->expenseCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Makan',
            'type' => 'expense',
            'color' => '#ef4444',
        ]);

        $this->actingAs($this->user);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 50_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ], $overrides);
    }

    public function test_unauthenticated_index_redirects_to_login(): void
    {
        auth()->logout();

        $this->get(route('transactions.index'))->assertRedirect(route('login'));
    }

    public function test_index_shows_only_own_transactions(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 10_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->otherUser->id,
            'account_id' => $this->foreignAccount->id,
            'type' => 'income',
            'amount' => 999_999,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->get(route('transactions.index'))->assertOk();

        $transactions = $response->inertiaProps('transactions');
        $this->assertEquals(1, $transactions['total']);
        $this->assertEquals(10_000, $transactions['data'][0]['amount']);
    }

    public function test_xss_in_description_and_string_fields_is_stored_safely(): void
    {
        $payload = '<script>alert("hacked")</script><b>bold</b>';

        $this->post(route('transactions.store'), $this->validPayload([
            'description' => $payload,
        ]))->assertRedirect(route('transactions.index'));

        $transaction = Transaction::where('user_id', $this->user->id)->first();
        $this->assertNotNull($transaction);
        $this->assertSame($payload, $transaction->description);

        $transactions = $this->get(route('transactions.index'))->inertiaProps('transactions');
        $this->assertSame($payload, $transactions['data'][0]['description']);
    }

    public function test_mass_assignment_user_id_is_ignored(): void
    {
        $this->post(route('transactions.store'), $this->validPayload([
            'user_id' => $this->otherUser->id,
        ]))->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 50_000,
        ]);
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $this->otherUser->id,
            'amount' => 50_000,
        ]);
    }

    public function test_account_owned_by_another_user_is_rejected(): void
    {
        $this->post(route('transactions.store'), $this->validPayload([
            'account_id' => $this->foreignAccount->id,
        ]))->assertSessionHasErrors('account_id');

        $this->assertDatabaseMissing('transactions', ['account_id' => $this->foreignAccount->id]);
    }

    public function test_amount_sql_injection_string_is_rejected(): void
    {
        $this->post(route('transactions.store'), $this->validPayload([
            'amount' => '50; DROP TABLE transactions; --',
        ]))->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    public function test_filter_account_id_sql_injection_is_rejected(): void
    {
        $this->get(route('transactions.index', ['account_id' => '1 OR 1=1']))
            ->assertSessionHasErrors('account_id');
    }

    public function test_filter_date_sql_injection_is_rejected(): void
    {
        $this->get(route('transactions.index', ['date_from' => "2026-01-01' OR '1'='1"]))
            ->assertSessionHasErrors('date_from');
    }

    public function test_filter_with_foreign_account_id_is_rejected(): void
    {
        $this->get(route('transactions.index', ['account_id' => $this->foreignAccount->id]))
            ->assertSessionHasErrors('account_id');
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->post(route('transactions.store'), $this->validPayload([
            'type' => '<script>alert(1)</script>',
        ]))->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    public function test_nonexistent_ids_via_route_binding_returns_404(): void
    {
        $this->deleteJson(route('transactions.destroy', 999_999))->assertNotFound();
    }

    public function test_description_over_max_length_is_rejected(): void
    {
        $this->post(route('transactions.store'), $this->validPayload([
            'description' => str_repeat('a', 1001),
        ]))->assertSessionHasErrors('description');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    public function test_filter_by_type_returns_only_matching_transactions(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 100_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 40_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 25_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $transactions = $this->get(route('transactions.index', ['type' => 'income']))
            ->assertOk()
            ->inertiaProps('transactions');

        $this->assertEquals(1, $transactions['total']);
        $this->assertEquals('income', $transactions['data'][0]['type']);
    }

    public function test_invalid_type_filter_is_rejected(): void
    {
        $this->get(route('transactions.index', ['type' => '<script>alert(1)</script>']))
            ->assertSessionHasErrors('type');
    }

    public function test_totals_reflect_active_filters(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 100_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 40_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 25_000,
            'transaction_date' => Carbon::now()->subDays(10)->toDateString(),
        ]);

        // Tanpa filter: income 100.000, expense 65.000
        $totals = $this->get(route('transactions.index'))->inertiaProps('totals');
        $this->assertEquals(100_000, $totals['income']);
        $this->assertEquals(65_000, $totals['expense']);

        // Filter kategori expense: income 0, expense 65.000
        $totals = $this->get(route('transactions.index', ['category_id' => $this->expenseCategory->id]))
            ->inertiaProps('totals');
        $this->assertEquals(0, $totals['income']);
        $this->assertEquals(65_000, $totals['expense']);

        // Filter rentang 7 hari terakhir: hanya transaksi hari ini (income 100.000, expense 40.000)
        $totals = $this->get(route('transactions.index', [
            'date_from' => Carbon::now()->subDays(7)->toDateString(),
            'date_to' => Carbon::now()->toDateString(),
        ]))->inertiaProps('totals');
        $this->assertEquals(100_000, $totals['income']);
        $this->assertEquals(40_000, $totals['expense']);

        // Filter type income: expense selalu 0
        $totals = $this->get(route('transactions.index', ['type' => 'income']))->inertiaProps('totals');
        $this->assertEquals(100_000, $totals['income']);
        $this->assertEquals(0, $totals['expense']);
    }
}
