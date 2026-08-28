<?php

namespace Webkul\DeliveryManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\DeliveryManagement\Models\DeliveryGovernorateRule;
use Webkul\DeliveryManagement\Models\DeliveryPoint;

class GovernorateDeliveryValidator
{
    public function __construct(
        protected ShippingMethodAdapter $shippingMethodAdapter
    ) {}

    /**
     * Resolve state code from input string, code, name or ID.
     */
    public function resolveStateCode(string $stateInput): string
    {
        $input = trim($stateInput);
        if (empty($input)) {
            return 'SAN';
        }

        $upper = strtoupper($input);

        // Direct code match in country_states
        $exists = DB::table('country_states')
            ->where('country_code', 'YE')
            ->where('code', $upper)
            ->value('code');

        if ($exists) {
            return $exists;
        }

        // Numeric ID check
        if (is_numeric($input)) {
            $codeById = DB::table('country_states')
                ->where('id', (int) $input)
                ->value('code');
            if ($codeById) {
                return $codeById;
            }
        }

        // Name match in default_name
        $codeByName = DB::table('country_states')
            ->where('country_code', 'YE')
            ->where('default_name', 'like', "%{$input}%")
            ->value('code');

        if ($codeByName) {
            return $codeByName;
        }

        // Translation name match
        $codeByTrans = DB::table('country_state_translations')
            ->join('country_states', 'country_states.id', '=', 'country_state_translations.country_state_id')
            ->where('country_states.country_code', 'YE')
            ->where('country_state_translations.default_name', 'like', "%{$input}%")
            ->value('country_states.code');

        return $codeByTrans ?: $upper;
    }

    /**
     * Validate that the state code is valid for Yemen (YE).
     */
    public function isValidStateCode(string $stateCode): bool
    {
        if (empty($stateCode)) {
            return false;
        }

        $resolved = $this->resolveStateCode($stateCode);

        return DB::table('country_states')
            ->where('country_code', 'YE')
            ->where('code', $resolved)
            ->exists();
    }

    /**
     * Get the active delivery rule for a state code and delivery type.
     */
    public function getActiveRule(string $stateCode, ?string $deliveryType): ?DeliveryGovernorateRule
    {
        if (empty($deliveryType)) {
            return null;
        }

        $resolvedCode = $this->resolveStateCode($stateCode);
        $canonicalType = $this->shippingMethodAdapter->canonicalize($deliveryType);

        if (empty($canonicalType)) {
            return null;
        }

        $query = DeliveryGovernorateRule::query()
            ->where('state_code', $resolvedCode)
            ->where('delivery_type', $canonicalType)
            ->where('is_enabled', true);

        $now = now()->toDateString();
        $query->where(function ($q) use ($now) {
            $q->whereNull('effective_from')
                ->orWhere('effective_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('effective_until')
                ->orWhere('effective_until', '>=', $now);
        });

        return $query->first();
    }

    /**
     * Check if a delivery type is enabled for a given state code.
     */
    public function isDeliveryTypeEnabled(string $stateCode, ?string $deliveryType): bool
    {
        return $this->getActiveRule($stateCode, $deliveryType) !== null;
    }

    /**
     * Validate delivery point and return its frozen snapshot.
     *
     * @throws ValidationException
     */
    public function validateDeliveryPoint(string $stateCode, ?int $deliveryPointId): array
    {
        $resolvedState = $this->resolveStateCode($stateCode);

        if (! $deliveryPointId) {
            throw ValidationException::withMessages([
                'delivery_point_id' => [trans('delivery::app.validation.delivery_point_required') ?: 'نقطة الاستلام مطلوبة لطريقة التوصيل المحددة.'],
            ]);
        }

        /** @var DeliveryPoint|null $point */
        $point = DeliveryPoint::find($deliveryPointId);

        if (! $point) {
            throw ValidationException::withMessages([
                'delivery_point_id' => [trans('delivery::app.validation.delivery_point_not_found') ?: 'نقطة الاستلام المحددة غير موجودة.'],
            ]);
        }

        if (! $point->is_active) {
            throw ValidationException::withMessages([
                'delivery_point_id' => [trans('delivery::app.validation.delivery_point_inactive') ?: 'نقطة الاستلام المحددة غير مفعلة حالياً.'],
            ]);
        }

        if (strtoupper($point->state_code) !== strtoupper($resolvedState)) {
            throw ValidationException::withMessages([
                'delivery_point_id' => [trans('delivery::app.validation.delivery_point_state_mismatch') ?: 'نقطة الاستلام المحددة لا تنتمي إلى المحافظة المختارة.'],
            ]);
        }

        return [
            'id' => $point->id,
            'code' => $point->code,
            'name' => $point->name,
            'name_ar' => $point->name_ar,
            'state_code' => $point->state_code,
            'city' => $point->city,
            'address' => $point->address,
            'contact_name' => $point->contact_name,
            'contact_phone' => $point->contact_phone,
            'working_hours' => $point->working_hours,
            'snapshot_created_at' => now()->toIso8601String(),
        ];
    }
}
