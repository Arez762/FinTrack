<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private Account $account;

    private Account $otherAccount;

    private Category $expenseCategory;

    private Category $incomeCategory;

    private Carbon $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->actingAs($this->user);

        $this->today = Carbon::today();

        $this->account = $this->accountFor($this->user, 'Cash');
        $this->otherAccount = $this->accountFor($this->otherUser, 'Other Cash');

        $this->expenseCategory = $this->categoryFor($this->user, 'Makan', 'expense', '#ef4444');
        $this->incomeCategory = $this->categoryFor($this->user, 'Gaji', 'income', '#22c55e');
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        auth()->logout();

        $this->get(route('recurring-transactions.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_own_templates_with_active_first(): void
    {
        $paused = $this->templateFor([
            'is_active' => false,
            'description' => 'Streaming',
        ]);

        $active = $this->templateFor([
            'description' => 'Gaji bulanan',
            'next_run_date' => $this->today->copy()->addDays(3)->toDateString(),
        ]);

        $otherCategory = $this->categoryFor($this->otherUser, 'Hidden', 'expense');
        $this->otherUser->recurringTransactions()->create([
            'account_id' => $this->otherAccount->id,
            'category_id' => $otherCategory->id,
            'type' => 'expense',
            'amount' => 500_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
            'next_run_date' => $this->today->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->get(route('recurring-transactions.index'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('RecurringTransactions/Index')
            ->has('recurringTransactions', 2)
        );

        $items = $response->inertiaProps('recurringTransactions');

        $this->assertSame($active->id, $items[0]['id']);
        $this->assertSame($paused->id, $items[1]['id']);
        $this->assertTrue($items[0]['is_active']);
        $this->assertFalse($items[1]['is_active']);
        $this->assertSame('monthly', $items[0]['frequency']);
        $this->assertSame($this->today->copy()->addDays(3)->toDateString(), $items[0]['next_run_date']);
        $this->assertSame('Cash', $items[0]['account']['name']);
        $this->assertSame('Makan', $items[0]['category']['name']);
        $this->assertEquals(1_000_000, $items[0]['amount']);
    }

    public function test_index_does_not_expose_other_users_templates(): void
    {
        $otherCategory = $this->categoryFor($this->otherUser, 'Hidden', 'expense');
        $this->otherUser->recurringTransactions()->create([
            'account_id' => $this->otherAccount->id,
            'category_id' => $otherCategory->id,
            'type' => 'expense',
            'amount' => 500_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
            'next_run_date' => $this->today->toDateString(),
            'is_active' => true,
        ]);

        $items = $this->get(route('recurring-transactions.index'))
            ->assertOk()
            ->inertiaProps('recurringTransactions');

        $this->assertCount(0, $items);
    }

    public function test_create_page_exposes_accounts_and_categories_of_the_user(): void
    {
        $this->categoryFor($this->otherUser, 'Hidden', 'expense');

        $response = $this->get(route('recurring-transactions.create'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('RecurringTransactions/Create')
            ->where('defaults.start_date', $this->today->toDateString())
            ->has('accounts', 1)
            ->has('categories', 2)
        );

        $categories = $response->inertiaProps('categories');
        $this->assertSame(['Gaji', 'Makan'], array_column($categories, 'name'));
    }

    public function test_recurring_transaction_can_be_stored(): void
    {
        $this->post(route('recurring-transactions.store'), [
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 350_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
            'description' => 'Tagihan listrik',
        ])->assertRedirect(route('recurring-transactions.index'));

        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 350_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateTimeString(),
            'next_run_date' => $this->today->toDateTimeString(),
            'is_active' => true,
        ]);
    }

    public function test_category_must_match_the_selected_type(): void
    {
        $this->post(route('recurring-transactions.store'), [
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'expense',
            'amount' => 100_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('recurring_transactions', 0);
    }

    public function test_cannot_use_another_users_account_or_category(): void
    {
        $otherCategory = $this->categoryFor($this->otherUser, 'Hidden', 'expense');

        $this->post(route('recurring-transactions.store'), [
            'account_id' => $this->otherAccount->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 100_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
        ])->assertSessionHasErrors('account_id');

        $this->post(route('recurring-transactions.store'), [
            'account_id' => $this->account->id,
            'category_id' => $otherCategory->id,
            'type' => 'expense',
            'amount' => 100_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('recurring_transactions', 0);
    }

    public function test_amount_must_be_greater_than_zero(): void
    {
        $this->post(route('recurring-transactions.store'), [
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 0,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
        ])->assertSessionHasErrors('amount');
    }

    public function test_frequency_must_be_one_of_the_supported_values(): void
    {
        $this->post(route('recurring-transactions.store'), [
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 100_000,
            'frequency' => 'hourly',
            'start_date' => $this->today->toDateString(),
        ])->assertSessionHasErrors('frequency');
    }

    public function test_mass_assignment_user_id_is_ignored(): void
    {
        $this->post(route('recurring-transactions.store'), [
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 100_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
            'user_id' => $this->otherUser->id,
        ])->assertRedirect(route('recurring-transactions.index'));

        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $this->user->id,
            'category_id' => $this->expenseCategory->id,
        ]);
        $this->assertDatabaseMissing('recurring_transactions', [
            'user_id' => $this->otherUser->id,
        ]);
    }

    public function test_edit_page_is_accessible(): void
    {
        $template = $this->templateFor(['description' => 'Tagihan']);

        $response = $this->get(route('recurring-transactions.edit', $template->id))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('RecurringTransactions/Edit')
            ->where('recurringTransaction.id', $template->id)
            ->where('recurringTransaction.frequency', 'monthly')
            ->where('recurringTransaction.is_active', true)
            ->has('accounts', 1)
            ->has('categories', 2)
        );
    }

    public function test_cannot_view_the_edit_page_of_another_users_template(): void
    {
        $template = $this->otherTemplate();

        $this->get(route('recurring-transactions.edit', $template->id))->assertForbidden();
    }

    public function test_recurring_transaction_can_be_updated(): void
    {
        $template = $this->templateFor();

        $this->put(route('recurring-transactions.update', $template->id), [
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 8_000_000,
            'frequency' => 'weekly',
            'start_date' => $this->today->toDateString(),
            'description' => 'Gaji mingguan',
        ])->assertRedirect(route('recurring-transactions.index'));

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $template->id,
            'category_id' => $this->incomeCategory->id,
            'type' => 'income',
            'amount' => 8_000_000,
            'frequency' => 'weekly',
        ]);
    }

    public function test_cannot_update_another_users_template(): void
    {
        $template = $this->otherTemplate();

        $this->put(route('recurring-transactions.update', $template->id), [
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 999_999,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
        ])->assertForbidden();

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $template->id,
            'amount' => 400_000,
        ]);
    }

    public function test_recurring_transaction_can_be_deleted(): void
    {
        $template = $this->templateFor();

        $this->delete(route('recurring-transactions.destroy', $template->id))
            ->assertRedirect(route('recurring-transactions.index'));

        $this->assertDatabaseMissing('recurring_transactions', ['id' => $template->id]);
    }

    public function test_cannot_delete_another_users_template(): void
    {
        $template = $this->otherTemplate();

        $this->delete(route('recurring-transactions.destroy', $template->id))->assertForbidden();

        $this->assertDatabaseHas('recurring_transactions', ['id' => $template->id]);
    }

    public function test_template_can_be_paused_and_resumed(): void
    {
        $template = $this->templateFor(['is_active' => true]);

        $this->patch(route('recurring-transactions.toggle', $template->id))
            ->assertRedirect(route('recurring-transactions.index'));

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $template->id,
            'is_active' => false,
        ]);

        $this->patch(route('recurring-transactions.toggle', $template->id))
            ->assertRedirect(route('recurring-transactions.index'));

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $template->id,
            'is_active' => true,
        ]);
    }

    public function test_pausing_a_template_does_not_delete_it(): void
    {
        $template = $this->templateFor();

        $this->patch(route('recurring-transactions.toggle', $template->id));

        $this->assertDatabaseCount('recurring_transactions', 1);
        $this->assertSame('monthly', $template->fresh()->frequency);
    }

    public function test_cannot_toggle_another_users_template(): void
    {
        $template = $this->otherTemplate();

        $this->patch(route('recurring-transactions.toggle', $template->id))->assertForbidden();

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $template->id,
            'is_active' => true,
        ]);
    }

    private function accountFor(User $user, string $name): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'cash',
            'initial_balance' => 0,
        ]);
    }

    private function categoryFor(User $user, string $name, string $type, ?string $color = null): Category
    {
        return Category::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => $type,
            'color' => $color,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function templateFor(array $overrides = []): RecurringTransaction
    {
        return $this->user->recurringTransactions()->create(array_merge([
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 1_000_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
            'next_run_date' => $this->today->toDateString(),
            'is_active' => true,
        ], $overrides));
    }

    private function otherTemplate(): RecurringTransaction
    {
        $category = $this->categoryFor($this->otherUser, 'Hidden', 'expense');

        return $this->otherUser->recurringTransactions()->create([
            'account_id' => $this->otherAccount->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 400_000,
            'frequency' => 'monthly',
            'start_date' => $this->today->toDateString(),
            'next_run_date' => $this->today->toDateString(),
            'is_active' => true,
        ]);
    }
}
