<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Sales\OrderShipmentDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAuditLog;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class ShipmentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemRepository $orderItemRepository,
        protected ShipmentRepository $shipmentRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderShipmentDataGrid::class)->process();
        }

        return view('admin::sales.shipments.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(int $orderId)
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if (! $order->channel || ! $order->canShip()) {
            session()->flash('error', trans('admin::app.sales.shipments.create.creation-error'));

            return redirect()->back();
        }

        return view('admin::sales.shipments.create', compact('order'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(int $orderId)
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if (! $order->canShip()) {
            session()->flash('error', trans('admin::app.sales.shipments.create.order-error'));

            return redirect()->back();
        }

        $this->validate(request(), [
            'shipment.source' => 'required',
            'shipment.items.*.*' => 'required|numeric|min:0',
        ]);

        $data = request()->only(['shipment', 'carrier_name']);

        if (! $this->isInventoryValidate($data)) {
            session()->flash('error', trans('admin::app.sales.shipments.create.quantity-invalid'));

            return redirect()->back();
        }

        $shipment = $this->shipmentRepository->create(array_merge($data, [
            'order_id' => $orderId,
        ]));

        // Sync and bind with Delivery Management unit
        if (class_exists(DeliveryAssignment::class)) {
            try {
                $assignment = DeliveryAssignment::firstOrNew(['order_id' => $orderId]);

                $deliveryBoyId = request()->input('shipment.delivery_boy_id');
                $deliveryPointId = request()->input('shipment.delivery_point_id');
                $deliveryType = request()->input('shipment.delivery_type');
                $deliveryNotes = request()->input('shipment.delivery_notes');

                if ($deliveryType) {
                    $assignment->delivery_type = $deliveryType;
                } elseif (empty($assignment->delivery_type)) {
                    $assignment->delivery_type = str_contains((string) $order->shipping_method, 'delivery_point')
                        ? DeliveryAssignment::TYPE_DELIVERY_POINT
                        : DeliveryAssignment::TYPE_HOME_DELIVERY;
                }

                $assignment->shipment_id = $shipment->id;

                $adminUser = auth()->guard('admin')->user();
                $adminId = $adminUser ? $adminUser->id : null;

                if (! empty($deliveryBoyId)) {
                    $assignment->delivery_boy_id = (int) $deliveryBoyId;
                    $assignment->assigned_by = $adminId;
                    $assignment->assigned_at = now();
                    $assignment->status = DeliveryAssignment::STATUS_ASSIGNED;
                }

                if (! empty($deliveryPointId)) {
                    $assignment->delivery_point_id = (int) $deliveryPointId;
                    if (empty($deliveryBoyId)) {
                        $assignment->assigned_by = $adminId;
                        $assignment->assigned_at = now();
                        $assignment->status = DeliveryAssignment::STATUS_ASSIGNED;
                    }
                }

                if (! empty($deliveryNotes)) {
                    $assignment->notes = ($assignment->notes ? $assignment->notes."\n" : '').'ملاحظات الشحن: '.$deliveryNotes;
                }

                if (empty($assignment->idempotency_key)) {
                    $assignment->idempotency_key = 'ORD-'.$orderId.'-'.time();
                }

                $assignment->save();

                if (class_exists(DeliveryAuditLog::class)) {
                    DeliveryAuditLog::log(
                        action: 'shipment_linked',
                        entityType: 'assignment',
                        entityId: $assignment->id,
                        reason: 'إنشاء شحنة وربط الطلب بوحدة التسليم ومندوب التوصيل',
                        newValues: [
                            'shipment_id' => $shipment->id,
                            'delivery_boy_id' => $assignment->delivery_boy_id,
                            'delivery_point_id' => $assignment->delivery_point_id,
                            'status' => $assignment->status,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning("[DeliverySync] Failed linking shipment #{$shipment->id} with delivery assignment for Order #{$orderId}: ".$e->getMessage());
            }
        }

        session()->flash('success', trans('admin::app.sales.shipments.create.success'));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    /**
     * Checks if requested quantity available or not.
     *
     * @param  array  $data
     * @return bool
     */
    public function isInventoryValidate(&$data)
    {
        if (! isset($data['shipment']['items'])) {
            return;
        }

        $valid = false;

        $inventorySourceId = $data['shipment']['source'];

        foreach ($data['shipment']['items'] as $itemId => $inventorySource) {
            $qty = $inventorySource[$inventorySourceId];

            if ((int) $qty) {
                $orderItem = $this->orderItemRepository->find($itemId);

                if ($orderItem->qty_to_ship < $qty) {
                    return false;
                }

                if ($orderItem->getTypeInstance()->isComposite()) {
                    foreach ($orderItem->children as $child) {
                        if (! $child->qty_ordered) {
                            continue;
                        }

                        $finalQty = ($child->qty_ordered / $orderItem->qty_ordered) * $qty;

                        $availableQty = $child->product->inventories()
                            ->where('inventory_source_id', $inventorySourceId)
                            ->sum('qty');

                        if (
                            $child->qty_to_ship < $finalQty
                            || $availableQty < $finalQty
                        ) {
                            return false;
                        }
                    }
                } else {
                    $availableQty = $orderItem->product->inventories()
                        ->where('inventory_source_id', $inventorySourceId)
                        ->sum('qty');

                    if (
                        $orderItem->qty_to_ship < $qty
                        || $availableQty < $qty
                    ) {
                        return false;
                    }
                }

                $valid = true;
            } else {
                unset($data['shipment']['items'][$itemId]);
            }
        }

        return $valid;
    }

    /**
     * Show the view for the specified resource.
     *
     * @return View
     */
    public function view(int $id)
    {
        $shipment = $this->shipmentRepository->findOrFail($id);

        return view('admin::sales.shipments.view', compact('shipment'));
    }
}
