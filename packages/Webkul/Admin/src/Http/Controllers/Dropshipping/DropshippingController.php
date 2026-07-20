<?php

namespace Webkul\Admin\Http\Controllers\Dropshipping;

use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;

class DropshippingController extends Controller
{
    /**
     * @deprecated Use AliExpressImportController instead.
     */
    public function imports()
    {
        if (config('dropshipping.admin_v2', true)) {
            \Illuminate\Support\Facades\Log::channel('aliexpress')->warning('Deprecated route accessed: admin.dropshipping.imports.index. Redirecting to admin.dropshipping.import.index.');
            return redirect()->route('admin.dropshipping.import.index');
        }

        return view('admin::dropshipping.coming-soon', [
            'pageTitle' => trans('admin::app.components.layouts.sidebar.dropshipping-imports'),
        ]);
    }

    /**
     * @deprecated Use FulfillmentController instead.
     */
    public function fulfillment()
    {
        if (config('dropshipping.admin_v2', true)) {
            \Illuminate\Support\Facades\Log::channel('aliexpress')->warning('Deprecated route accessed: admin.dropshipping.fulfillment.index. Redirecting to actual fulfillment.');
            return redirect()->route('admin.dropshipping.fulfillment.index');
        }

        return view('admin::dropshipping.coming-soon', [
            'pageTitle' => trans('admin::app.components.layouts.sidebar.dropshipping-fulfillment'),
        ]);
    }

    /**
     * @deprecated Use AliExpressKeysController instead.
     */
    public function apiKeys()
    {
        if (config('dropshipping.admin_v2', true)) {
            \Illuminate\Support\Facades\Log::channel('aliexpress')->warning('Deprecated route accessed: admin.dropshipping.api-keys.index. Redirecting to admin.dropshipping.keys.index.');
            return redirect()->route('admin.dropshipping.keys.index');
        }

        return view('admin::dropshipping.coming-soon', [
            'pageTitle' => trans('admin::app.components.layouts.sidebar.dropshipping-api-keys'),
        ]);
    }
}

