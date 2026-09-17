<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\User;
use App\Services\BudgetProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    /**
     * Display the user's budgets for the running month or year.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'nullable', Rule::in(['month', 'year'])],
        ]);

        $now = Carbon::now();
        $period = $validated['period'] ?? 'month';

        $budgets = (new BudgetProgressService)->forPeriod(
            $request->user(),
            $period,
            $now->year,
            $period === 'month' ? $now->month : null,
        );

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgets,
            'period' => $period,
            'periodLabel' => $period === 'year'
                ? (string) $now->year
                : $now->translatedFormat('F Y'),
        ]);
    }

    /**
     * Show the form for creating a new budget.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Budget::class);

        return Inertia::render('Budgets/Create', [
            'categories' => $this->expenseCategories($request->user()),
            'defaults' => [
                'month' => (int) Carbon::now()->month,
                'year' => (int) Carbon::now()->year,
            ],
        ]);
    }

    /**
     * Store a newly created budget.
     */
    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $request->user()->budgets()->create($request->validated());

        return redirect()
            ->route('budgets.index')
            ->with('success', 'Budget berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified budget.
     */
    public function edit(Request $request, Budget $budget): Response
    {
        $this->authorize('update', $budget);

        $now = Carbon::now();

        return Inertia::render('Budgets/Edit', [
            'budget' => [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'amount_limit' => (float) $budget->amount_limit,
                'period' => $budget->period,
                'month' => $budget->month,
                'year' => $budget->year,
            ],
            'categories' => $this->expenseCategories($request->user()),
            'defaults' => [
                'month' => (int) ($budget->month ?? $now->month),
                'year' => (int) $budget->year,
            ],
        ]);
    }

    /**
     * Update the specified budget.
     */
    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $this->authorize('update', $budget);

        $budget->update($request->validated());

        return redirect()
            ->route('budgets.index')
            ->with('success', 'Budget berhasil diperbarui.');
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorize('delete', $budget);

        $budget->delete();

        return redirect()
            ->route('budgets.index')
            ->with('success', 'Budget berhasil dihapus.');
    }

    /**
     * Expense categories owned by the user, used by the form dropdown.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function expenseCategories(User $user): Collection
    {
        return $user->categories()
            ->where('type', 'expense')
            ->orderBy('name')
            ->get(['id', 'name', 'color'])
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
            ]);
    }
}
