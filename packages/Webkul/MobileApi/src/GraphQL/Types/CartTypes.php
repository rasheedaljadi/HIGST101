<?php

namespace Webkul\MobileApi\GraphQL\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Webkul\Shipping\Facades\Shipping;

class CartTypes
{
    public static function cartItem(): ObjectType
    {
        return TypeRegistry::get('CartItem', fn () => new ObjectType([
            'name' => 'CartItem',
            'fields' => [
                'id' => Type::id(),
                'cartId' => ['type' => Type::id(), 'resolve' => fn ($item) => (string) ($item->cart_id ?? '')],
                'productId' => ['type' => Type::id(), 'resolve' => fn ($item) => (string) ($item->product_id ?? '')],
                'name' => ['type' => Type::string(), 'resolve' => fn ($item) => $item->name ?? ($item->product?->name ?? '')],
                'sku' => ['type' => Type::string(), 'resolve' => fn ($item) => $item->sku ?? ($item->product?->sku ?? '')],
                'type' => ['type' => Type::string(), 'resolve' => fn ($item) => $item->type ?? ($item->product?->type ?? 'simple')],
                'quantity' => Type::int(),
                'price' => ['type' => Type::float(), 'resolve' => fn ($item) => (float) ($item->price ?? 0)],
                'formattedPrice' => ['type' => Type::string(), 'resolve' => fn ($item) => core()->formatPrice($item->price ?? 0)],
                'total' => ['type' => Type::float(), 'resolve' => fn ($item) => (float) ($item->total ?? 0)],
                'formattedTotal' => ['type' => Type::string(), 'resolve' => fn ($item) => core()->formatPrice($item->total ?? 0)],
                'baseImage' => ['type' => Type::string(), 'resolve' => fn ($item) => product_image()->getProductBaseImage($item->product)['medium_image_url'] ?? null],
                'productUrlKey' => ['type' => Type::string(), 'resolve' => fn ($item) => $item->product?->url_key],
                'canChangeQty' => ['type' => Type::boolean(), 'resolve' => fn () => true],
                'product' => ['type' => CatalogTypes::product(), 'resolve' => fn ($item) => $item->product],
                'additional' => ['type' => TypeRegistry::json(), 'resolve' => fn ($item) => $item->additional],
                'options' => ['type' => TypeRegistry::json(), 'resolve' => fn ($item) => $item->additional['attributes'] ?? ($item->additional ?? [])],
                'originalPrice' => [
                    'type' => Type::float(),
                    'resolve' => function ($item) {
                        $regPrice = (float) ($item->product?->price ?? 0);
                        $curPrice = (float) ($item->price ?? 0);
                        return $regPrice > $curPrice ? $regPrice : null;
                    },
                ],
                'formattedOriginalPrice' => [
                    'type' => Type::string(),
                    'resolve' => function ($item) {
                        $regPrice = (float) ($item->product?->price ?? 0);
                        $curPrice = (float) ($item->price ?? 0);
                        return $regPrice > $curPrice ? core()->formatPrice($regPrice) : null;
                    },
                ],
                'discountPercent' => [
                    'type' => Type::float(),
                    'resolve' => function ($item) {
                        if (!empty($item->discount_percent)) return (float) $item->discount_percent;
                        $regPrice = (float) ($item->product?->price ?? 0);
                        $curPrice = (float) ($item->price ?? 0);
                        if ($regPrice > $curPrice && $curPrice > 0) {
                            return round((($regPrice - $curPrice) / $regPrice) * 100);
                        }
                        return 0;
                    },
                ],
            ],
        ]));
    }

