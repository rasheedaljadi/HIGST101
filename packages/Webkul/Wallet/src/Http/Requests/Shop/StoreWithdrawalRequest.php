<?php

namespace Webkul\Wallet\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Webkul\Wallet\Repositories\WalletAccountRepository;

class StoreWithdrawalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->guard('customer')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:5|max:50000',
            'method' => 'required|string',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:100',
        ];
    }

    /**
     * Configure the validator instance with custom available balance checks.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $customer = auth()->guard('customer')->user();

            if (! $customer) {
                return;
            }

            $walletRepo = app(WalletAccountRepository::class);
            $wallet = $walletRepo->where('customer_id', $customer->id)->first();

            $availableBalance = $wallet ? (float) $wallet->available_balance : 0.00;
            $requestedAmount = (float) $this->input('amount');

            if ($requestedAmount > $availableBalance) {
                $validator->errors()->add('amount', trans('wallet::app.shop.withdraw.insufficient-balance') ?? 'Insufficient available balance.');
            }
        });
    }
}
