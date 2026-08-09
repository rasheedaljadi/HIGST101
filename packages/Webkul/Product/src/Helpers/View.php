<?php

namespace Webkul\Product\Helpers;

use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Product\Contracts\Product;

class View
{
    /**
     * Returns the visible custom attributes
     *
     * @param  Product  $product
     * @return void|array
     */
    public function getAdditionalData($product)
    {
        $data = [];

        $attributes = $product->attribute_family->custom_attributes()->where('attributes.is_visible_on_front', 1)->get();

        $attributeOptionRepository = app(AttributeOptionRepository::class);

        foreach ($attributes as $attribute) {
            $value = $product->{$attribute->code};

            if ($attribute->type == 'boolean') {
                $value = $value ? 'Yes' : 'No';
            } elseif ($value) {
                if ($attribute->type == 'select') {
                    $attributeOption = $attributeOptionRepository->find($value);

                    if ($attributeOption) {
                        $value = $attributeOption->label ?? null;

                        if (! $value) {
                            continue;
                        }
                    }
                } elseif (
                    $attribute->type == 'multiselect'
                    || $attribute->type == 'checkbox'
                ) {
                    $labels = [];

                    $attributeOptions = $attributeOptionRepository->findWhereIn('id', explode(',', $value));

                    foreach ($attributeOptions as $attributeOption) {
                        if ($label = $attributeOption->label) {
                            $labels[] = $label;
                        }
                    }

                    $value = implode(', ', $labels);
                }
            }

            $data[] = [
                'id' => $attribute->id,
                'code' => $attribute->code,
                'label' => $attribute->name,
                'value' => $value,
                'admin_name' => $attribute->admin_name,
                'type' => $attribute->type,
            ];
        }

        return $data;
    }

    /**
     * Get dropshipping fulfillment transparency metadata.
     *
     * @param  Product  $product
     */
    public function getDropshippingMetadata($product): array
    {
        $originCountry = core()->getConfigData('catalog.products.dropshipping.default_origin')
            ?: ($product->country_of_origin ?? 'International Overseas Warehouse (Express Freight)');

        $leadTimeDays = (int) (core()->getConfigData('catalog.products.dropshipping.dispatch_lead_time') ?: 2);
        $minDays = $leadTimeDays + 3;
        $maxDays = $leadTimeDays + 6;

        return [
            'origin_country' => $originCountry,
            'dispatch_lead_time_days' => $leadTimeDays,
            'estimated_delivery_window' => "{$minDays} - {$maxDays} Business Days",
            'tracking_available' => true,
            'local_rma_days' => (int) (core()->getConfigData('sales.shipping.rma.default_return_days') ?: 14),
            'return_center_location' => core()->getConfigData('sales.shipping.rma.return_center_address') ?: 'Local HIGEST Return Hub Processing',
        ];
    }
}
