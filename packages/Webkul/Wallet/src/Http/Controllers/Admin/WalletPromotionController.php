<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Wallet\DataGrids\WalletPromotionsDataGrid;
use Webkul\Wallet\Http\Requests\Admin\StoreWalletPromotionRequest;
use Webkul\Wallet\Http\Requests\Admin\UpdateWalletPromotionRequest;
use Webkul\Wallet\Models\WalletPromotion;
use Webkul\Wallet\Models\WalletPromotionAudit;

class WalletPromotionController extends Controller
{
    /**
     * Display a listing of promotions.
     */
    public function index()
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.view')) {
            abort(403);
        }

        if (request()->ajax()) {
            return app(WalletPromotionsDataGrid::class)->toJson();
        }

        return view('wallet::admin.promotions.index');
    }

    /**
     * Show the form for creating a new promotion.
     */
    public function create(): View
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.create')) {
            abort(403);
        }

        return view('wallet::admin.promotions.create');
    }

    /**
     * Store a newly created promotion in storage.
     */
    public function store(StoreWalletPromotionRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $promotion = WalletPromotion::create($data);

        // Audit Trail
        $adminId = auth()->guard('admin')->user()?->id;
        WalletPromotionAudit::create([
            'promotion_id' => $promotion->id,
            'admin_user_id' => $adminId,
            'action' => WalletPromotionAudit::ACTION_CREATED,
            'old_values' => null,
            'new_values' => $promotion->toArray(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'تم إنشاء العرض الترويجي بنجاح.',
                'data' => $promotion,
            ]);
        }

        session()->flash('success', 'تم إنشاء العرض الترويجي بنجاح.');

        return redirect()->route('admin.wallet.promotions.index');
    }

    /**
     * Show the form for editing the specified promotion.
     */
    public function edit(int $id): View
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.edit')) {
            abort(403);
        }

        $promotion = WalletPromotion::findOrFail($id);

        return view('wallet::admin.promotions.edit', compact('promotion'));
    }

    /**
     * Update the specified promotion in storage.
     */
    public function update(UpdateWalletPromotionRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $promotion = WalletPromotion::findOrFail($id);
        $oldValues = $promotion->toArray();

        $data = $request->validated();
        $promotion->update($data);

        // Audit Trail
        $adminId = auth()->guard('admin')->user()?->id;
        WalletPromotionAudit::create([
            'promotion_id' => $promotion->id,
            'admin_user_id' => $adminId,
            'action' => WalletPromotionAudit::ACTION_UPDATED,
            'old_values' => $oldValues,
            'new_values' => $promotion->fresh()->toArray(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'تم تحديث العرض الترويجي بنجاح.',
                'data' => $promotion,
            ]);
        }

        session()->flash('success', 'تم تحديث العرض الترويجي بنجاح.');

        return redirect()->route('admin.wallet.promotions.index');
    }

    /**
     * Archive the specified promotion.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        if (function_exists('bouncer') && ! bouncer()->hasPermission('wallet.promotions.delete')) {
            abort(403);
        }

        $promotion = WalletPromotion::findOrFail($id);
        $oldValues = $promotion->toArray();

        $promotion->status = WalletPromotion::STATUS_ARCHIVED;
        $promotion->save();

        // Audit Trail
        $adminId = auth()->guard('admin')->user()?->id;
        WalletPromotionAudit::create([
            'promotion_id' => $promotion->id,
            'admin_user_id' => $adminId,
            'action' => WalletPromotionAudit::ACTION_ARCHIVED,
            'old_values' => $oldValues,
            'new_values' => $promotion->fresh()->toArray(),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'تمت أرشفة العرض الترويجي بنجاح.',
            ]);
        }

        session()->flash('success', 'تمت أرشفة العرض الترويجي بنجاح.');

        return redirect()->route('admin.wallet.promotions.index');
    }
}
