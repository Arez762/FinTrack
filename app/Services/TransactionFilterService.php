<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TransactionFilterService
{
    /**
     * Validate and return the transaction filter inputs from the request.
     */
    public function validated(Request $request): array
    {
        return $request->validate([
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('user_id', $request->user()->id),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $request->user()->id),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($value && $request->input('date_from') && $value < $request->input('date_from')) {
                        $fail('Tanggal akhir tidak boleh sebelum tanggal awal.');
                    }
                },
            ],
            'type' => ['nullable', Rule::in(['income', 'expense', 'transfer'])],
        ]);
    }

    /**
     * Build the filtered query for the authenticated user's transactions.
     */
    public function query(Request $request, array $filters): HasMany
    {
        return $request->user()->transactions()
            ->with(['account:id,name', 'transferToAccount:id,name', 'category:id,name,color'])
            ->when($filters['account_id'] ?? null, fn ($query, $id) => $query->where('account_id', $id))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', '<=', $date));
    }

    /**
     * Keep only the known filter keys, normalised to null when absent.
     */
    public function normalise(array $filters): array
    {
        return [
            'account_id' => $filters['account_id'] ?? null,
            'category_id' => $filters['category_id'] ?? null,
            'type' => $filters['type'] ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];
    }

    /**
     * Totals and date span of the filtered query.
     */
    public function summary(HasMany $query): object
    {
        return (clone $query)
            ->reorder()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense")
            ->selectRaw('MIN(transaction_date) as first_date')
            ->selectRaw('MAX(transaction_date) as last_date')
            ->first();
    }

    /**
     * The period covered by the export, falling back to the data's own date span.
     *
     * @return array{from: string|null, to: string|null}
     */
    public function period(array $filters, object $summary): array
    {
        return [
            'from' => $filters['date_from']
                ?? ($summary->first_date ? Carbon::parse($summary->first_date)->toDateString() : null),
            'to' => $filters['date_to']
                ?? ($summary->last_date ? Carbon::parse($summary->last_date)->toDateString() : null),
        ];
    }

    /**
     * Row representation shared by the CSV and PDF exports.
     */
    public function row(Transaction $transaction): array
    {
        return [
            'date' => $transaction->transaction_date->format('Y-m-d'),
            'account' => $transaction->account?->name,
            'category' => $transaction->category?->name,
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
            'description' => $transaction->description,
        ];
    }
}
