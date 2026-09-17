<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(private readonly AccountBalanceService $balances) {}

    /**
     * Display a listing of the user's accounts with their computed balance.
     */
    public function index(Request $request): Response
    {
        $accounts = $request->user()->accounts()->get();

        $balances = $this->balances->balances($request->user(), $accounts);

        $accounts = $accounts->map(fn (Account $account) => [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type,
            'initial_balance' => $account->initial_balance,
            'balance' => $balances[$account->id],
        ]);

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Account::class);

        return Inertia::render('Accounts/Create');
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $request->user()->accounts()->create($request->validated());

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(Request $request, Account $account): Response
    {
        $this->authorize('update', $account);

        return Inertia::render('Accounts/Edit', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'initial_balance' => $account->initial_balance,
            ],
        ]);
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $account->update($request->validated());

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Request $request, Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Akun berhasil dihapus.');
    }
}
