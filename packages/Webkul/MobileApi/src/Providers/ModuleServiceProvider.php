<?php

namespace Webkul\MobileApi\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\MobileApi\Models\MobileApiKey;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        MobileApiKey::class,
    ];
}
