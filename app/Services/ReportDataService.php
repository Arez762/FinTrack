<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportDataService
{
    /**
     * Total income & expense per week / month / year bucket.
     */
    public function incomeExpense(User $user, string $range): array
    {
        [$buckets, $start, $periodExpression, $keyFormat] = $this->periodBuckets($range);

        $rows = $user->transactions()
            ->whereIn('type', ['income', 'expense'])
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense")
            ->groupByRaw($periodExpression)
            ->orderByRaw($periodExpression)
            ->get()
            ->keyBy('period');

        return $buckets
            ->map(fn (Carbon $bucket) => [
                'period' => $bucket->format($keyFormat),
                'label' => $bucket->format($this->labelFormat($range)),
                'income' => round((float) ($rows[$bucket->format($keyFormat)]->income ?? 0), 2),
                'expense' => round((float) ($rows[$bucket->format($keyFormat)]->expense ?? 0), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * Total expense per category for the current week / month / year.
     */
    public function categoryExpense(User $user, string $range): array
    {
        $now = Carbon::now();

        [$start, $end] = match ($range) {
            'week' => [
                $now->copy()->startOfWeek(Carbon::MONDAY),
                $now->copy()->endOfWeek(Carbon::MONDAY),
            ],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        return $user->transactions()
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.type', 'expense')
            ->whereDate('transactions.transaction_date', '>=', $start->toDateString())
            ->whereDate('transactions.transaction_date', '<=', $end->toDateString())
            ->selectRaw('categories.name as name')
            ->selectRaw('categories.color as color')
            ->selectRaw('COALESCE(SUM(transactions.amount), 0) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'color' => $row->color,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /**
     * Build the bucket list, boundary start and the SQL period expression for a range.
     */
    private function periodBuckets(string $range): array
    {
        $now = Carbon::now();
        $sqlite = DB::connection()->getDriverName() === 'sqlite';

        $periodExpression = match ($range) {
            'week' => $sqlite
                ? "strftime('%Y-%m-%d', transaction_date, 'weekday 0', '-6 days')"
                : "DATE_FORMAT(DATE_SUB(transaction_date, INTERVAL WEEKDAY(transaction_date) DAY), '%Y-%m-%d')",
            'year' => $sqlite ? "strftime('%Y', transaction_date)" : 'YEAR(transaction_date)',
            default => $sqlite
                ? "strftime('%Y-%m', transaction_date)"
                : "DATE_FORMAT(transaction_date, '%Y-%m')",
        };

        return match ($range) {
            'week' => [
                collect(range(0, 11))
                    ->map(fn (int $i) => $now->copy()->startOfWeek(Carbon::MONDAY)->addWeeks($i - 11)),
                $now->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(11),
                $periodExpression,
                'Y-m-d',
            ],
            'year' => [
                collect(range(0, 4))
                    ->map(fn (int $i) => $now->copy()->startOfYear()->addYears($i - 4)),
                $now->copy()->startOfYear()->subYears(4),
                $periodExpression,
                'Y',
            ],
            default => [
                collect(range(0, 11))
                    ->map(fn (int $i) => $now->copy()->startOfMonth()->addMonths($i - 11)),
                $now->copy()->startOfMonth()->subMonths(11),
                $periodExpression,
                'Y-m',
            ],
        };
    }

    private function labelFormat(string $range): string
    {
        return match ($range) {
            'week' => 'd M',
            'year' => 'Y',
            default => 'M Y',
        };
    }
}
