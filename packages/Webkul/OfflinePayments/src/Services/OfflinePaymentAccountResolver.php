<?php

namespace Webkul\OfflinePayments\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Checkout\Contracts\Cart;
use Webkul\OfflinePayments\Repositories\OfflinePaymentDestinationRepository;

class OfflinePaymentAccountResolver
{
    /**
     * Create a new resolver instance.
     */
    public function __construct(
        protected OfflinePaymentDestinationRepository $destinationRepository
    ) {}

    /**
     * Resolve eligible payment destinations for the given cart.
     *
     * @param  Cart|null  $cart
     */
    public function getAccountsForCart($cart): Collection
    {
        if (! $cart) {
            return collect();
        }

        $currency = DB::table('currencies')
            ->where('code', $cart->cart_currency_code)
            ->first();

        if (! $currency) {
            return collect();
        }

        return $this->destinationRepository
            ->scopeQuery(function ($query) use ($currency) {
                return $query->where('is_active', true)
                    ->where('currency_id', $currency->id)
                    ->whereHas('account', function ($q) {
                        $q->where('is_active', true);
                    })
                    ->with(['account', 'currency'])
                    ->orderBy('sort_order', 'asc');
            })
            ->get()
            ->filter(function ($destination) use ($cart) {
                $account = $destination->account;

                if (! $account || ! $account->is_active) {
                    return false;
                }

                $channelIds = $account->channel_ids ?? [];

                return in_array($cart->channel_id, $channelIds)
                    || in_array((string) $cart->channel_id, $channelIds);
            })
            ->values();
    }
}
