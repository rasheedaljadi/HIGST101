<?php

namespace App\Http\Requests\AliExpress;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the AliExpress product import submission.
 *
 * Only shallow validation lives here: the identifier must be present and a
 * reasonably sized string. Deciding whether the value resolves to a real
 * AliExpress product id is delegated to AliExpressProductIdExtractor so the
 * importer can return the precise reason (Requirements 2.3/2.4).
 *
 * Messages are plain Arabic strings since the store locale is `ar`.
 */
class ImportProductRequest extends FormRequest
{
    /**
     * The route already sits behind the admin auth guard middleware, so
     * authorization is handled at the route level (Requirement 1.4).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'الرجاء إدخال معرف منتج AliExpress أو رابط المنتج.',
            'identifier.string' => 'يجب أن يكون معرف المنتج أو الرابط نصًا صالحًا.',
            'identifier.max' => 'معرف المنتج أو الرابط طويل جدًا (الحد الأقصى 2048 حرفًا).',
        ];
    }
}
