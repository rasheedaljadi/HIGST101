<?php

namespace Webkul\MobileApi\GraphQL;

use Illuminate\Http\Request;
use Webkul\Customer\Contracts\Customer;

class Context
{
    public function __construct(
        public Request $request,
        public ?Customer $customer = null
    ) {}

    public function getChannel()
    {
        return core()->getCurrentChannel();
    }

    public function getCurrency()
    {
        return core()->getCurrentCurrency();
    }

    public function getLocale()
    {
        return app()->getLocale();
    }
}
