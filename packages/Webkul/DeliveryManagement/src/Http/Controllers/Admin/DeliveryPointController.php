<?php

namespace Webkul\DeliveryManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\DeliveryManagement\DataGrids\DeliveryPointDataGrid;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAuditLog;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\User\Models\Admin;

class DeliveryPointController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(DeliveryPointDataGrid::class)->process();
        }

        return view('delivery::admin.points.index');
    }

    public function create()
    {
        $governorates = DB::table('country_states')
            ->leftJoin('country_state_translations', function ($join) {
                $join->on('country_states.id', '=', 'country_state_translations.country_state_id')
                    ->where('country_state_translations.locale', '=', 'ar');
            })
            ->where('country_states.country_code', 'YE')
            ->select(
                'country_states.code as state_code',
                DB::raw('COALESCE(country_state_translations.default_name, country_states.default_name, country_states.code) as governorate_name')
            )
            ->get();
        $staff = Admin::where('status', 1)->get();

        return view('delivery::admin.points.create', compact('governorates', 'staff'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|unique:delivery_points,code|max:50',
            'name' => 'required|string|max:255',
            'state_code' => 'required|string|max:10',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'max_capacity' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        $point = DeliveryPoint::create([
            'code' => strtoupper($request->input('code')),
            'name' => $request->input('name'),
            'name_ar' => $request->input('name'),
            'state_code' => $request->input('state_code'),
            'city' => $request->input('city'),
            'address' => $request->input('address'),
            'contact_name' => $request->input('contact_name'),
            'contact_phone' => $request->input('contact_phone'),
            'max_capacity' => (int) $request->input('max_capacity'),
            'is_active' => (bool) $request->input('is_active'),
        ]);

        DeliveryAuditLog::log(
            action: 'point_created',
            entityType: 'point',
            entityId: $point->id,
            reason: 'إنشاء نقطة تسليم جديدة',
            newValues: ['code' => $point->code, 'name' => $point->name, 'governorate' => $point->state_code]
        );

        session()->flash('success', 'تم إنشاء نقطة التسليم بنجاح.');

        return redirect()->route('admin.delivery.points.index');
    }

    public function edit(int $id)
    {
        $point = DeliveryPoint::findOrFail($id);
        $governorates = DB::table('country_states')
            ->leftJoin('country_state_translations', function ($join) {
                $join->on('country_states.id', '=', 'country_state_translations.country_state_id')
                    ->where('country_state_translations.locale', '=', 'ar');
            })
            ->where('country_states.country_code', 'YE')
            ->select(
                'country_states.code as state_code',
                DB::raw('COALESCE(country_state_translations.default_name, country_states.default_name, country_states.code) as governorate_name')
            )
            ->get();
        $staff = Admin::where('status', 1)->get();
        $shipments = DeliveryAssignment::with(['order'])->where('delivery_point_id', $id)->orderBy('id', 'desc')->paginate(15);

        return view('delivery::admin.points.edit', compact('point', 'governorates', 'staff', 'shipments'));
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $point = DeliveryPoint::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'state_code' => 'required|string|max:10',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'max_capacity' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
        ]);

        $oldValues = $point->toArray();

        $point->update([
            'name' => $request->input('name'),
            'name_ar' => $request->input('name'),
            'state_code' => $request->input('state_code'),
            'city' => $request->input('city'),
            'address' => $request->input('address'),
            'contact_name' => $request->input('contact_name'),
            'contact_phone' => $request->input('contact_phone'),
            'max_capacity' => (int) $request->input('max_capacity'),
            'is_active' => (bool) $request->input('is_active'),
        ]);

        DeliveryAuditLog::log(
            action: 'point_updated',
            entityType: 'point',
            entityId: $point->id,
            reason: 'تعديل بيانات نقطة التسليم',
            oldValues: $oldValues,
            newValues: $point->toArray()
        );

        session()->flash('success', 'تم تحديث بيانات نقطة التسليم بنجاح.');

        return redirect()->route('admin.delivery.points.index');
    }

    public function toggle(int $id): JsonResponse
    {
        $point = DeliveryPoint::findOrFail($id);
        $point->is_active = ! $point->is_active;
        $point->save();

        DeliveryAuditLog::log(
            action: 'point_toggled',
            entityType: 'point',
            entityId: $point->id,
            reason: 'تغيير حالة تفعيل نقطة التسليم',
            newValues: ['is_active' => $point->is_active]
        );

        return response()->json([
            'success' => true,
            'is_active' => $point->is_active,
            'message' => 'تم تغيير حالة نقطة التسليم بنجاح.',
        ]);
    }
}
