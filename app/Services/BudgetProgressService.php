<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Support\Carbon;

class BudgetProgressService
{
    /**
     * Usage ratio (in percent) at which a budget is considered a warning.
     */
    public const WARNING_THRESHOLD = 70.0;

    /**
     * Usage ratio (in percent) at which a budget is considered exceeded.
     */
    public const OVER_THRESHOLD = 100.0;

    /**
     * Usage ratio (in percent) at which the dashboard raises an alert.
     */
    public const ALERT_THRESHOLD = 90.0;

    /**
     * Budgets of the given period together with the spend progress of their category.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forPeriod(User $user, string $period, int $year, ?int $month = null): array
    {
        $budgets = $user->budgets()
            ->with('category:id,name,color')
            ->where('period', $period)
            ->where('year', $year)
            ->when(
                $period === 'month',
                fn ($query) => $query->where('month', $month),
                fn ($query) => $query->whereNull('month'),
            )
            ->get();

        [$start, $end] = $this->periodRange($period, $year, $month);

        $spent = $this->spentByCategory(
            $user,
            $budgets->pluck('category_id')->all(),
            $start,
            $end,
        );

        return $budgets
            ->sortBy(fn (Budget $budget) => $budget->category?->name)
            ->values()
            ->map(fn (Budget $budget) => $this->progress(
                $budget,
                (float) ($spent[$budget->category_id] ?? 0),
            ))
            ->all();
    }

    /**
     * Currently running budgets that already reached the given usage threshold.
     *
     * @return array<int, array<string, mixed>>
     */
    public function alerts(User $user, float $threshold = self::ALERT_THRESHOLD): array
    {
        $now = Carbon::now();

        $running = [
            ...$this->forPeriod($user, 'month', $now->year, $now->month),
            ...$this->forPeriod($user, 'year', $now->year),
        ];

        return array_values(array_filter(
            $running,
            fn (array $budget) => $budget['percentage'] >= $threshold,
        ));
    }

    /**
     * Resolve the inclusive date range covered by a budget period.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodRange(string $period, int $year, ?int $month = null): array
    {
        if ($period === 'year') {
            $start = Carbon::create($year, 1, 1)->startOfYear();

            return [$start, $start->copy()->endOfYear()];
        }

        $start = Carbon::create($year, $month ?? 1, 1)->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    /**
     * Sum of expense transactions per category within the given range.
     *
     * @param  array<int, int>  $categoryIds
     * @return array<int, float>
     */
    private function spentByCategory(User $user, array $categoryIds, Carbon $start, Carbon $end): array
    {
        if ($categoryIds === []) {
            return [];
        }

        return $user->transactions()
            ->where('type', 'expense')
            ->whereIn('category_id', $categoryIds)
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->groupBy('category_id')
            ->selectRaw('category_id, COALESCE(SUM(amount), 0) as total')
            ->pluck('total', 'category_id')
            ->map(fn ($total) => (float) $total)
            ->all();
    }

    /**
     * Build the progress payload for a single budget.
     *
     * @return array<string, mixed>
     */
    private function progress(Budget $budget, float $spent): array
    {
        $limit = (float) $budget->amount_limit;
        $percentage = $limit > 0 ? round(($spent / $limit) * 100, 2) : 0.0;

        return [
            'id' => $budget->id,
            'category_id' => $budget->category_id,
            'category' => $budget->category ? [
                'id' => $budget->category->id,
                'name' => $budget->category->name,
                'color' => $budget->category->color,
            ] : null,
            'amount_limit' => round($limit, 2),
            'spent' => round($spent, 2),
            'remaining' => round($limit - $spent, 2),
            'percentage' => $percentage,
            'status' => $this->status($percentage),
            'period' => $budget->period,
            'month' => $budget->month,
            'year' => $budget->year,
        ];
    }

    /**
     * Classify a usage percentage into safe / warning / over.
     */
    public function status(float $percentage): string
    {
        return match (true) {
            $percentage >= self::OVER_THRESHOLD => 'over',
            $percentage >= self::WARNING_THRESHOLD => 'warning',
            default => 'safe',
        };
    }
}
