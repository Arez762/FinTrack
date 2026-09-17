<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionExportTest extends TestCase
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
    }

    private function transaction(array $attributes = []): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
            'type' => 'expense',
            'amount' => 25_000,
            'description' => 'Kopi pagi',
            'transaction_date' => '2026-01-20',
        ], $attributes));
    }

    public function test_exports_require_authentication(): void
    {
        $this->get(route('transactions.export.csv'))->assertRedirect(route('login'));
        $this->get(route('transactions.export.pdf'))->assertRedirect(route('login'));
    }

    public function test_csv_export_contains_only_authenticated_users_transactions(): void
    {
        $this->actingAs($this->user);
        $this->transaction(['description' => 'Kopi pagi']);

        $otherUser = User::factory()->create();
        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => 'Bank Lain',
            'type' => 'bank',
            'initial_balance' => 0,
        ]);
        Transaction::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'type' => 'expense',
            'amount' => 999_000,
            'description' => 'Rahasia orang lain',
            'transaction_date' => '2026-01-20',
        ]);

        $response = $this->get(route('transactions.export.csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Date,Account,Category,Type,Amount,Description', $csv);
        $this->assertStringContainsString('Kopi pagi', $csv);
        $this->assertStringContainsString('Cash', $csv);
        $this->assertStringContainsString('25000.00', $csv);
        $this->assertStringNotContainsString('Rahasia orang lain', $csv);
        $this->assertStringNotContainsString('Bank Lain', $csv);
        $this->assertStringNotContainsString('999000.00', $csv);
    }

    public function test_csv_export_applies_active_filters(): void
    {
        $this->actingAs($this->user);

        $this->transaction([
            'type' => 'income',
            'category_id' => $this->incomeCategory->id,
            'amount' => 500_000,
            'description' => 'Gaji bulanan',
            'transaction_date' => '2026-01-10',
        ]);
        $this->transaction([
            'type' => 'expense',
            'description' => 'Makan siang',
            'transaction_date' => '2026-01-20',
        ]);

        $byType = $this->get(route('transactions.export.csv', ['type' => 'expense']))
            ->streamedContent();
        $this->assertStringContainsString('Makan siang', $byType);
        $this->assertStringNotContainsString('Gaji bulanan', $byType);

        $byDate = $this->get(route('transactions.export.csv', [
            'date_from' => '2026-01-15',
            'date_to' => '2026-01-31',
        ]))->streamedContent();
        $this->assertStringContainsString('Makan siang', $byDate);
        $this->assertStringNotContainsString('Gaji bulanan', $byDate);
    }

    public function test_csv_filename_uses_applied_date_range(): void
    {
        $this->actingAs($this->user);
        $this->transaction();

        $this->get(route('transactions.export.csv', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]))->assertDownload('fintrack-transactions-2026-01-01-to-2026-01-31.csv');
    }

    public function test_csv_filename_falls_back_to_the_data_date_span(): void
    {
        $this->actingAs($this->user);
        $this->transaction(['transaction_date' => '2026-05-10']);

        $this->get(route('transactions.export.csv'))
            ->assertDownload('fintrack-transactions-2026-05-10-to-2026-05-10.csv');
    }

    public function test_csv_filename_uses_today_when_there_is_no_data(): void
    {
        $this->actingAs($this->user);

        $this->get(route('transactions.export.csv'))
            ->assertDownload(sprintf(
                'fintrack-transactions-%s-to-%s.csv',
                now()->toDateString(),
                now()->toDateString(),
            ));
    }

    public function test_pdf_export_returns_a_pdf_download(): void
    {
        $this->actingAs($this->user);
        $this->transaction();

        $response = $this->get(route('transactions.export.pdf', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('fintrack-transactions-2026-01-01-to-2026-01-31.pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_report_renders_summary_period_and_rows(): void
    {
        $html = view('exports.transactions-pdf', [
            'transactions' => collect([
                [
                    'date' => '2026-01-20',
                    'account' => 'Cash',
                    'category' => 'Makan',
                    'type' => 'expense',
                    'amount' => 25_000.0,
                    'description' => 'Kopi pagi',
                ],
            ]),
            'totalIncome' => 500_000.0,
            'totalExpense' => 25_000.0,
            'period' => ['from' => '2026-01-01', 'to' => '2026-01-31'],
            'generatedAt' => Carbon::parse('2026-02-01 10:00:00'),
        ])->render();

        $this->assertStringContainsString('Total Income', $html);
        $this->assertStringContainsString('Total Expense', $html);
        $this->assertStringContainsString('Rp 500.000', $html);
        $this->assertStringContainsString('Rp 25.000', $html);
        $this->assertStringContainsString('2026-01-01', $html);
        $this->assertStringContainsString('2026-01-31', $html);
        $this->assertStringContainsString('Kopi pagi', $html);
    }
}
