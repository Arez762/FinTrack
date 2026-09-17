<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $source;

    private Account $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->source = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 1_000_000,
        ]);
        $this->destination = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Bank Account',
            'type' => 'bank',
            'initial_balance' => 0,
        ]);

        $this->actingAs($this->user);
    }

    public function test_transfer_pages_require_authentication(): void
    {
        auth()->logout();

        $this->get(route('transfers.index'))->assertRedirect(route('login'));
        $this->get(route('transfers.create'))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_transfers_with_both_accounts(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Makan',
            'type' => 'expense',
            'color' => '#ef4444',
        ]);

        $transfer = Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->source->id,
            'transfer_to_account_id' => $this->destination->id,
            'type' => 'transfer',
            'amount' => 200_000,
            'description' => 'Top up',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->source->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->get(route('transfers.index'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Transfers/Index')
            ->has('transfers.data', 1)
            ->where('transfers.data.0.id', $transfer->id)
            ->where('transfers.data.0.amount', 200_000)
            ->where('transfers.data.0.account.name', 'Cash')
            ->where('transfers.data.0.transfer_to_account.name', 'Bank Account')
        );
    }

    public function test_create_page_exposes_accounts_and_preselects_from_query(): void
    {
        $response = $this->get(route('transfers.create', ['from' => $this->destination->id]))
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Transfers/Create')
            ->has('accounts', 2)
            ->where('defaults.account_id', $this->destination->id)
        );
    }

    public function test_create_page_ignores_from_query_for_other_users_account(): void
    {
        $other = User::factory()->create();
        $otherAccount = Account::create([
            'user_id' => $other->id,
            'name' => 'Secret',
            'type' => 'cash',
            'initial_balance' => 0,
        ]);

        $response = $this->get(route('transfers.create', ['from' => $otherAccount->id]))
            ->assertOk();

        $response->assertInertia(fn ($page) => $page->where('defaults.account_id', null));
    }

    public function test_valid_transfer_is_stored_as_single_transaction(): void
    {
        $response = $this->post(route('transfers.store'), [
            'account_id' => $this->source->id,
            'transfer_to_account_id' => $this->destination->id,
            'amount' => 200_000,
            'description' => 'Top up e-wallet',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response->assertRedirect(route('transfers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'transfer',
            'account_id' => $this->source->id,
            'transfer_to_account_id' => $this->destination->id,
            'category_id' => null,
            'amount' => 200_000,
        ]);
    }

    public function test_transfer_to_same_account_is_rejected(): void
    {
        $this->post(route('transfers.store'), [
            'account_id' => $this->source->id,
            'transfer_to_account_id' => $this->source->id,
            'amount' => 200_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ])->assertSessionHasErrors('transfer_to_account_id');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    public function test_transfer_to_other_users_account_is_rejected(): void
    {
        $other = User::factory()->create();
        $otherAccount = Account::create([
            'user_id' => $other->id,
            'name' => 'Secret',
            'type' => 'cash',
            'initial_balance' => 0,
        ]);

        $this->post(route('transfers.store'), [
            'account_id' => $this->source->id,
            'transfer_to_account_id' => $otherAccount->id,
            'amount' => 200_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ])->assertSessionHasErrors('transfer_to_account_id');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    public function test_transfer_with_zero_amount_is_rejected(): void
    {
        $this->post(route('transfers.store'), [
            'account_id' => $this->source->id,
            'transfer_to_account_id' => $this->destination->id,
            'amount' => 0,
            'transaction_date' => Carbon::now()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    public function test_account_balances_move_money_from_source_to_destination(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->source->id,
            'transfer_to_account_id' => $this->destination->id,
            'type' => 'transfer',
            'amount' => 200_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $accounts = collect($this->get(route('accounts.index'))->assertOk()->inertiaProps('accounts'))
            ->keyBy('name');

        $this->assertEquals(800_000, $accounts['Cash']['balance']);
        $this->assertEquals(200_000, $accounts['Bank Account']['balance']);
    }

    public function test_dashboard_total_balance_is_unaffected_by_transfers(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->source->id,
            'transfer_to_account_id' => $this->destination->id,
            'type' => 'transfer',
            'amount' => 200_000,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->get(route('dashboard'))->assertOk();

        $this->assertEquals(1_000_000, $response->inertiaProps('summary')['total_balance']);
        $this->assertEquals(0, $response->inertiaProps('summary')['month_expense']);
        $this->assertEquals(0, $response->inertiaProps('summary')['month_income']);
    }
}
