<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionFilterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionFilterService $filters) {}

    /**
     * Display a listing of the user's transactions, newest first.
     */
    public function index(Request $request): Response
    {
        $validated = $this->filters->validated($request);

        $query = $this->filters->query($request, $validated);

        $totals = $this->filters->summary($query);

        $transactions = $query->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $this->filters->normalise($validated),
            'totals' => [
                'income' => round((float) $totals->total_income, 2),
                'expense' => round((float) $totals->total_expense, 2),
            ],
            'accounts' => $request->user()->accounts()
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
            'categories' => $request->user()->categories()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color']),
        ]);
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Transaction::class);

        return Inertia::render('Transactions/Create', $this->formOptions($request));
    }

    /**
     * Store a newly created transaction.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $request->user()->transactions()->create($request->validated());

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Request $request, Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        return Inertia::render('Transactions/Edit', [
            ...$this->formOptions($request),
            'transaction' => [
                'id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'transfer_to_account_id' => $transaction->transfer_to_account_id,
                'category_id' => $transaction->category_id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Update the specified transaction.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $transaction->update($request->validated());

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }

    /**
     * Shared data for the create/edit forms.
     */
    private function formOptions(Request $request): array
    {
        return [
            'accounts' => $request->user()->accounts()
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
                ->map(fn ($account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                ]),
            'categories' => $request->user()->categories()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color'])
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'color' => $category->color,
                ]),
        ];
    }
}
