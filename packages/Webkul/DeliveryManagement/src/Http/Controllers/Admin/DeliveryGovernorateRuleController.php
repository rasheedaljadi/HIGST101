<?php

namespace Webkul\DeliveryManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\DeliveryManagement\DataGrids\DeliveryGovernorateRuleDataGrid;
use Webkul\DeliveryManagement\Models\DeliveryAuditLog;
use Webkul\DeliveryManagement\Models\DeliveryGovernorateRule;

class DeliveryGovernorateRuleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(DeliveryGovernorateRuleDataGrid::class)->process();
        }

        return view('delivery::admin.rules.index');
    }

    public function edit(int $id)
    {
        $rule = DeliveryGovernorateRule::findOrFail($id);

        $governorate = DB::table('country_states')
            ->leftJoin('country_state_translations', function ($join) {
                $join->on('country_states.id', '=', 'country_state_translations.country_state_id')
                    ->where('country_state_translations.locale', '=', 'ar');
            })
            ->where('country_states.country_code', 'YE')
            ->where('country_states.code', $rule->state_code)
            ->select(DB::raw('COALESCE(country_state_translations.default_name, country_states.default_name) as name'))
            ->first();

        $governorateName = $governorate?->name ?: $rule->state_code;

        $auditLogs = DeliveryAuditLog::where(function ($q) use ($id) {
            $q->where('delivery_governorate_rule_id', $id)
                ->orWhere(function ($sq) use ($id) {
                    $sq->where('entity_type', 'rule')
                        ->where('entity_id', $id);
                });
        })
            ->orderBy('id', 'desc')
            ->get();

        return view('delivery::admin.rules.edit', compact('rule', 'governorateName', 'auditLogs'));
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $rule = DeliveryGovernorateRule::findOrFail($id);

        $request->validate([
            'delivery_fee' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'is_enabled' => 'nullable|boolean',
            'allowed_payment_methods' => 'nullable|array',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date',
        ]);

        $oldValues = $rule->toArray();

        $allowedMethods = $request->input('allowed_payment_methods', []);
        if (empty($allowedMethods)) {
            $allowedMethods = [];
        }

        $rule->update([
            'delivery_fee' => (float) $request->input('delivery_fee', 0),
            'min_order_amount' => (float) $request->input('min_order_amount', 0),
            'is_enabled' => (bool) $request->input('is_enabled', false),
            'allowed_payment_methods' => $allowedMethods,
            'effective_from' => $request->input('effective_from') ?: null,
            'effective_until' => $request->input('effective_until') ?: null,
        ]);

        DeliveryAuditLog::log(
            action: 'rule_updated',
            entityType: 'rule',
            entityId: $rule->id,
            reason: 'تحديث قواعد التوصيل والدفع لمحافظة '.$rule->state_code.' ('.($rule->delivery_type === 'home_delivery' ? 'توصيل منزلي' : 'نقطة استلام').')',
            oldValues: $oldValues,
            newValues: $rule->toArray(),
            ruleId: $rule->id
        );

        session()->flash('success', 'تم حفظ وتحديث قاعدة المحافظة بنجاح وتوثيق التغيير في سجل التدقيق.');

        return redirect()->route('admin.delivery.rules.index');
    }
}