    public static function shippingRate(): ObjectType
    {
        return TypeRegistry::get('ShippingRate', fn () => new ObjectType([
            'name' => 'ShippingRate',
            'fields' => [
                'id' => Type::id(),
                'code' => ['type' => Type::string(), 'resolve' => fn ($r) => $r->carrier.'_'.$r->method],
                'carrier' => Type::string(),
                'carrierTitle' => ['type' => Type::string(), 'resolve' => fn ($r) => $r->carrier_title],
                'method' => Type::string(),
                'methodTitle' => ['type' => Type::string(), 'resolve' => fn ($r) => $r->method_title],
                'methodDescription' => ['type' => Type::string(), 'resolve' => fn ($r) => $r->method_description],
                'label' => ['type' => Type::string(), 'resolve' => fn ($r) => $r->method_title ?: $r->carrier_title],
                'description' => ['type' => Type::string(), 'resolve' => fn ($r) => $r->method_description],
                'price' => ['type' => Type::float(), 'resolve' => fn ($r) => (float) round(core()->convertPrice($r->price ?? 0), 2)],
                'formattedPrice' => ['type' => Type::string(), 'resolve' => fn ($r) => core()->currency($r->price ?? 0)],
                'basePrice' => ['type' => Type::float(), 'resolve' => fn ($r) => (float) ($r->base_price ?? $r->price ?? 0)],
                'baseFormattedPrice' => ['type' => Type::string(), 'resolve' => fn ($r) => core()->formatBasePrice($r->base_price ?? $r->price ?? 0)],
            ],
        ]));
    }

    public static function paymentMethod(): ObjectType
    {
        return TypeRegistry::get('PaymentMethod', fn () => new ObjectType([
            'name' => 'PaymentMethod',
            'fields' => [
                'id' => ['type' => Type::id(), 'resolve' => fn ($m) => is_array($m) ? ($m['id'] ?? $m['method'] ?? 'payment_method') : ($m->method ?? 'payment_method')],
                'method' => ['type' => Type::string(), 'resolve' => fn ($m) => is_array($m) ? ($m['method'] ?? '') : ($m->method ?? '')],
                'title' => ['type' => Type::string(), 'resolve' => fn ($m) => is_array($m) ? ($m['title'] ?? $m['method_title'] ?? $m['methodTitle'] ?? '') : ($m->method_title ?? $m->title ?? '')],
                'methodTitle' => ['type' => Type::string(), 'resolve' => fn ($m) => is_array($m) ? ($m['methodTitle'] ?? $m['method_title'] ?? $m['title'] ?? '') : ($m->method_title ?? $m->title ?? '')],
                'description' => ['type' => Type::string(), 'resolve' => fn ($m) => is_array($m) ? ($m['description'] ?? '') : ($m->description ?? '')],
                'icon' => ['type' => Type::string(), 'resolve' => fn ($m) => is_array($m) ? ($m['icon'] ?? null) : null],
                'isAllowed' => ['type' => Type::boolean(), 'resolve' => fn ($m) => is_array($m) ? (bool) ($m['isAllowed'] ?? true) : true],
            ],
        ]));
    }

