<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller
{
    /**
     * Display a history of the user's transfers, newest first.
     */
    public function index(Request $request): Response
    {
        $transfers = $request->user()->transactions()
            ->where('type', 'transfer')
            ->with(['account:id,name,type', 'transferToAccount:id,name,type'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Transaction $transfer) => $this->payload($transfer));

        return Inertia::render('Transfers/Index', [
            'transfers' => $transfers,
        ]);
    }

    /**
     * Show the form for creating a new transfer.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Transaction::class);

        $accountIds = $request->user()->accounts()->pluck('id')->all();
        $from = $request->integer('from');

        return Inertia::render('Transfers/Create', [
            'accounts' => $this->accountOptions($request),
            'defaults' => [
                'account_id' => in_array($from, $accountIds, true) ? $from : null,
                'transaction_date' => Carbon::today()->toDateString(),
            ],
        ]);
    }

    /**
     * Store a newly created transfer.
     */
    public function store(StoreTransferRequest $request): RedirectResponse
    {
        $request->user()->transactions()->create([
            ...$request->validated(),
            'type' => 'transfer',
            'category_id' => null,
        ]);

        return redirect()
            ->route('transfers.index')
            ->with('success', 'Transfer berhasil dicatat.');
    }

    /**
     * Shared account options for the transfer pages.
     */
    private function accountOptions(Request $request): array
    {
        return $request->user()->accounts()
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn ($account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
            ])
            ->all();
    }

    /**
     * Serialise a transfer for the frontend.
     *
     * @return array<string, mixed>
     */
    private function payload(Transaction $transfer): array
    {
        return [
            'id' => $transfer->id,
            'amount' => (float) $transfer->amount,
            'description' => $transfer->description,
            'transaction_date' => $transfer->transaction_date->format('Y-m-d'),
            'account' => $transfer->account ? [
                'id' => $transfer->account->id,
                'name' => $transfer->account->name,
                'type' => $transfer->account->type,
            ] : null,
            'transfer_to_account' => $transfer->transferToAccount ? [
                'id' => $transfer->transferToAccount->id,
                'name' => $transfer->transferToAccount->name,
                'type' => $transfer->transferToAccount->type,
            ] : null,
        ];
    }
}
