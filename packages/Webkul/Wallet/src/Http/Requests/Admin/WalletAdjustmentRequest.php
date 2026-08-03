<?php

namespace Webkul\Wallet\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WalletAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return function_exists('bouncer') ? bouncer()->hasPermission('wallet.accounts.adjust') : true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'required|in:increase,decrease,credit,debit',
            'amount' => 'required|numeric|min:0.01|max:1000000',
            'reason' => 'required|string|min:5|max:500',
            'reference' => 'nullable|string|max:100',
        ];
    }
}
