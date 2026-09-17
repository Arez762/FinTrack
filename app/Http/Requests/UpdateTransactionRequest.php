<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Normalise the destination account so it is only kept for transfers.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('type') !== 'transfer') {
            $this->merge(['transfer_to_account_id' => null]);
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
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'transfer_to_account_id' => [
                Rule::requiredIf($this->input('type') === 'transfer'),
                'nullable',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
                'different:account_id',
            ],
            'category_id' => [
                Rule::requiredIf(in_array($this->input('type'), ['income', 'expense'])),
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('type', $this->input('type')),
            ],
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'transaction_date' => [
                'required',
                'date',
                Rule::when(
                    $this->input('type') === 'expense',
                    'before_or_equal:today'
                ),
            ],
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
            'amount.gt' => 'Jumlah transaksi harus lebih besar dari 0.',
            'transaction_date.before_or_equal' => 'Tanggal transaksi expense tidak boleh melewati hari ini.',
            'transfer_to_account_id.required' => 'Pilih akun tujuan transfer.',
            'transfer_to_account_id.different' => 'Akun asal dan tujuan tidak boleh sama.',
        ];
    }
}
