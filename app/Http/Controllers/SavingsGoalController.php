<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddFundsRequest;
use App\Http\Requests\StoreSavingsGoalRequest;
use App\Http\Requests\UpdateSavingsGoalRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SavingsGoalController extends Controller
{
    /**
     * Display a listing of the user's savings goals.
     */
    public function index(Request $request): Response
    {
        $goals = $request->user()->savingsGoals()
            ->with('account:id,name')
            ->orderBy('is_completed')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SavingsGoal $goal) => $this->serialize($goal));

        return Inertia::render('SavingsGoals/Index', [
            'savingsGoals' => $goals,
        ]);
    }

    /**
     * Show the form for creating a new savings goal.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', SavingsGoal::class);

        return Inertia::render('SavingsGoals/Create', [
            'accounts' => $this->accounts($request->user()),
        ]);
    }

    /**
     * Store a newly created savings goal.
     */
    public function store(StoreSavingsGoalRequest $request): RedirectResponse
    {
        $request->user()->savingsGoals()->create($request->validated());

        return redirect()
            ->route('savings-goals.index')
            ->with('success', 'Target tabungan berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified savings goal.
     */
    public function edit(Request $request, SavingsGoal $savingsGoal): Response
    {
        $this->authorize('update', $savingsGoal);

        return Inertia::render('SavingsGoals/Edit', [
            'savingsGoal' => $this->serialize($savingsGoal),
            'accounts' => $this->accounts($request->user()),
        ]);
    }

    /**
     * Update the specified savings goal.
     */
    public function update(UpdateSavingsGoalRequest $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        $this->authorize('update', $savingsGoal);

        $savingsGoal->update($request->validated());

        return redirect()
            ->route('savings-goals.index')
            ->with('success', 'Target tabungan berhasil diperbarui.');
    }

    /**
     * Remove the specified savings goal.
     */
    public function destroy(Request $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        $this->authorize('delete', $savingsGoal);

        $savingsGoal->delete();

        return redirect()
            ->route('savings-goals.index')
            ->with('success', 'Target tabungan berhasil dihapus.');
    }

    /**
     * Add funds to a savings goal, optionally recording an expense transaction
     * against the linked account so the money actually leaves the balance.
     */
    public function addFunds(AddFundsRequest $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        $this->authorize('update', $savingsGoal);

        $amount = (float) $request->validated('amount');
        $wasCompleted = $savingsGoal->is_completed;

        $savingsGoal->current_amount += $amount;
        $savingsGoal->is_completed = $savingsGoal->current_amount >= (float) $savingsGoal->target_amount;
        $savingsGoal->save();

        if ($savingsGoal->account_id) {
            $request->user()->transactions()->create([
                'account_id' => $savingsGoal->account_id,
                'category_id' => $this->savingCategory($request->user())->id,
                'type' => 'expense',
                'amount' => $amount,
                'description' => "Menabung untuk {$savingsGoal->name}",
                'transaction_date' => Carbon::now()->toDateString(),
            ]);
        }

        $response = redirect()
            ->route('savings-goals.index')
            ->with('success', "Dana berhasil ditambahkan ke \"{$savingsGoal->name}\".");

        if (! $wasCompleted && $savingsGoal->is_completed) {
            $response->with('savings_goal_completed', $savingsGoal->name);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SavingsGoal $goal): array
    {
        $target = (float) $goal->target_amount;
        $current = (float) $goal->current_amount;

        $daysLeft = null;
        if ($goal->target_date) {
            $today = Carbon::today();
            $targetDate = Carbon::parse($goal->target_date->format('Y-m-d'));
            $daysLeft = $today->diffInDays($targetDate);
            if ($targetDate < $today) {
                $daysLeft = -$daysLeft;
            }
        }

        return [
            'id' => $goal->id,
            'name' => $goal->name,
            'target_amount' => $target,
            'current_amount' => $current,
            'target_date' => $goal->target_date?->format('Y-m-d'),
            'icon' => $goal->icon,
            'color' => $goal->color,
            'is_completed' => $goal->is_completed,
            'account_id' => $goal->account_id,
            'account' => $goal->account ? ['id' => $goal->account->id, 'name' => $goal->account->name] : null,
            'percentage' => $target > 0 ? round(($current / $target) * 100, 1) : 0,
            'remaining' => round($target - $current, 2),
            'days_left' => $goal->is_completed ? 0 : $daysLeft,
        ];
    }

    /**
     * Accounts owned by the user, used by the form dropdown.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function accounts(User $user): Collection
    {
        return $user->accounts()
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
            ]);
    }

    /**
     * The special system expense category used to record savings deposits.
     * Created once per user, then reused for every goal.
     */
    private function savingCategory(User $user): Category
    {
        return Category::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Tabungan', 'type' => 'expense'],
            ['color' => '#0ea5e9'],
        );
    }
}
