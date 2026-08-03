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
            'bank_reference_id' => 'nullable|string|min:3|max:100',
            'bank_transaction_reference' => 'nullable|string|min:3|max:100',
            'admin_notes' => 'nullable|string|max:500',
        ];
    }
}
