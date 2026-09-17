<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $incomeCategory;

    private Category $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 1_000_000,
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

    /**
     * Skenario 1: income valid dengan kategori income.
     */
    public function test_valid_income_is_stored(): void
    {
        $data = [
            'type' => 'income',
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 500_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('transactions.store'), $data);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'income',
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 500_000,
        ]);
    }

    /**
     * Skenario 2: expense valid dengan kategori expense.
     */
    public function test_valid_expense_is_stored(): void
    {
        $data = [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 75_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 75_000,
            'category_id' => $this->expenseCategory->id,
        ]);
    }

    /**
     * Skenario 3: transfer valid tanpa kategori.
     */
    public function test_valid_transfer_is_stored_without_category(): void
    {
        $destination = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Bank',
            'type' => 'bank',
            'initial_balance' => 0,
        ]);

        $data = [
            'type' => 'transfer',
            'account_id' => $this->account->id,
            'transfer_to_account_id' => $destination->id,
            'amount' => 200_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'transfer',
            'amount' => 200_000,
            'category_id' => null,
            'transfer_to_account_id' => $destination->id,
        ]);
    }

    /**
     * Skenario 3b: transfer tanpa akun tujuan ditolak.
     */
    public function test_transfer_without_destination_account_is_rejected(): void
    {
        $data = [
            'type' => 'transfer',
            'account_id' => $this->account->id,
            'amount' => 200_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertSessionHasErrors('transfer_to_account_id');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    /**
     * Skenario 3c: transfer ke akun yang sama ditolak.
     */
    public function test_transfer_to_the_same_account_is_rejected(): void
    {
        $data = [
            'type' => 'transfer',
            'account_id' => $this->account->id,
            'transfer_to_account_id' => $this->account->id,
            'amount' => 200_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertSessionHasErrors('transfer_to_account_id');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    /**
     * Skenario 3d: destination account diabaikan untuk transaksi non-transfer.
     */
    public function test_destination_account_is_ignored_for_non_transfer(): void
    {
        $destination = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Bank',
            'type' => 'bank',
            'initial_balance' => 0,
        ]);

        $data = [
            'type' => 'income',
            'account_id' => $this->account->id,
            'transfer_to_account_id' => $destination->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 100_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'income',
            'transfer_to_account_id' => null,
        ]);
    }

    /**
     * Skenario 4: expense valid dengan tanggal lama + description.
     */
    public function test_valid_expense_with_description_and_past_date(): void
    {
        $data = [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 15_500,
            'description' => 'Nasi goreng + es teh',
            'transaction_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'description' => 'Nasi goreng + es teh',
            'amount' => 15_500,
        ]);
    }

    /**
     * Skenario 5: income valid dengan nominal besar.
     */
    public function test_valid_income_with_large_amount(): void
    {
        $data = [
            'type' => 'income',
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 99_999_999,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 99_999_999,
        ]);
    }

    /**
     * Skenario 6: expense amount 0 ditolak.
     */
    public function test_expense_with_zero_amount_is_rejected(): void
    {
        $data = [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 0,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    /**
     * Skenario 7: expense amount negatif ditolak.
     */
    public function test_expense_with_negative_amount_is_rejected(): void
    {
        $data = [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => -10_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    /**
     * Skenario 8: expense dengan tanggal besok (future) ditolak.
     */
    public function test_expense_with_future_date_is_rejected(): void
    {
        $data = [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 50_000,
            'transaction_date' => Carbon::now()->addDay()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertSessionHasErrors('transaction_date');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    /**
     * Skenario 9: income memakai kategori expense ditolak (tipe tidak cocok).
     */
    public function test_income_with_expense_category_is_rejected(): void
    {
        $data = [
            'type' => 'income',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 100_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    /**
     * Skenario 10: income tanpa kategori ditolak.
     */
    public function test_income_without_category_is_rejected(): void
    {
        $data = [
            'type' => 'income',
            'account_id' => $this->account->id,
            'amount' => 100_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('transactions.store'), $data)
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
    }

    /**
     * Skenario 11: update transaksi milik sendiri (jalur authorize) berhasil.
     */
    public function test_user_can_update_own_transaction(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 25_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $data = [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 40_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->patch(route('transactions.update', $transaction->id), $data)
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 40_000,
        ]);
    }

    /**
     * Skenario 12: update transaksi milik user lain ditolak (403, jalur authorize).
     */
    public function test_user_cannot_update_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => 'Cash',
            'type' => 'cash',
            'initial_balance' => 0,
        ]);
        $transaction = Transaction::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'type' => 'expense',
            'amount' => 10_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $data = [
            'type' => 'expense',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'amount' => 99_000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->patch(route('transactions.update', $transaction->id), $data)
            ->assertForbidden();

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
            'amount' => 99_000,
        ]);
    }

    /**
     * Skenario 13: halaman 2 pagination tetap menghitung totals (regresi: paginate
     * memutasi builder sehingga query totals pada page 2 menghasilkan null).
     */
    public function test_index_page_2_still_returns_totals(): void
    {
        for ($i = 0; $i < 12; $i++) {
            Transaction::create([
                'user_id' => $this->user->id,
                'account_id' => $this->account->id,
                'category_id' => $this->expenseCategory->id,
                'type' => 'expense',
                'amount' => 10_000,
                'transaction_date' => Carbon::now()->subDays($i)->format('Y-m-d'),
            ]);
        }

        $response = $this->get(route('transactions.index', ['page' => 2]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Transactions/Index')
            ->where('transactions.current_page', 2)
            ->where('transactions.total', 12)
            ->where('totals.expense', 120_000)
            ->where('totals.income', 0)
        );
    }
}
