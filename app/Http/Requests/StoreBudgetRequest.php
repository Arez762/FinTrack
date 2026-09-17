<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreBudgetRequest extends FormRequest
{
    /**
     * A yearly budget never carries a month, even if one is submitted.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('period') === 'year') {
            $this->merge(['month' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('type', 'expense'),
                $this->uniqueBudgetRule(),
            ],
            'amount_limit' => ['required', 'numeric', 'gt:0'],
            'period' => ['required', Rule::in(['month', 'year'])],
            'month' => [
                'nullable',
                'integer',
                'between:1,12',
                Rule::requiredIf($this->input('period') === 'month'),
            ],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.unique' => 'Budget untuk kategori dan periode ini sudah ada.',
            'amount_limit.gt' => 'Batas budget harus lebih besar dari 0.',
            'month.required' => 'Bulan wajib diisi untuk budget bulanan.',
        ];
    }

    /**
     * Prevent duplicate budgets for the same category and period of a user.
     */
    protected function uniqueBudgetRule(): Unique
    {
        $period = $this->input('period');

        $rule = Rule::unique('budgets', 'category_id')
            ->where(fn ($query) => $query
                ->where('user_id', $this->user()->id)
                ->where('period', $period)
                ->where('year', $this->input('year'))
                ->when(
                    $this->input('month') === null,
                    fn ($query) => $query->whereNull('month'),
                    fn ($query) => $query->where('month', $this->input('month')),
                )
            );

        if ($budget = $this->route('budget')) {
            $rule->ignore($budget);
        }

        return $rule;
    }
}
