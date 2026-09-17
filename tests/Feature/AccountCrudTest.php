<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCrudTest extends TestCase
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

        $this->get(route('accounts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_lists_only_own_accounts_with_computed_balance(): void
    {
        Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 1_000_000,
        ]);
        Account::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Secret Wallet',
            'type' => 'ewallet',
            'initial_balance' => 999_999,
        ]);

        $response = $this->get(route('accounts.index'))->assertOk();

        $accounts = $response->inertiaProps('accounts');
        $this->assertCount(1, $accounts);
        $this->assertEquals('Cash', $accounts[0]['name']);
        $this->assertEquals(1_000_000, $accounts[0]['balance']);
    }

    public function test_balance_includes_transactions_signed_by_type(): void
    {
        $account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 1_000_000,
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 500_000,
            'transaction_date' => now()->toDateString(),
        ]);
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 200_000,
            'transaction_date' => now()->toDateString(),
        ]);

        $accounts = $this->get(route('accounts.index'))->assertOk()->inertiaProps('accounts');

        $this->assertEquals(1_300_000, $accounts[0]['balance']);
    }

    public function test_create_page_is_accessible(): void
    {
        $this->get(route('accounts.create'))->assertOk();
    }

    public function test_valid_account_can_be_stored(): void
    {
        $this->post(route('accounts.store'), [
            'name' => 'Bank BCA',
            'type' => 'bank',
            'initial_balance' => 250_000,
        ])->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'user_id' => $this->user->id,
            'name' => 'Bank BCA',
            'type' => 'bank',
            'initial_balance' => 250_000,
        ]);
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->post(route('accounts.store'), [
            'name' => 'Bad',
            'type' => 'bitcoin',
            'initial_balance' => 0,
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('accounts', ['name' => 'Bad']);
    }

    public function test_negative_initial_balance_is_rejected(): void
    {
        $this->post(route('accounts.store'), [
            'name' => 'Bad',
            'type' => 'cash',
            'initial_balance' => -50,
        ])->assertSessionHasErrors('initial_balance');

        $this->assertDatabaseMissing('accounts', ['name' => 'Bad']);
    }

    public function test_mass_assignment_user_id_is_ignored(): void
    {
        $this->post(route('accounts.store'), [
            'name' => 'Hijacked',
            'type' => 'cash',
            'initial_balance' => 0,
            'user_id' => $this->otherUser->id,
        ])->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'name' => 'Hijacked',
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseMissing('accounts', [
            'name' => 'Hijacked',
            'user_id' => $this->otherUser->id,
        ]);
    }

    public function test_edit_page_is_accessible(): void
    {
        $account = $this->user->accounts()->create(['name' => 'Cash', 'type' => 'cash', 'initial_balance' => 0]);

        $this->get(route('accounts.edit', $account->id))->assertOk();
    }

    public function test_account_can_be_updated(): void
    {
        $account = $this->user->accounts()->create(['name' => 'Cash', 'type' => 'cash', 'initial_balance' => 0]);

        $this->put(route('accounts.update', $account->id), [
            'name' => 'Cash Baru',
            'type' => 'bank',
            'initial_balance' => 300_000,
        ])->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Cash Baru',
            'type' => 'bank',
            'initial_balance' => 300_000,
        ]);
    }

    public function test_account_can_be_deleted(): void
    {
        $account = $this->user->accounts()->create(['name' => 'Temp', 'type' => 'cash', 'initial_balance' => 0]);

        $this->delete(route('accounts.destroy', $account->id))
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_cannot_update_another_users_account(): void
    {
        $otherAccount = Account::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Milk',
            'type' => 'cash',
            'initial_balance' => 0,
        ]);

        $this->put(route('accounts.update', $otherAccount->id), [
            'name' => 'Hacked',
            'type' => 'cash',
            'initial_balance' => 0,
        ])->assertForbidden();

        $this->assertDatabaseHas('accounts', ['id' => $otherAccount->id, 'name' => 'Milk']);
    }

    public function test_cannot_delete_another_users_account(): void
    {
        $otherAccount = Account::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Milk',
            'type' => 'cash',
            'initial_balance' => 0,
        ]);

        $this->delete(route('accounts.destroy', $otherAccount->id))->assertForbidden();

        $this->assertDatabaseHas('accounts', ['id' => $otherAccount->id]);
    }

    public function test_xss_script_in_name_is_stored_escaped_safely(): void
    {
        $payload = '<script>alert(document.cookie)</script>';

        $this->post(route('accounts.store'), [
            'name' => $payload,
            'type' => 'cash',
            'initial_balance' => 0,
        ])->assertRedirect(route('accounts.index'));

        $account = Account::where('user_id', $this->user->id)->first();
        $this->assertNotNull($account);
        $this->assertSame($payload, $account->name);

        $accounts = $this->get(route('accounts.index'))->assertOk()->inertiaProps('accounts');
        $this->assertStringContainsString('<script>', $accounts[0]['name']);
    }
}