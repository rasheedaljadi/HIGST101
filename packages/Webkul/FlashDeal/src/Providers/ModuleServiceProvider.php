<?php

namespace Webkul\FlashDeal\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\FlashDeal\Models\FlashDeal;
use Webkul\FlashDeal\Models\FlashDealProduct;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models registered with Concord.
     *
     * @var array
     */
    protected $models = [
        FlashDeal::class,
        FlashDealProduct::class,
    ];
}
