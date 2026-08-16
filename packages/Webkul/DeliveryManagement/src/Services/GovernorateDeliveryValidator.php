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
     * Validate that the state code is valid for Yemen (YE).
     */
    public function isValidStateCode(string $stateCode): bool
    {
        if (empty($stateCode)) {
            return false;
        }

        return DB::table('country_states')
            ->where('country_code', 'YE')
            ->where('code', strtoupper($stateCode))
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

        $stateCode = strtoupper(trim($stateCode));
        $canonicalType = $this->shippingMethodAdapter->canonicalize($deliveryType);

        if (empty($canonicalType)) {
            return null;
        }

        $query = DeliveryGovernorateRule::query()
            ->where('state_code', $stateCode)
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
        $stateCode = strtoupper(trim($stateCode));

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

        if (strtoupper($point->state_code) !== $stateCode) {
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
