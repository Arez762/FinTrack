<?php

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Collection;

class AccountBalanceService
{
    /**
     * Net movement per account, counting both legs of every transfer.
     *
     * @return array<int, float> account id => net amount
     */
    public function netChangeByAccount(User $user): array
    {
        $outgoing = $user->transactions()
            ->selectRaw("account_id, SUM(CASE WHEN type = 'income' THEN amount WHEN type = 'expense' THEN -amount ELSE -amount END) as net")
            ->groupBy('account_id')
            ->pluck('net', 'account_id');

        $incoming = $user->transactions()
            ->where('type', 'transfer')
            ->whereNotNull('transfer_to_account_id')
            ->selectRaw('transfer_to_account_id as account_id, SUM(amount) as net')
            ->groupBy('transfer_to_account_id')
            ->pluck('net', 'account_id');

        $net = [];

        foreach ($outgoing as $accountId => $amount) {
            $net[(int) $accountId] = (float) $amount;
        }

        foreach ($incoming as $accountId => $amount) {
            $net[(int) $accountId] = ($net[(int) $accountId] ?? 0.0) + (float) $amount;
        }

        return $net;
    }

    /**
     * Current balance for each account, including its initial balance.
     *
     * @param  Collection<int, Account>  $accounts
     * @return array<int, float> account id => balance
     */
    public function balances(User $user, Collection $accounts): array
    {
        $net = $this->netChangeByAccount($user);

        $balances = [];

        foreach ($accounts as $account) {
            $balances[$account->id] = (float) $account->initial_balance + ($net[$account->id] ?? 0.0);
        }

        return $balances;
    }

    /**
     * Combined balance of every account owned by the user.
     */
    public function total(User $user): float
    {
        $initial = (float) Account::where('user_id', $user->id)
            ->selectRaw('COALESCE(SUM(initial_balance), 0) as total')
            ->value('total');

        return $initial + array_sum($this->netChangeByAccount($user));
    }
}
