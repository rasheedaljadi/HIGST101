<?php

namespace Webkul\DeliveryManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Webkul\DeliveryManagement\DataGrids\DeliveryCourierDataGrid;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAuditLog;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class DeliveryCourierController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(DeliveryCourierDataGrid::class)->process();
        }

        return view('delivery::admin.couriers.index');
    }

    public function create()
    {
        $roles = Role::all();

        return view('delivery::admin.couriers.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:6',
        ]);

        $courierRole = Role::firstOrCreate(
            ['name' => 'Courier'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        $courier = Admin::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'role_id' => $courierRole->id,
            'status' => 1,
        ]);

        DeliveryAuditLog::log(
            action: 'courier_created',
            entityType: 'courier',
            entityId: $courier->id,
            reason: 'إنشاء حساب مندوب توصيل جديد',
            newValues: ['name' => $courier->name, 'email' => $courier->email]
        );

        session()->flash('success', 'تم إنشاء حساب المندوب بنجاح.');

        return redirect()->route('admin.delivery.couriers.index');
    }

    public function edit(int $id)
    {
        $courier = Admin::findOrFail($id);
        $tasks = DeliveryAssignment::with(['order'])->where('delivery_boy_id', $id)->orderBy('id', 'desc')->paginate(15);

        return view('delivery::admin.couriers.edit', compact('courier', 'tasks'));
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $courier = Admin::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,'.$id,
            'password' => 'nullable|string|min:6',
            'status' => 'required|boolean',
        ]);

        $oldValues = [
            'name' => $courier->name,
            'email' => $courier->email,
            'status' => $courier->status,
        ];

        $courier->name = $request->input('name');
        $courier->email = $request->input('email');
        $courier->status = (int) $request->input('status');

        if ($request->filled('password')) {
            $courier->password = Hash::make($request->input('password'));
        }

        $courier->save();

        DeliveryAuditLog::log(
            action: 'courier_updated',
            entityType: 'courier',
            entityId: $courier->id,
            reason: 'تعديل بيانات المندوب وحالة التفعيل',
            oldValues: $oldValues,
            newValues: ['name' => $courier->name, 'email' => $courier->email, 'status' => $courier->status]
        );

        session()->flash('success', 'تم تحديث بيانات المندوب بنجاح.');

        return redirect()->route('admin.delivery.couriers.index');
    }

    public function toggle(int $id): JsonResponse
    {
        $courier = Admin::findOrFail($id);
        $courier->status = $courier->status ? 0 : 1;
        $courier->save();

        DeliveryAuditLog::log(
            action: 'courier_toggled',
            entityType: 'courier',
            entityId: $courier->id,
            reason: 'تغيير حالة تفعيل المندوب',
            newValues: ['status' => $courier->status]
        );

        return response()->json([
            'success' => true,
            'status' => $courier->status,
            'message' => 'تم تغيير حالة المندوب بنجاح.',
        ]);
    }
}
