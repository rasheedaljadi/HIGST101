<?php

namespace Webkul\Admin\Http\Controllers\Wallet;

use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;

class WalletController extends Controller
{
    public function deposits(): View
    {
        return view('admin::wallet.coming-soon', [
            'pageTitle' => trans('admin::app.components.layouts.sidebar.wallet-deposits'),
        ]);
    }

    public function withdrawals(): View
    {
        return view('admin::wallet.coming-soon', [
            'pageTitle' => trans('admin::app.components.layouts.sidebar.wallet-withdrawals'),
        ]);
    }

    public function settings(): View
    {
        return view('admin::wallet.coming-soon', [
            'pageTitle' => trans('admin::app.components.layouts.sidebar.wallet-settings'),
        ]);
    }
}
