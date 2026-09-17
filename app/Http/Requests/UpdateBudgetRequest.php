<?php

namespace App\Http\Requests;

use App\Models\Budget;

/**
 * The rules are identical to the store request; the unique rule ignores
 * the budget currently being updated through the route binding.
 */
class UpdateBudgetRequest extends StoreBudgetRequest
{
    /**
     * Other users must not learn about the budget through validation errors.
     */
    public function authorize(): bool
    {
        $budget = $this->route('budget');

        return $budget instanceof Budget && $this->user()->can('update', $budget);
    }
}
