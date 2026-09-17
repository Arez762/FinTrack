<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecurringTransactionRequest;
use App\Http\Requests\UpdateRecurringTransactionRequest;
use App\Models\RecurringTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTransactionController extends Controller
{
    /**
     * Display the user's recurring templates, active ones first.
     */
    public function index(Request $request): Response
    {
        $recurringTransactions = $request->user()->recurringTransactions()
            ->with(['account:id,name,type', 'category:id,name,color'])
            ->orderByDesc('is_active')
            ->orderBy('next_run_date')
            ->orderBy('id')
            ->get()
            ->map(fn (RecurringTransaction $recurring) => $this->payload($recurring))
            ->all();

        return Inertia::render('RecurringTransactions/Index', [
            'recurringTransactions' => $recurringTransactions,
        ]);
    }

    /**
     * Show the form for creating a new recurring template.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', RecurringTransaction::class);

        return Inertia::render('RecurringTransactions/Create', [
            ...$this->formOptions($request),
            'defaults' => [
                'start_date' => Carbon::today()->toDateString(),
            ],
        ]);
    }

    /**
     * Store a newly created recurring template.
     */
    public function store(StoreRecurringTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['next_run_date'] = $data['start_date'];

        $request->user()->recurringTransactions()->create($data);

        return redirect()
            ->route('recurring-transactions.index')
            ->with('success', 'Transaksi berulang berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified recurring template.
     */
    public function edit(Request $request, RecurringTransaction $recurringTransaction): Response
    {
        $this->authorize('update', $recurringTransaction);

        return Inertia::render('RecurringTransactions/Edit', [
            ...$this->formOptions($request),
            'recurringTransaction' => $this->payload($recurringTransaction),
        ]);
    }

    /**
     * Update the specified recurring template.
     */
    public function update(UpdateRecurringTransactionRequest $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize('update', $recurringTransaction);

        $recurringTransaction->update($request->validated());

        return redirect()
            ->route('recurring-transactions.index')
            ->with('success', 'Transaksi berulang berhasil diperbarui.');
    }

    /**
     * Remove the specified recurring template.
     */
    public function destroy(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize('delete', $recurringTransaction);

        $recurringTransaction->delete();

        return redirect()
            ->route('recurring-transactions.index')
            ->with('success', 'Transaksi berulang berhasil dihapus.');
    }

    /**
     * Pause or resume the specified recurring template without deleting it.
     */
    public function toggleActive(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $this->authorize('toggleActive', $recurringTransaction);

        $recurringTransaction->update(['is_active' => ! $recurringTransaction->is_active]);

        return redirect()
            ->route('recurring-transactions.index')
            ->with('success', $recurringTransaction->is_active
                ? 'Transaksi berulang diaktifkan kembali.'
                : 'Transaksi berulang dijeda.');
    }

    /**
     * Shared account and category options for the create/edit forms.
     *
     * @return array<string, Collection<int, array<string, mixed>>>
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

    /**
     * Serialise a recurring template for the frontend.
     *
     * @return array<string, mixed>
     */
    private function payload(RecurringTransaction $recurring): array
    {
        return [
            'id' => $recurring->id,
            'account_id' => $recurring->account_id,
            'category_id' => $recurring->category_id,
            'type' => $recurring->type,
            'amount' => (float) $recurring->amount,
            'description' => $recurring->description,
            'frequency' => $recurring->frequency,
            'start_date' => $recurring->start_date->format('Y-m-d'),
            'next_run_date' => $recurring->next_run_date->format('Y-m-d'),
            'is_active' => $recurring->is_active,
            'account' => $recurring->account ? [
                'id' => $recurring->account->id,
                'name' => $recurring->account->name,
                'type' => $recurring->account->type,
            ] : null,
            'category' => $recurring->category ? [
                'id' => $recurring->category->id,
                'name' => $recurring->category->name,
                'color' => $recurring->category->color,
            ] : null,
        ];
    }
}
