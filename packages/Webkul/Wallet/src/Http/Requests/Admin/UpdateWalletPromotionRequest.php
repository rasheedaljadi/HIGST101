<?php

namespace Webkul\Wallet\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Wallet\Models\WalletPromotion;

class UpdateWalletPromotionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return function_exists('bouncer') ? bouncer()->hasPermission('wallet.promotions.edit') : true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => [
                'required',
                'string',
                'in:'.implode(',', [
                    WalletPromotion::TYPE_WELCOME_BONUS,
                    WalletPromotion::TYPE_TOPUP_BONUS,
                    WalletPromotion::TYPE_ORDER_SUBTOTAL_CASHBACK,
                    WalletPromotion::TYPE_ORDER_CONDITIONAL_CASHBACK,
                ]),
            ],
            'status' => [
                'required',
                'string',
                'in:'.implode(',', [
                    WalletPromotion::STATUS_DRAFT,
                    WalletPromotion::STATUS_ACTIVE,
                    WalletPromotion::STATUS_INACTIVE,
                    WalletPromotion::STATUS_ARCHIVED,
                ]),
            ],
            'action_type' => [
                'required',
                'string',
                'in:'.implode(',', [
                    WalletPromotion::ACTION_FIXED,
                    WalletPromotion::ACTION_PERCENTAGE,
                ]),
            ],
            'reward_value' => ['required', 'numeric', 'min:0.0001'],
            'min_spend_amount' => ['nullable', 'numeric', 'min:0'],
            'max_reward_amount' => ['nullable', 'numeric', 'min:0'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_per_customer' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'grant_validity_days' => ['nullable', 'integer', 'min:1'],
            'starts_from' => ['nullable', 'date'],
            'ends_till' => ['nullable', 'date', 'after_or_equal:starts_from'],
            'conditions' => ['nullable', 'array'],
        ];
    }
}
