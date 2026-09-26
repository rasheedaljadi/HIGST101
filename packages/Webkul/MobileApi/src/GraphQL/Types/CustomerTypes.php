<?php

namespace Webkul\MobileApi\GraphQL\Types;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CustomerTypes
{
    public static function address(): ObjectType
    {
        return TypeRegistry::get('CustomerAddress', fn () => new ObjectType([
            'name' => 'CustomerAddress',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($a) => (int) $a->id],
                'addressId' => ['type' => Type::int(), 'resolve' => fn ($a) => (int) $a->id],
                'parentAddressId' => ['type' => Type::int(), 'resolve' => fn ($a) => (int) ($a->parent_address_id ?? 0)],
                'customerId' => ['type' => Type::int(), 'resolve' => fn ($a) => (int) ($a->customer_id ?? 0)],
                'cartId' => ['type' => Type::int(), 'resolve' => fn ($a) => (int) ($a->cart_id ?? 0)],
                'orderId' => ['type' => Type::int(), 'resolve' => fn ($a) => (int) ($a->order_id ?? 0)],
                'addressType' => ['type' => Type::string(), 'resolve' => fn ($a) => $a->address_type ?? 'customer'],
                'firstName' => ['type' => Type::string(), 'resolve' => fn ($a) => $a->first_name],
                'lastName' => ['type' => Type::string(), 'resolve' => fn ($a) => $a->last_name],
                'name' => ['type' => Type::string(), 'resolve' => fn ($a) => ($a->first_name ?? '').' '.($a->last_name ?? '')],
                'gender' => ['type' => Type::string(), 'resolve' => fn ($a) => (string) ($a->gender ?? '')],
                'companyName' => ['type' => Type::string(), 'resolve' => fn ($a) => $a->company_name],
                'vatId' => ['type' => Type::string(), 'resolve' => fn ($a) => $a->vat_id ?? ''],
                'address' => ['type' => Type::string(), 'resolve' => fn ($a) => is_array($a->address) ? implode(', ', $a->address) : (string) ($a->address ?? '')],
                'address1' => ['type' => Type::string(), 'resolve' => function ($a) {
                    if (is_array($a->address)) {
                        return $a->address[0] ?? '';
                    }
                    $lines = explode("\n", str_replace("\r", '', (string) ($a->address ?? '')));

                    return $lines[0] ?? (string) ($a->address ?? '');
                }],
                'address2' => ['type' => Type::string(), 'resolve' => function ($a) {
                    if (is_array($a->address)) {
                        return $a->address[1] ?? '';
                    }
                    $lines = explode("\n", str_replace("\r", '', (string) ($a->address ?? '')));

                    return $lines[1] ?? '';
                }],
                'city' => Type::string(),
                'state' => Type::string(),
                'country' => Type::string(),
                'postcode' => Type::string(),
                'phone' => Type::string(),
                'email' => ['type' => Type::string(), 'resolve' => fn ($a) => $a->email ?? ($a->customer?->email ?? '')],
                'defaultAddress' => ['type' => Type::boolean(), 'resolve' => fn ($a) => (bool) $a->default_address],
                'useForShipping' => ['type' => Type::boolean(), 'resolve' => fn ($a) => (bool) ($a->use_for_shipping ?? false)],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($a) => (string) ($a->created_at ?? '')],
                'updatedAt' => ['type' => Type::string(), 'resolve' => fn ($a) => (string) ($a->updated_at ?? '')],
            ],
        ]));
    }

    public static function orderPayment(): ObjectType
    {
        return TypeRegistry::get('OrderPayment', fn () => new ObjectType([
            'name' => 'OrderPayment',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($p) => (int) ($p->id ?? 0)],
                'method' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->method ?? '')],
                'methodTitle' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->method_title ?? core()->getConfigData('sales.paymentmethods.'.($p->method ?? '').'.title') ?? ($p->method ?? ''))],
            ],
        ]));
    }

    public static function orderItem(): ObjectType
    {
        return TypeRegistry::get('OrderItem', fn () => new ObjectType([
            'name' => 'OrderItem',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) $it->id],
                'name' => ['type' => Type::string(), 'resolve' => fn ($it) => (string) ($it->name ?? '')],
                'sku' => ['type' => Type::string(), 'resolve' => fn ($it) => (string) ($it->sku ?? '')],
                'type' => ['type' => Type::string(), 'resolve' => fn ($it) => (string) ($it->type ?? 'simple')],
                'additional' => ['type' => Type::string(), 'resolve' => fn ($it) => is_array($it->additional) ? json_encode($it->additional) : (string) ($it->additional ?? '')],
                'qtyOrdered' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) ($it->qty_ordered ?? 0)],
                'qtyShipped' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) ($it->qty_shipped ?? 0)],
                'qtyInvoiced' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) ($it->qty_invoiced ?? 0)],
                'qtyCanceled' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) ($it->qty_canceled ?? 0)],
                'qtyRefunded' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) ($it->qty_refunded ?? 0)],
                'price' => ['type' => Type::float(), 'resolve' => fn ($it) => (float) ($it->price ?? 0)],
                'formattedPrice' => ['type' => Type::string(), 'resolve' => fn ($it) => core()->formatPrice($it->price ?? 0)],
                'total' => ['type' => Type::float(), 'resolve' => fn ($it) => (float) ($it->total ?? 0)],
                'formattedTotal' => ['type' => Type::string(), 'resolve' => fn ($it) => core()->formatPrice($it->total ?? 0)],
            ],
        ]));
    }

    public static function order(): ObjectType
    {
        return TypeRegistry::get('Order', fn () => new ObjectType([
            'name' => 'Order',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($o) => (int) $o->id],
                'orderId' => ['type' => Type::id(), 'resolve' => fn ($o) => (string) $o->id],
                'incrementId' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) $o->increment_id],
                'orderIncrementId' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) $o->increment_id],
                'success' => ['type' => Type::boolean(), 'resolve' => fn () => true],
                'message' => ['type' => Type::string(), 'resolve' => fn () => 'Order placed successfully.'],
                'status' => Type::string(),
                'statusLabel' => ['type' => Type::string(), 'resolve' => fn ($o) => $o->status_label ?? $o->status],
                'channelName' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->channel_name ?? '')],
                'customerEmail' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->customer_email ?? '')],
                'customerFirstName' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->customer_first_name ?? '')],
                'customerLastName' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->customer_last_name ?? '')],
                'shippingMethod' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->shipping_method ?? '')],
                'shippingTitle' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->shipping_title ?? '')],
                'paymentTitle' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->payment?->method_title ?? core()->getConfigData('sales.payment_methods.'.($o->payment?->method ?? '').'.title') ?? ($o->payment?->method ?? ''))],
                'paymentMethod' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->payment?->method ?? '')],
                'couponCode' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->coupon_code ?? '')],
                'totalItemCount' => ['type' => Type::int(), 'resolve' => fn ($o) => (int) ($o->total_item_count ?? count($o->items ?? []))],
                'totalQtyOrdered' => ['type' => Type::int(), 'resolve' => fn ($o) => (int) ($o->total_qty_ordered ?? 0)],
                'grandTotal' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->grand_total ?? 0)],
                'formattedGrandTotal' => ['type' => Type::string(), 'resolve' => fn ($o) => core()->formatPrice($o->grand_total ?? 0)],
                'baseGrandTotal' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->base_grand_total ?? 0)],
                'grandTotalInvoiced' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->grand_total_invoiced ?? 0)],
                'grandTotalRefunded' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->grand_total_refunded ?? 0)],
                'subTotal' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->sub_total ?? 0)],
                'formattedSubTotal' => ['type' => Type::string(), 'resolve' => fn ($o) => core()->formatPrice($o->sub_total ?? 0)],
                'baseSubTotal' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->base_sub_total ?? 0)],
                'taxAmount' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->tax_amount ?? 0)],
                'formattedTaxAmount' => ['type' => Type::string(), 'resolve' => fn ($o) => core()->formatPrice($o->tax_amount ?? 0)],
                'baseTaxAmount' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->base_tax_amount ?? 0)],
                'discountAmount' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->discount_amount ?? 0)],
                'formattedDiscountAmount' => ['type' => Type::string(), 'resolve' => fn ($o) => core()->formatPrice($o->discount_amount ?? 0)],
                'baseDiscountAmount' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->base_discount_amount ?? 0)],
                'shippingAmount' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->shipping_amount ?? 0)],
                'formattedShippingAmount' => ['type' => Type::string(), 'resolve' => fn ($o) => core()->formatPrice($o->shipping_amount ?? 0)],
                'baseShippingAmount' => ['type' => Type::float(), 'resolve' => fn ($o) => (float) ($o->base_shipping_amount ?? 0)],
                'baseCurrencyCode' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->base_currency_code ?? '')],
                'channelCurrencyCode' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->channel_currency_code ?? '')],
                'orderCurrencyCode' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->order_currency_code ?? '')],
                'payment' => [
                    'type' => self::orderPayment(),
                    'resolve' => fn ($o) => $o->payment,
                ],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) $o->created_at],
                'updatedAt' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) $o->updated_at],
                'billingAddress' => ['type' => self::address(), 'resolve' => fn ($o) => $o->billing_address],
                'shippingAddress' => ['type' => self::address(), 'resolve' => fn ($o) => $o->shipping_address],
                'addresses' => [
                    'type' => TypeRegistry::connection('OrderAddress', self::address()),
                    'resolve' => function ($o) {
                        $addresses = $o->addresses ?? [];
                        $edges = collect($addresses)->map(fn ($a) => ['node' => $a, 'cursor' => (string) $a->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'items' => [
                    'type' => TypeRegistry::connection('OrderItem', self::orderItem()),
                    'resolve' => function ($o) {
                        $items = $o->items ?? [];
                        $edges = collect($items)->map(fn ($it) => ['node' => $it, 'cursor' => (string) $it->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]));
    }

    public static function orderShipmentItem(): ObjectType
    {
        return TypeRegistry::get('OrderShipmentItem', fn () => new ObjectType([
            'name' => 'OrderShipmentItem',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) $it->id],
                'name' => ['type' => Type::string(), 'resolve' => fn ($it) => (string) ($it->name ?? '')],
                'sku' => ['type' => Type::string(), 'resolve' => fn ($it) => (string) ($it->sku ?? '')],
                'qty' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) ($it->qty ?? 0)],
            ],
        ]));
    }

    public static function orderShipment(): ObjectType
    {
        return TypeRegistry::get('OrderShipment', fn () => new ObjectType([
            'name' => 'OrderShipment',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) $s->id],
                'status' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) ($s->status ?? 'processing')],
                'trackNumber' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) ($s->track_number ?? ($s->carrier_title ? 'TRK-'.$s->id : ''))],
                'shippingNumber' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) ($s->id ?? '')],
                'carrierTitle' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) ($s->carrier_title ?? 'Default Carrier')],
                'totalQty' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->total_qty ?? 0)],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) $s->created_at],
                'updatedAt' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) $s->updated_at],
                'items' => [
                    'type' => TypeRegistry::connection('OrderShipmentItem', self::orderShipmentItem()),
                    'resolve' => function ($s) {
                        $items = $s->items ?? [];
                        $edges = collect($items)->map(fn ($it) => ['node' => $it, 'cursor' => (string) $it->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]));
    }

    public static function orderInvoiceItem(): ObjectType
    {
        return TypeRegistry::get('OrderInvoiceItem', fn () => new ObjectType([
            'name' => 'OrderInvoiceItem',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) $it->id],
                'parentId' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) ($it->parent_id ?? 0)],
                'sku' => ['type' => Type::string(), 'resolve' => fn ($it) => (string) ($it->sku ?? '')],
                'name' => ['type' => Type::string(), 'resolve' => fn ($it) => (string) ($it->name ?? '')],
                'description' => ['type' => Type::string(), 'resolve' => fn ($it) => (string) ($it->description ?? '')],
                'qty' => ['type' => Type::int(), 'resolve' => fn ($it) => (int) ($it->qty ?? 0)],
                'price' => ['type' => Type::float(), 'resolve' => fn ($it) => (float) ($it->price ?? 0)],
                'total' => ['type' => Type::float(), 'resolve' => fn ($it) => (float) ($it->total ?? 0)],
                'basePrice' => ['type' => Type::float(), 'resolve' => fn ($it) => (float) ($it->base_price ?? 0)],
                'baseTotal' => ['type' => Type::float(), 'resolve' => fn ($it) => (float) ($it->base_total ?? 0)],
                'taxAmount' => ['type' => Type::float(), 'resolve' => fn ($it) => (float) ($it->tax_amount ?? 0)],
            ],
        ]));
    }

    public static function orderInvoice(): ObjectType
    {
        return TypeRegistry::get('OrderInvoice', fn () => new ObjectType([
            'name' => 'OrderInvoice',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($inv) => (int) $inv->id],
                'incrementId' => ['type' => Type::string(), 'resolve' => fn ($inv) => (string) ($inv->increment_id ?? $inv->id)],
                'state' => ['type' => Type::string(), 'resolve' => fn ($inv) => (string) ($inv->state ?? 'paid')],
                'totalQty' => ['type' => Type::int(), 'resolve' => fn ($inv) => (int) ($inv->total_qty ?? 0)],
                'grandTotal' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->grand_total ?? 0)],
                'baseGrandTotal' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->base_grand_total ?? 0)],
                'subTotal' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->sub_total ?? 0)],
                'baseSubTotal' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->base_sub_total ?? 0)],
                'shippingAmount' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->shipping_amount ?? 0)],
                'baseShippingAmount' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->base_shipping_amount ?? 0)],
                'taxAmount' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->tax_amount ?? 0)],
                'baseTaxAmount' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->base_tax_amount ?? 0)],
                'discountAmount' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->discount_amount ?? 0)],
                'baseDiscountAmount' => ['type' => Type::float(), 'resolve' => fn ($inv) => (float) ($inv->base_discount_amount ?? 0)],
                'baseCurrencyCode' => ['type' => Type::string(), 'resolve' => fn ($inv) => (string) ($inv->base_currency_code ?? '')],
                'orderCurrencyCode' => ['type' => Type::string(), 'resolve' => fn ($inv) => (string) ($inv->order_currency_code ?? '')],
                'transactionId' => ['type' => Type::string(), 'resolve' => fn ($inv) => (string) ($inv->transaction_id ?? '')],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($inv) => (string) $inv->created_at],
                'updatedAt' => ['type' => Type::string(), 'resolve' => fn ($inv) => (string) $inv->updated_at],
                'items' => [
                    'type' => TypeRegistry::connection('OrderInvoiceItem', self::orderInvoiceItem()),
                    'resolve' => function ($inv) {
                        $items = $inv->items ?? [];
                        $edges = collect($items)->map(fn ($it) => ['node' => $it, 'cursor' => (string) $it->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]));
    }

    public static function productReview(): ObjectType
    {
        return TypeRegistry::get('ProductReview', fn () => new ObjectType([
            'name' => 'ProductReview',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($r) => (int) $r->id],
                'name' => ['type' => Type::string(), 'resolve' => fn ($r) => (string) ($r->name ?? 'Customer')],
                'title' => ['type' => Type::string(), 'resolve' => fn ($r) => (string) ($r->title ?? '')],
                'rating' => ['type' => Type::int(), 'resolve' => fn ($r) => (int) ($r->rating ?? 5)],
                'comment' => ['type' => Type::string(), 'resolve' => fn ($r) => (string) ($r->comment ?? '')],
                'status' => ['type' => Type::string(), 'resolve' => fn ($r) => (string) ($r->status ?? 'approved')],
                'product' => [
                    'type' => CatalogTypes::product(),
                    'resolve' => fn ($r) => $r->product,
                ],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($r) => (string) $r->created_at],
                'updatedAt' => ['type' => Type::string(), 'resolve' => fn ($r) => (string) $r->updated_at],
            ],
        ]));
    }

    public static function customer(): ObjectType
    {
        return TypeRegistry::get('Customer', fn () => new ObjectType([
            'name' => 'Customer',
            'fields' => [
                'id' => Type::id(),
                'firstName' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->first_name],
                'lastName' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->last_name],
                'name' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->name ?? ($c->first_name.' '.$c->last_name)],
                'email' => Type::string(),
                'phone' => Type::string(),
                'dateOfBirth' => ['type' => Type::string(), 'resolve' => fn ($c) => (string) ($c->date_of_birth ?? '')],
                'gender' => ['type' => Type::string(), 'resolve' => fn ($c) => (string) ($c->gender ?? '')],
                'status' => ['type' => Type::boolean(), 'resolve' => fn ($c) => (bool) $c->status],
                'isVerified' => ['type' => Type::boolean(), 'resolve' => fn ($c) => (bool) ($c->is_verified ?? true)],
                'isSuspended' => ['type' => Type::boolean(), 'resolve' => fn ($c) => (bool) ($c->is_suspended ?? false)],
                'subscribedToNewsLetter' => ['type' => Type::boolean(), 'resolve' => fn ($c) => (bool) ($c->subscribed_to_news_letter ?? false)],
                'customerGroupId' => ['type' => Type::id(), 'resolve' => fn ($c) => (string) ($c->customer_group_id ?? '1')],
                'apiToken' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->api_token ?? ($c->token ?? '')],
                'token' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->api_token ?? ($c->token ?? '')],
                'rememberToken' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->remember_token ?? ''],
                'image' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->image ? asset('storage/'.$c->image) : null],
                'addresses' => [
                    'type' => TypeRegistry::connection('CustomerAddress', self::address()),
                    'resolve' => function ($c) {
                        $addresses = $c->addresses ?? [];
                        $edges = collect($addresses)->map(fn ($a) => ['node' => $a, 'cursor' => (string) $a->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]));
    }

    public static function walletTransaction(): ObjectType
    {
        return TypeRegistry::get('WalletTransaction', fn () => new ObjectType([
            'name' => 'WalletTransaction',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($t) => (int) $t->id],
                'type' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) $t->type],
                'direction' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) $t->direction],
                'amount' => ['type' => Type::float(), 'resolve' => fn ($t) => (float) ($t->amount ?? 0)],
                'formattedAmount' => ['type' => Type::string(), 'resolve' => fn ($t) => core()->formatPrice($t->amount ?? 0)],
                'runningBalance' => ['type' => Type::float(), 'resolve' => fn ($t) => (float) ($t->running_balance ?? 0)],
                'formattedRunningBalance' => ['type' => Type::string(), 'resolve' => fn ($t) => core()->formatPrice($t->running_balance ?? 0)],
                'description' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->description ?? '')],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) $t->created_at],
            ],
        ]));
    }

    public static function wallet(): ObjectType
    {
        return TypeRegistry::get('Wallet', fn () => new ObjectType([
            'name' => 'Wallet',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($w) => (int) ($w->id ?? 0)],
                'totalBalance' => ['type' => Type::float(), 'resolve' => fn ($w) => (float) ($w->total_balance ?? 0)],
                'formattedTotalBalance' => ['type' => Type::string(), 'resolve' => fn ($w) => core()->formatPrice($w->total_balance ?? 0)],
                'availableBalance' => ['type' => Type::float(), 'resolve' => fn ($w) => (float) ($w->available_balance ?? 0)],
                'formattedAvailableBalance' => ['type' => Type::string(), 'resolve' => fn ($w) => core()->formatPrice($w->available_balance ?? 0)],
                'heldBalance' => ['type' => Type::float(), 'resolve' => fn ($w) => (float) ($w->held_balance ?? 0)],
                'promoBalance' => ['type' => Type::float(), 'resolve' => fn ($w) => (float) ($w->promo_balance ?? 0)],
                'cashBalance' => ['type' => Type::float(), 'resolve' => fn ($w) => (float) ($w->cash_balance ?? 0)],
                'currencyCode' => ['type' => Type::string(), 'resolve' => fn ($w) => (string) ($w->currency_code ?? core()->getBaseCurrencyCode() ?? 'SAR')],
                'status' => ['type' => Type::string(), 'resolve' => fn ($w) => (string) ($w->status ?? 'active')],
                'transactions' => [
                    'type' => TypeRegistry::connection('WalletTransaction', self::walletTransaction()),
                    'resolve' => function ($w) {
                        $txs = method_exists($w, 'transactions') ? $w->transactions()->latest()->get() : ($w->transactions ?? []);
                        $edges = collect($txs)->map(fn ($t) => ['node' => $t, 'cursor' => (string) $t->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]));
    }

    public static function deliveryTracking(): ObjectType
    {
        return TypeRegistry::get('DeliveryTracking', fn () => new ObjectType([
            'name' => 'DeliveryTracking',
            'fields' => [
                'orderId' => ['type' => Type::id(), 'resolve' => fn ($d) => (string) ($d['order_id'] ?? '')],
                'status' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['status'] ?? 'pending')],
                'statusLabel' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['status_label'] ?? $d['status'] ?? 'قيد المعالجة')],
                'deliveryType' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['delivery_type'] ?? 'home_delivery')],
                'trackingNumber' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['tracking_number'] ?? '')],
                'courierName' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['courier_name'] ?? '')],
                'courierPhone' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['courier_phone'] ?? '')],
                'assignedAt' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['assigned_at'] ?? '')],
                'deliveredAt' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['delivered_at'] ?? '')],
                'notes' => ['type' => Type::string(), 'resolve' => fn ($d) => (string) ($d['notes'] ?? '')],
            ],
        ]));
    }

    public static function channel(): ObjectType
    {
        return TypeRegistry::get('Channel', fn () => new ObjectType([
            'name' => 'Channel',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($ch) => (int) ($ch->id ?? 0)],
                'code' => ['type' => Type::string(), 'resolve' => fn ($ch) => (string) ($ch->code ?? '')],
                'name' => ['type' => Type::string(), 'resolve' => fn ($ch) => (string) ($ch->name ?? '')],
                'translation' => [
                    'type' => TypeRegistry::get('ChannelTranslation', fn () => new ObjectType([
                        'name' => 'ChannelTranslation',
                        'fields' => [
                            'name' => ['type' => Type::string(), 'resolve' => fn ($tr) => (string) (is_array($tr) ? ($tr['name'] ?? '') : ($tr->name ?? ''))],
                        ],
                    ])),
                    'resolve' => fn ($ch) => ['name' => (string) ($ch?->name ?? 'Default Channel')],
                ],
            ],
        ]));
    }

    public static function wishlist(): ObjectType
    {
        return TypeRegistry::get('Wishlist', fn () => new ObjectType([
            'name' => 'Wishlist',
            'fields' => [
                'id' => ['type' => Type::id(), 'resolve' => fn ($w) => is_array($w) ? ((string) ($w['id'] ?? '')) : ('/api/shop/wishlists/'.$w->id)],
                '_id' => ['type' => Type::int(), 'resolve' => fn ($w) => is_array($w) ? ((int) ($w['_id'] ?? $w['id'] ?? 0)) : ((int) $w->id)],
                'product' => [
                    'type' => CatalogTypes::product(),
                    'resolve' => fn ($w) => is_array($w) ? ($w['product'] ?? null) : $w->product,
                ],
                'customer' => [
                    'type' => self::customer(),
                    'resolve' => fn ($w) => is_array($w) ? ($w['customer'] ?? null) : $w->customer,
                ],
                'channel' => [
                    'type' => self::channel(),
                    'resolve' => fn ($w) => is_array($w) ? ($w['channel'] ?? core()->getCurrentChannel()) : ($w->channel ?? core()->getCurrentChannel()),
                ],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($w) => is_array($w) ? ((string) ($w['createdAt'] ?? $w['created_at'] ?? '')) : ((string) $w->created_at)],
                'updatedAt' => ['type' => Type::string(), 'resolve' => fn ($w) => is_array($w) ? ((string) ($w['updatedAt'] ?? $w['updated_at'] ?? '')) : ((string) $w->updated_at)],
            ],
        ]));
    }

    public static function createWishlistInput(): InputObjectType
    {
        return TypeRegistry::get('createWishlistInput', fn () => new InputObjectType([
            'name' => 'createWishlistInput',
            'fields' => [
                'productId' => Type::nonNull(Type::id()),
            ],
        ]));
    }

    public static function deleteWishlistInput(): InputObjectType
    {
        return TypeRegistry::get('deleteWishlistInput', fn () => new InputObjectType([
            'name' => 'deleteWishlistInput',
            'fields' => [
                'id' => Type::nonNull(Type::id()),
            ],
        ]));
    }

    public static function moveWishlistToCartInput(): InputObjectType
    {
        return TypeRegistry::get('moveWishlistToCartInput', fn () => new InputObjectType([
            'name' => 'moveWishlistToCartInput',
            'fields' => [
                'wishlistItemId' => Type::nonNull(Type::id()),
                'quantity' => ['type' => Type::int(), 'defaultValue' => 1],
            ],
        ]));
    }

    public static function wishlistToCart(): ObjectType
    {
        return TypeRegistry::get('WishlistToCart', fn () => new ObjectType([
            'name' => 'WishlistToCart',
            'fields' => [
                'message' => Type::string(),
                'success' => Type::boolean(),
            ],
        ]));
    }

    public static function compareItem(): ObjectType
    {
        return TypeRegistry::get('CompareItem', fn () => new ObjectType([
            'name' => 'CompareItem',
            'fields' => [
                'id' => ['type' => Type::id(), 'resolve' => fn ($item) => is_array($item) ? ((string) ($item['id'] ?? '')) : ('/api/shop/compare-items/'.$item->id)],
                '_id' => ['type' => Type::int(), 'resolve' => fn ($item) => is_array($item) ? ((int) ($item['_id'] ?? $item['id'] ?? 0)) : ((int) $item->id)],
                'product' => [
                    'type' => CatalogTypes::product(),
                    'resolve' => fn ($item) => is_array($item) ? ($item['product'] ?? null) : $item->product,
                ],
                'customer' => [
                    'type' => self::customer(),
                    'resolve' => fn ($item) => is_array($item) ? ($item['customer'] ?? null) : $item->customer,
                ],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($item) => is_array($item) ? ((string) ($item['createdAt'] ?? $item['created_at'] ?? '')) : ((string) $item->created_at)],
                'updatedAt' => ['type' => Type::string(), 'resolve' => fn ($item) => is_array($item) ? ((string) ($item['updatedAt'] ?? $item['updated_at'] ?? '')) : ((string) $item->updated_at)],
            ],
        ]));
    }

    public static function createCompareItemInput(): InputObjectType
    {
        return TypeRegistry::get('createCompareItemInput', fn () => new InputObjectType([
            'name' => 'createCompareItemInput',
            'fields' => [
                'productId' => Type::nonNull(Type::id()),
            ],
        ]));
    }

    public static function deleteCompareItemInput(): InputObjectType
    {
        return TypeRegistry::get('deleteCompareItemInput', fn () => new InputObjectType([
            'name' => 'deleteCompareItemInput',
            'fields' => [
                'id' => Type::nonNull(Type::id()),
            ],
        ]));
    }

    public static function createDeleteAllCompareItemsInput(): InputObjectType
    {
        return TypeRegistry::get('createDeleteAllCompareItemsInput', fn () => new InputObjectType([
            'name' => 'createDeleteAllCompareItemsInput',
            'fields' => [
                'clientMutationId' => Type::string(),
            ],
        ]));
    }

    public static function createAddUpdateCustomerAddressInput(): InputObjectType
    {
        return TypeRegistry::get('createAddUpdateCustomerAddressInput', fn () => new InputObjectType([
            'name' => 'createAddUpdateCustomerAddressInput',
            'fields' => [
                'addressId' => Type::int(),
                'firstName' => Type::string(),
                'lastName' => Type::string(),
                'email' => Type::string(),
                'phone' => Type::string(),
                'address' => Type::string(),
                'address1' => Type::string(),
                'address2' => Type::string(),
                'country' => Type::string(),
                'state' => Type::string(),
                'city' => Type::string(),
                'postcode' => Type::string(),
                'companyName' => Type::string(),
                'vatId' => Type::string(),
                'useForShipping' => Type::boolean(),
                'defaultAddress' => Type::boolean(),
            ],
        ]));
    }

    public static function createDeleteCustomerAddressInput(): InputObjectType
    {
        return TypeRegistry::get('createDeleteCustomerAddressInput', fn () => new InputObjectType([
            'name' => 'createDeleteCustomerAddressInput',
            'fields' => [
                'id' => Type::id(),
                'addressId' => Type::id(),
            ],
        ]));
    }

    public static function createCustomerProfileUpdateInput(): InputObjectType
    {
        return TypeRegistry::get('createCustomerProfileUpdateInput', fn () => new InputObjectType([
            'name' => 'createCustomerProfileUpdateInput',
            'fields' => [
                'firstName' => Type::string(),
                'lastName' => Type::string(),
                'email' => Type::string(),
                'phone' => Type::string(),
                'gender' => Type::string(),
                'dateOfBirth' => Type::string(),
                'currentPassword' => Type::string(),
                'password' => Type::string(),
                'subscribedToNewsLetter' => Type::boolean(),
            ],
        ]));
    }

    public static function createCustomerProfileDeleteInput(): InputObjectType
    {
        return TypeRegistry::get('createCustomerProfileDeleteInput', fn () => new InputObjectType([
            'name' => 'createCustomerProfileDeleteInput',
            'fields' => [
                'currentPassword' => Type::string(),
                'password' => Type::string(),
            ],
        ]));
    }

    public static function createProductReviewInput(): InputObjectType
    {
        return TypeRegistry::get('createProductReviewInput', fn () => new InputObjectType([
            'name' => 'createProductReviewInput',
            'fields' => [
                'productId' => Type::nonNull(Type::id()),
                'title' => Type::string(),
                'comment' => Type::string(),
                'rating' => Type::int(),
                'name' => Type::string(),
            ],
        ]));
    }

    public static function createContactUsInput(): InputObjectType
    {
        return TypeRegistry::get('createContactUsInput', fn () => new InputObjectType([
            'name' => 'createContactUsInput',
            'fields' => [
                'name' => Type::string(),
                'email' => Type::string(),
                'contact' => Type::string(),
                'message' => Type::string(),
            ],
        ]));
    }
}
