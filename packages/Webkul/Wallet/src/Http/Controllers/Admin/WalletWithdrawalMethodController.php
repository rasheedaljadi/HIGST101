<?php

namespace Webkul\Wallet\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Wallet\Repositories\WalletWithdrawalMethodRepository;

class WalletWithdrawalMethodController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected WalletWithdrawalMethodRepository $withdrawalMethodRepository
    ) {}

    /**
     * Display a listing of all withdrawal methods.
     */
    public function index(): JsonResponse
    {
        $methods = $this->withdrawalMethodRepository->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        return response()->json([
            'methods' => $methods,
        ]);
    }

    /**
     * Store a newly created withdrawal method in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $maxSortOrder = $this->withdrawalMethodRepository->max('sort_order') ?? 0;

        $method = $this->withdrawalMethodRepository->create([
            'name' => trim($request->name),
            'status' => true,
            'sort_order' => $maxSortOrder + 1,
        ]);

        return response()->json([
            'message' => 'تم إضافة طريقة السحب بنجاح.',
            'method' => $method,
        ]);
    }

    /**
     * Update the specified withdrawal method in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $method = $this->withdrawalMethodRepository->findOrFail($id);

        $method->update([
            'name' => trim($request->name),
        ]);

        return response()->json([
            'message' => 'تم تعديل طريقة السحب بنجاح.',
            'method' => $method->fresh(),
        ]);
    }

    /**
     * Toggle the status (active / disabled) of the specified withdrawal method.
     */
    public function toggle(int $id): JsonResponse
    {
        $method = $this->withdrawalMethodRepository->findOrFail($id);

        $method->update([
            'status' => ! $method->status,
        ]);

        $statusText = $method->status ? 'تفعيل' : 'إيقاف';

        return response()->json([
            'message' => "تم {$statusText} طريقة السحب بنجاح.",
            'method' => $method->fresh(),
        ]);
    }

    /**
     * Remove the specified withdrawal method from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $method = $this->withdrawalMethodRepository->findOrFail($id);

        $method->delete();

        return response()->json([
            'message' => 'تم حذف طريقة السحب بنجاح.',
        ]);
    }
}
