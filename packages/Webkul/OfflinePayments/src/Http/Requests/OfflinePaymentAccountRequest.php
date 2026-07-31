<?php

namespace Webkul\OfflinePayments\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OfflinePaymentAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => 'required|string|max:255',
            'provider_name' => 'required|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'channel_ids' => 'required|array|min:1',
            'channel_ids.*' => 'required|integer',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer',
            'logo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',

            'destinations' => 'required|array|min:1',
            'destinations.*.id' => 'nullable|integer',
            'destinations.*.currency_id' => 'required|integer|exists:currencies,id|distinct',
            'destinations.*.account_identifier' => 'required|string|max:255',
            'destinations.*.swift_code' => 'nullable|string|max:255',
            'destinations.*.transfer_instructions' => 'nullable|string',
            'destinations.*.is_active' => 'sometimes|boolean',
            'destinations.*.sort_order' => 'nullable|integer',
        ];
    }
}
