<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\AccountBalanceService;
use App\Services\BudgetProgressService;
use App\Services\ReportDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly AccountBalanceService $balances) {}

    /**
     * Display the dashboard with financial summary for the current user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $totals = Transaction::query()
            ->where('user_id', $user->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' AND transaction_date >= ? AND transaction_date <= ? THEN amount ELSE 0 END), 0) as month_income", [$startOfMonth, $endOfMonth])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' AND transaction_date >= ? AND transaction_date <= ? THEN amount ELSE 0 END), 0) as month_expense", [$startOfMonth, $endOfMonth])
            ->first();

        $recentTransactions = $user->transactions()
            ->with(['account:id,name', 'transferToAccount:id,name', 'category:id,name,color'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                'account' => $transaction->account,
                'transfer_to_account' => $transaction->transferToAccount,
                'category' => $transaction->category,
            ]);

        $service = new ReportDataService;
        $budgetService = new BudgetProgressService;

        $goals = $user->savingsGoals()
            ->select(['target_amount', 'current_amount', 'is_completed'])
            ->get();

        $goalSummary = [
            'total' => $goals->count(),
            'active' => $goals->where('is_completed', false)->count(),
            'average_progress' => round($goals->map(fn ($goal) => (float) $goal->target_amount > 0
                ? min(100, ((float) $goal->current_amount / (float) $goal->target_amount) * 100)
                : 0)->avg() ?? 0, 1),
        ];

        $validated = $request->validate(['range' => ['sometimes', 'nullable', Rule::in(['week', 'month', 'year'])],
            'category_range' => ['sometimes', 'nullable', Rule::in(['week', 'month', 'year'])],
        ]);

        $range = $validated['range'] ?? 'month';
        $categoryRange = $validated['category_range'] ?? 'month';

        return Inertia::render('Dashboard', [
            'summary' => [
                'total_balance' => round($this->balances->total($user), 2),
                'month_income' => round((float) $totals->month_income, 2),
                'month_expense' => round((float) $totals->month_expense, 2),
                'month' => $now->translatedFormat('F Y'),
            ],
            'recent_transactions' => $recentTransactions,
            'budget_alerts' => $budgetService->alerts($user),
            'budgets' => [
                ...$budgetService->forPeriod($user, 'month', $now->year, $now->month),
                ...$budgetService->forPeriod($user, 'year', $now->year),
            ],
            'range' => $range,
            'categoryRange' => $categoryRange,
            'monthly' => $service->incomeExpense($user, $range),
            'category_expense' => $service->categoryExpense($user, $categoryRange),
            'monthLabel' => $now->format('F Y'),
            'goal_summary' => $goalSummary,
        ]);
    }
}
