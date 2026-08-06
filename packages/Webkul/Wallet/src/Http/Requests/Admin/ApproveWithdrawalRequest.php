<?php

namespace Webkul\Wallet\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveWithdrawalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'bank_transaction_reference' => 'nullable|string|max:100',
            'bank_reference_id' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            'proof' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            'admin_notes' => 'nullable|string|max:500',
        ];
    }
}
