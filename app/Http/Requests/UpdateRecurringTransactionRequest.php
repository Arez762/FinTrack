<?php

namespace App\Http\Requests;

use App\Models\RecurringTransaction;

/**
 * The rules are identical to the store request; ownership is checked before
 * validation so another user's template answers with 403 instead of a
 * misleading validation error.
 */
class UpdateRecurringTransactionRequest extends StoreRecurringTransactionRequest
{
    public function authorize(): bool
    {
        $recurringTransaction = $this->route('recurring_transaction');

        return $recurringTransaction instanceof RecurringTransaction
            && $this->user()->can('update', $recurringTransaction);
    }
}
