<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FinTrackSeeder extends Seeder
{
    /**
     * Seed a demo account with a realistic, sizeable dataset.
     *
     * The seeder is re-runnable: the demo account is reused and its previous
     * data is removed first, so `db:seed` always yields a clean, full dataset.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@fintrack.test'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now(),
            ],
        );

        Transaction::where('user_id', $user->id)->delete();
        Budget::where('user_id', $user->id)->delete();
        RecurringTransaction::where('user_id', $user->id)->delete();
        Category::where('user_id', $user->id)->delete();
        Account::where('user_id', $user->id)->delete();

        $accounts = collect([
            ['name' => 'Cash', 'type' => 'cash', 'initial_balance' => 2_000_000],
            ['name' => 'Bank Account', 'type' => 'bank', 'initial_balance' => 15_000_000],
            ['name' => 'E-Wallet', 'type' => 'ewallet', 'initial_balance' => 1_000_000],
        ])->map(fn (array $data) => Account::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'initial_balance' => $data['initial_balance'],
        ]));

        $expenseCategories = collect([
            ['name' => 'Makanan', 'color' => '#ef4444'],
            ['name' => 'Transport', 'color' => '#f97316'],
            ['name' => 'Belanja', 'color' => '#eab308'],
            ['name' => 'Hiburan', 'color' => '#ec4899'],
            ['name' => 'Tagihan', 'color' => '#3b82f6'],
            ['name' => 'Kesehatan', 'color' => '#10b981'],
            ['name' => 'Pendidikan', 'color' => '#8b5cf6'],
            ['name' => 'Lainnya', 'color' => '#64748b'],
        ])->map(fn (array $data) => Category::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'type' => 'expense',
            'color' => $data['color'],
        ]));

        $incomeCategories = collect([
            ['name' => 'Gaji', 'color' => '#22c55e'],
            ['name' => 'Bonus', 'color' => '#06b6d4'],
            ['name' => 'Investasi', 'color' => '#6366f1'],
            ['name' => 'Lainnya', 'color' => '#0ea5e9'],
        ])->map(fn (array $data) => Category::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'type' => 'income',
            'color' => $data['color'],
        ]));

        $now = Carbon::now();

        $budgets = [
            ['name' => 'Makanan', 'amount_limit' => 2_500_000, 'period' => 'month'],
            ['name' => 'Transport', 'amount_limit' => 1_500_000, 'period' => 'month'],
            ['name' => 'Belanja', 'amount_limit' => 1_000_000, 'period' => 'month'],
            ['name' => 'Hiburan', 'amount_limit' => 500_000, 'period' => 'month'],
            ['name' => 'Tagihan', 'amount_limit' => 2_000_000, 'period' => 'month'],
            ['name' => 'Kesehatan', 'amount_limit' => 12_000_000, 'period' => 'year'],
        ];

        foreach ($budgets as $budget) {
            Budget::create([
                'user_id' => $user->id,
                'category_id' => $expenseCategories->firstWhere('name', $budget['name'])->id,
                'amount_limit' => $budget['amount_limit'],
                'period' => $budget['period'],
                'month' => $budget['period'] === 'month' ? $now->month : null,
                'year' => $now->year,
            ]);
        }

        $recurringTemplates = [
            ['name' => 'Gaji', 'type' => 'income', 'account' => 'bank', 'amount' => 8_000_000, 'frequency' => 'monthly', 'description' => 'Gaji bulanan otomatis'],
            ['name' => 'Tagihan', 'type' => 'expense', 'account' => 'bank', 'amount' => 350_000, 'frequency' => 'monthly', 'description' => 'Tagihan listrik & internet'],
            ['name' => 'Transport', 'type' => 'expense', 'account' => 'ewallet', 'amount' => 60_000, 'frequency' => 'weekly', 'description' => 'Transport mingguan'],
            ['name' => 'Kesehatan', 'type' => 'expense', 'account' => 'bank', 'amount' => 2_400_000, 'frequency' => 'yearly', 'description' => 'Premi asuransi kesehatan'],
            ['name' => 'Hiburan', 'type' => 'expense', 'account' => 'ewallet', 'amount' => 54_000, 'frequency' => 'monthly', 'description' => 'Langganan streaming', 'is_active' => false],
        ];

        foreach ($recurringTemplates as $template) {
            $pool = $template['type'] === 'income' ? $incomeCategories : $expenseCategories;

            RecurringTransaction::create([
                'user_id' => $user->id,
                'account_id' => $accounts->firstWhere('type', $template['account'])->id,
                'category_id' => $pool->firstWhere('name', $template['name'])->id,
                'type' => $template['type'],
                'amount' => $template['amount'],
                'description' => $template['description'],
                'frequency' => $template['frequency'],
                'start_date' => $now->toDateString(),
                'next_run_date' => $now->toDateString(),
                'is_active' => $template['is_active'] ?? true,
            ]);
        }

        $expenseDescriptions = [
            'Makanan' => ['Makan siang di kantor', 'Makan malam bersama keluarga', 'Beli makanan ringan', 'Ngopi di kafe', 'Beli buah dan sayur'],
            'Transport' => ['Beli bensin kendaraan', 'Ojek online', 'Bayar parkir', 'Top up e-toll', 'ISi ulang kendaraan'],
            'Belanja' => ['Belanja kebutuhan bulanan', 'Belanja online', 'Beli perlengkapan rumah', 'Beli alat dapur'],
            'Hiburan' => ['Nonton bioskop', 'Langganan streaming', 'Main ke taman hiburan', 'Konser musik'],
            'Tagihan' => ['Tagihan listrik', 'Bayar internet', 'Bayar air', 'Tagihan HP'],
            'Kesehatan' => ['Beli obat di apotek', 'Konsultasi dokter', 'Beli vitamin'],
            'Pendidikan' => ['Beli buku', 'Kursus online', 'Beli alat tulis'],
            'Lainnya' => ['Sumbangan', 'Bayar jasa kebersihan', 'Lain-lain pengeluaran'],
        ];

        $incomeDescriptions = [
            'Gaji' => 'Transfer gaji bulanan',
            'Bonus' => 'Bonus kinerja',
            'Investasi' => 'Dividen investasi',
            'Lainnya' => 'Pendapatan tambahan',
        ];

        $transferDescriptions = ['Pindah saldo antar akun', 'Top up e-wallet', 'Transfer ke tabungan'];

        // Pendapatan & kewajiban bulanan untuk 6 bulan terakhir.
        for ($month = 5; $month >= 0; $month--) {
            $monthDate = Carbon::now()->startOfMonth()->subMonths($month);

            $salaryDate = $monthDate->copy()->day(25);
            if ($salaryDate->isFuture()) {
                $salaryDate = Carbon::now();
            }
            $this->createTransaction([
                'user_id' => $user->id,
                'account_id' => $accounts->firstWhere('type', 'bank')->id,
                'category_id' => $incomeCategories->firstWhere('name', 'Gaji')->id,
                'type' => 'income',
                'amount' => fake()->numberBetween(7_000_000, 9_000_000),
                'description' => 'Transfer gaji bulanan',
                'transaction_date' => $salaryDate->toDateString(),
            ]);

            $billDate = $monthDate->copy()->day(5);
            if ($billDate->isPast() || $billDate->isToday()) {
                $this->createTransaction([
                    'user_id' => $user->id,
                    'account_id' => $accounts->random()->id,
                    'category_id' => $expenseCategories->firstWhere('name', 'Tagihan')->id,
                    'type' => 'expense',
                    'amount' => fake()->numberBetween(150_000, 900_000),
                    'description' => fake()->randomElement($expenseDescriptions['Tagihan']),
                    'transaction_date' => $billDate->toDateString(),
                ]);
            }

            if (($month % 3) === 0) {
                $bonusDate = $monthDate->copy()->day(fake()->numberBetween(20, 28));
                if ($bonusDate->isFuture()) {
                    continue;
                }
                $this->createTransaction([
                    'user_id' => $user->id,
                    'account_id' => $accounts->firstWhere('type', 'bank')->id,
                    'category_id' => $incomeCategories->firstWhere('name', 'Bonus')->id,
                    'type' => 'income',
                    'amount' => fake()->numberBetween(500_000, 2_000_000),
                    'description' => 'Bonus kinerja',
                    'transaction_date' => $bonusDate->toDateString(),
                ]);
            }
        }

        // Transaksi harian untuk 180 hari terakhir (~1-2 pengeluaran per hari).
        for ($i = 179; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();

            if (fake()->boolean(70)) {
                $category = $expenseCategories->random();
                $this->createTransaction([
                    'user_id' => $user->id,
                    'account_id' => $accounts->random()->id,
                    'category_id' => $category->id,
                    'type' => 'expense',
                    'amount' => fake()->numberBetween(10_000, 450_000),
                    'description' => fake()->randomElement($expenseDescriptions[$category->name]),
                    'transaction_date' => $date,
                ]);
            }

            if (fake()->boolean(8)) {
                $this->createTransaction([
                    'user_id' => $user->id,
                    'account_id' => $accounts->random()->id,
                    'category_id' => $incomeCategories->firstWhere('name', 'Investasi')->id,
                    'type' => 'income',
                    'amount' => fake()->numberBetween(100_000, 1_500_000),
                    'description' => 'Dividen investasi',
                    'transaction_date' => $date,
                ]);
            }

            if (fake()->boolean(5)) {
                $from = $accounts->random();
                $to = $accounts->reject(fn ($account) => $account->id === $from->id)->random();

                $this->createTransaction([
                    'user_id' => $user->id,
                    'account_id' => $from->id,
                    'transfer_to_account_id' => $to->id,
                    'category_id' => null,
                    'type' => 'transfer',
                    'amount' => fake()->numberBetween(50_000, 500_000),
                    'description' => fake()->randomElement($transferDescriptions),
                    'transaction_date' => $date,
                ]);
            }
        }

        $this->command?->info(sprintf(
            'FinTrack demo seeded: %d accounts, %d categories, %d transactions, %d budgets, %d recurring transactions.',
            $user->accounts()->count(),
            $user->categories()->count(),
            $user->transactions()->count(),
            $user->budgets()->count(),
            $user->recurringTransactions()->count(),
        ));
    }

    private function createTransaction(array $data): void
    {
        Transaction::create($data);
    }
}