    public static function cart(): ObjectType
    {
        return TypeRegistry::get('Cart', fn () => new ObjectType([
            'name' => 'Cart',
            'fields' => fn () => [
                'id' => Type::id(),
                'cartToken' => ['type' => Type::string(), 'resolve' => fn () => session()->getId() ?: (string) optional(Cart::getCart())->id],
                'isGuest' => ['type' => Type::boolean(), 'resolve' => fn ($c) => (bool) $c->is_guest],
                'itemsCount' => ['type' => Type::int(), 'resolve' => fn ($c) => (int) $c->items_count],
                'itemsQty' => ['type' => Type::int(), 'resolve' => fn ($c) => (int) $c->items_qty],
                'subTotal' => [
                    'type' => Type::float(),
                    'resolve' => function ($c) {
                        $itemsSum = collect($c->items ?? [])->sum(fn ($it) => (float) ($it->total ?? 0));

                        return (float) ($itemsSum > 0 ? $itemsSum : ($c->sub_total ?? 0));
                    },
                ],
                'formattedSubTotal' => [
                    'type' => Type::string(),
                    'resolve' => function ($c) {
                        $itemsSum = collect($c->items ?? [])->sum(fn ($it) => (float) ($it->total ?? 0));
                        $val = $itemsSum > 0 ? $itemsSum : ($c->sub_total ?? 0);

                        return core()->formatPrice($val);
                    },
                ],
                'subtotal' => [
                    'type' => Type::float(),
                    'resolve' => function ($c) {
                        $itemsSum = collect($c->items ?? [])->sum(fn ($it) => (float) ($it->total ?? 0));

                        return (float) ($itemsSum > 0 ? $itemsSum : ($c->sub_total ?? 0));
                    },
                ],
                'formattedSubtotal' => [
                    'type' => Type::string(),
                    'resolve' => function ($c) {
                        $itemsSum = collect($c->items ?? [])->sum(fn ($it) => (float) ($it->total ?? 0));
                        $val = $itemsSum > 0 ? $itemsSum : ($c->sub_total ?? 0);

                        return core()->formatPrice($val);
                    },
                ],
                'taxAmount' => ['type' => Type::float(), 'resolve' => fn ($c) => (float) ($c->tax_total ?? 0)],
                'formattedTaxAmount' => ['type' => Type::string(), 'resolve' => fn ($c) => core()->formatPrice($c->tax_total ?? 0)],
                'shippingAmount' => ['type' => Type::float(), 'resolve' => fn ($c) => (float) (optional($c->selected_shipping_rate)->price ?? $c->shipping_amount ?? 0)],
                'formattedShippingAmount' => ['type' => Type::string(), 'resolve' => fn ($c) => core()->formatPrice(optional($c->selected_shipping_rate)->price ?? $c->shipping_amount ?? 0)],
                'discountAmount' => ['type' => Type::float(), 'resolve' => fn ($c) => (float) ($c->discount_amount ?? 0)],
                'formattedDiscountAmount' => ['type' => Type::string(), 'resolve' => fn ($c) => core()->formatPrice($c->discount_amount ?? 0)],
                'grandTotal' => [
                    'type' => Type::float(),
                    'resolve' => function ($c) {
                        $itemsSum = collect($c->items ?? [])->sum(fn ($it) => (float) ($it->total ?? 0));
                        $shipping = (float) (optional($c->selected_shipping_rate)->price ?? $c->shipping_amount ?? 0);
                        $tax = (float) ($c->tax_total ?? 0);
                        $discount = (float) ($c->discount_amount ?? 0);
                        $expected = max(0, $itemsSum + $tax + $shipping - $discount);
                        if ($itemsSum > 0 && abs((float) ($c->grand_total ?? 0) - $expected) > 0.01) {
                            return (float) $expected;
                        }

                        return (float) ($c->grand_total ?? ($itemsSum > 0 ? $expected : 0));
                    },
                ],
                'formattedGrandTotal' => [
                    'type' => Type::string(),
                    'resolve' => function ($c) {
                        $itemsSum = collect($c->items ?? [])->sum(fn ($it) => (float) ($it->total ?? 0));
                        $shipping = (float) (optional($c->selected_shipping_rate)->price ?? $c->shipping_amount ?? 0);
                        $tax = (float) ($c->tax_total ?? 0);
                        $discount = (float) ($c->discount_amount ?? 0);
                        $expected = max(0, $itemsSum + $tax + $shipping - $discount);
                        if ($itemsSum > 0 && abs((float) ($c->grand_total ?? 0) - $expected) > 0.01) {
                            return core()->formatPrice($expected);
                        }

                        return core()->formatPrice($c->grand_total ?? ($itemsSum > 0 ? $expected : 0));
                    },
                ],
                'couponCode' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->coupon_code],
                'success' => ['type' => Type::boolean(), 'resolve' => fn () => true],
                'message' => ['type' => Type::string(), 'resolve' => fn () => 'Operation completed successfully.'],
                'sessionToken' => ['type' => Type::string(), 'resolve' => fn () => session()->getId()],
                'items' => [
                    'type' => TypeRegistry::connection('CartItem', self::cartItem()),
                    'resolve' => function ($c) {
                        $items = $c->items ?? [];
                        $edges = collect($items)->map(fn ($it) => ['node' => $it, 'cursor' => (string) $it->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'shippingRates' => [
                    'type' => TypeRegistry::connection('ShippingRate', self::shippingRate()),
                    'resolve' => function ($c) {
                        $rates = $c->shipping_rates ?? [];
                        if (empty($rates) || (is_object($rates) && method_exists($rates, 'isEmpty') && $rates->isEmpty())) {
                            Shipping::collectRates();
                            $rates = $c->shipping_rates ?? [];
                        }
                        $edges = collect($rates)->map(fn ($r) => ['node' => $r, 'cursor' => (string) $r->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'payment' => ['type' => self::paymentMethod(), 'resolve' => fn ($c) => $c->payment],
                'paymentGatewayUrl' => ['type' => Type::string(), 'resolve' => fn () => null],
                'paymentData' => ['type' => Type::string(), 'resolve' => fn () => null],
            ],
        ]));
    }

    public static function createCheckoutAddressInput(): InputObjectType
    {
        return TypeRegistry::get('createCheckoutAddressInput', fn () => new InputObjectType([
            'name' => 'createCheckoutAddressInput',
            'fields' => [
                'billing' => TypeRegistry::json(),
                'shipping' => TypeRegistry::json(),
                'billingFirstName' => Type::string(),
                'billingLastName' => Type::string(),
                'billingEmail' => Type::string(),
                'billingPhoneNumber' => Type::string(),
                'billingAddress' => Type::string(),
                'billingCountry' => Type::string(),
                'billingState' => Type::string(),
                'billingCity' => Type::string(),
                'billingPostcode' => Type::string(),
                'billingCompanyName' => Type::string(),
                'billingVatId' => Type::string(),
                'shippingFirstName' => Type::string(),
                'shippingLastName' => Type::string(),
                'shippingEmail' => Type::string(),
                'shippingPhoneNumber' => Type::string(),
                'shippingAddress' => Type::string(),
                'shippingCountry' => Type::string(),
                'shippingState' => Type::string(),
                'shippingCity' => Type::string(),
                'shippingPostcode' => Type::string(),
                'shippingCompanyName' => Type::string(),
                'shippingVatId' => Type::string(),
                'useForShipping' => Type::boolean(),
            ],
        ]));
    }

    public static function createCheckoutShippingMethodInput(): InputObjectType
    {
        return TypeRegistry::get('createCheckoutShippingMethodInput', fn () => new InputObjectType([
            'name' => 'createCheckoutShippingMethodInput',
            'fields' => [
                'shippingMethod' => Type::nonNull(Type::string()),
            ],
        ]));
    }

    public static function createCheckoutPaymentMethodInput(): InputObjectType
    {
        return TypeRegistry::get('createCheckoutPaymentMethodInput', fn () => new InputObjectType([
            'name' => 'createCheckoutPaymentMethodInput',
            'fields' => [
                'paymentMethod' => Type::nonNull(Type::string()),
            ],
        ]));
    }

    public static function createApplyCouponInput(): InputObjectType
    {
        return TypeRegistry::get('createApplyCouponInput', fn () => new InputObjectType([
            'name' => 'createApplyCouponInput',
            'fields' => [
                'code' => Type::nonNull(Type::string()),
            ],
        ]));
    }

    public static function createRemoveCouponInput(): InputObjectType
    {
        return TypeRegistry::get('createRemoveCouponInput', fn () => new InputObjectType([
            'name' => 'createRemoveCouponInput',
            'fields' => [
                'code' => Type::string(),
            ],
        ]));
    }
}
