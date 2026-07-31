<?php

namespace Webkul\OfflinePayments\ViewComposers;

use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\OfflinePayments\Services\OfflinePaymentAccountResolver;

class CheckoutPaymentComposer
{
    /**
     * Create a new composer instance.
     */
    public function __construct(
        protected OfflinePaymentAccountResolver $accountResolver
    ) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $cart = Cart::getCart();

        $offlineDestinations = $this->accountResolver->getAccountsForCart($cart);

        $view->with('offlineDestinations', $offlineDestinations);
        $view->with('offlineAccounts', $offlineDestinations); // Fallback alias
    }
}
