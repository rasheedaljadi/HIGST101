@php
    $deliveryAssignment = class_exists(\Webkul\DeliveryManagement\Models\DeliveryAssignment::class)
        ? \Webkul\DeliveryManagement\Models\DeliveryAssignment::with(['deliveryBoy', 'deliveryPoint'])->where('order_id', $order->id)->first()
        : null;

    $activeCouriers = class_exists(\Webkul\User\Models\Admin::class)
        ? \Webkul\User\Models\Admin::where('status', 1)->get()
        : collect();

    $activePoints = class_exists(\Webkul\DeliveryManagement\Models\DeliveryPoint::class)
        ? \Webkul\DeliveryManagement\Models\DeliveryPoint::where('is_active', true)->get()
        : collect();

    $shippingMethod = (string) $order->shipping_method;
    $isDeliveryPoint = str_contains($shippingMethod, 'delivery_point') || ($deliveryAssignment?->delivery_type === 'delivery_point');
    $isHomeDelivery = ! $isDeliveryPoint;

    $currentDeliveryType = $isDeliveryPoint ? 'delivery_point' : 'home_delivery';

    $defaultCarrierTitle = $isHomeDelivery 
        ? 'توصيل محلي (مندوب هايست)' 
        : ($isDeliveryPoint ? 'نقطة تسليم معتمدة (هايست)' : ($order->shipping_title ?: 'شحن هايست'));

    $defaultTrackNumber = 'HAYEST-' . str_pad((string) ($order->increment_id ?? $order->id), 8, '0', STR_PAD_LEFT);
@endphp

<!-- Shipment Vue Components -->
<v-create-shipment>
    <div
        class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
    >
        <span class="icon-ship text-2xl"></span>

        @lang('admin::app.sales.orders.view.ship')
    </div>
</v-create-shipment>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-create-shipment-template"
    >
        <div>
            <div
                class="transparent-button px-1 py-1.5 hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                @click="$refs.shipment.open()"
            >
                <span
                    class="icon-ship text-2xl"
                    role="button"
                    tabindex="0"
                >
                </span>

                @lang('admin::app.sales.orders.view.ship')
            </div>

            <!-- Shipment Create Drawer -->
            <x-admin::form
                method="POST"
                :action="route('admin.sales.shipments.store', $order->id)"
            >
                <x-admin::drawer ref="shipment">
                    <!-- Drawer Header -->
                    <x-slot:header>
                        <div class="grid gap-3 sm:h-8">
                            <div class="flex items-center justify-between">
                                <p class="text-xl font-medium dark:text-white">
                                    @lang('admin::app.sales.shipments.create.title')
                                </p>

                                @if (bouncer()->hasPermission('sales.shipments.create'))
                                    <button
                                        type="submit"
                                        class="primary-button ltr:mr-11 rtl:ml-11"
                                    >
                                        @lang('admin::app.sales.shipments.create.create-btn')
                                    </button>
                                @endif
                            </div>
                        </div>
                    </x-slot>

                    <!-- Drawer Content -->
                    <x-slot:content class="!p-0">
                        <div class="grid p-4 pt-2">
                            <!-- Delivery Management Integration Box -->
                            <div class="mb-4 rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50/70 to-indigo-50/70 p-4 dark:border-blue-900/50 dark:from-blue-950/20 dark:to-indigo-950/20 shadow-xs">
                                <div class="flex items-center justify-between gap-2 pb-3 border-b border-blue-200/60 dark:border-blue-900/40">
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl">🚚</span>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-800 dark:text-white">إدارة مسار التوصيل (Delivery Management)</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">ربط الشحنة بمهمة التسليم وتعيين موظف التوصيل المسؤول</p>
                                        </div>
                                    </div>

                                    <div>
                                        @if ($isHomeDelivery)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                                                🏠 توصيل للمنزل
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-300 dark:border-purple-700">
                                                📍 استلام من نقطة
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <input type="hidden" name="shipment[delivery_type]" value="{{ $currentDeliveryType }}">

                                <div class="mt-3 grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                                    @if ($isHomeDelivery)
                                        <!-- Delivery Courier / Agent Select -->
                                        <x-admin::form.control-group class="!mb-0">
                                            <x-admin::form.control-group.label class="font-semibold text-gray-700 dark:text-gray-200">
                                                موظف / مندوب التوصيل
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="select"
                                                id="shipment[delivery_boy_id]"
                                                name="shipment[delivery_boy_id]"
                                                :value="old('shipment.delivery_boy_id', $deliveryAssignment?->delivery_boy_id)"
                                            >
                                                <option value="">-- اختر موظف التوصيل (أو اتركه للإسناد لاحقاً) --</option>
                                                @foreach ($activeCouriers as $courier)
                                                    <option 
                                                        value="{{ $courier->id }}"
                                                        {{ (old('shipment.delivery_boy_id', $deliveryAssignment?->delivery_boy_id) == $courier->id) ? 'selected' : '' }}
                                                    >
                                                        🚴 {{ $courier->name }} ({{ $courier->email }})
                                                    </option>
                                                @endforeach
                                            </x-admin::form.control-group.control>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">سيتم إسناد الطلب للمندوب فور إنشاء الشحنة وتحديث حالته في وحدة التسليم.</p>
                                        </x-admin::form.control-group>
                                    @else
                                        <!-- Delivery Point Select -->
                                        <x-admin::form.control-group class="!mb-0">
                                            <x-admin::form.control-group.label class="font-semibold text-gray-700 dark:text-gray-200">
                                                نقطة التسليم المعتمدة
                                            </x-admin::form.control-group.label>

                                            <x-admin::form.control-group.control
                                                type="select"
                                                id="shipment[delivery_point_id]"
                                                name="shipment[delivery_point_id]"
                                                :value="old('shipment.delivery_point_id', $deliveryAssignment?->delivery_point_id)"
                                            >
                                                <option value="">-- اختر نقطة التسليم --</option>
                                                @foreach ($activePoints as $point)
                                                    <option 
                                                        value="{{ $point->id }}"
                                                        {{ (old('shipment.delivery_point_id', $deliveryAssignment?->delivery_point_id) == $point->id) ? 'selected' : '' }}
                                                    >
                                                        🏢 {{ $point->name }} - {{ $point->governorate }} ({{ $point->city }})
                                                    </option>
                                                @endforeach
                                            </x-admin::form.control-group.control>
                                        </x-admin::form.control-group>
                                    @endif

                                    <!-- Delivery Notes -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.label class="font-semibold text-gray-700 dark:text-gray-200">
                                            ملاحظات الشحن والتسليم
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            id="shipment[delivery_notes]"
                                            name="shipment[delivery_notes]"
                                            placeholder="أي تعليمات خاصة بالتسليم أو الاتصال بالعميل..."
                                        />
                                    </x-admin::form.control-group>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-5">
                                <!-- Carrier Name -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.sales.shipments.create.carrier-name')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="shipment[carrier_title]"
                                        name="shipment[carrier_title]"
                                        :value="old('shipment.carrier_title', $defaultCarrierTitle)"
                                        :label="trans('admin::app.sales.shipments.create.carrier-name')"
                                        :placeholder="trans('admin::app.sales.shipments.create.carrier-name')"
                                    />

                                    <x-admin::form.control-group.error control-name="carrier_name" />
                                </x-admin::form.control-group>

                                <!-- Tracking Number -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.sales.shipments.create.tracking-number')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="shipment[track_number]"
                                        name="shipment[track_number]"
                                        :value="old('shipment.track_number', $defaultTrackNumber)"
                                        :label="trans('admin::app.sales.shipments.create.tracking-number')"
                                        :placeholder="trans('admin::app.sales.shipments.create.tracking-number')"
                                    />

                                    <x-admin::form.control-group.error control-name="shipment[track_number]" />
                                </x-admin::form.control-group>
                            </div>

                            <!-- Resource -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.sales.shipments.create.source')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="shipment[source]"
                                    name="shipment[source]"
                                    rules="required"
                                    v-model="source"
                                    :label="trans('admin::app.sales.shipments.create.source')"
                                    :placeholder="trans('admin::app.sales.shipments.create.source')"
                                    @change="onSourceChange"
                                >
                                    @foreach ($order->channel->inventory_sources as $inventorySource)
                                        <option 
                                            value="{{ $inventorySource->id }}"
                                            v-pre
                                        >
                                            {{ $inventorySource->name }}
                                        </option>
                                    @endforeach
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="shipment[source]" />
                            </x-admin::form.control-group>

                            <div class="grid">
                                <!-- Item Listing -->
                                @foreach ($order->items as $item)
                                    @if (
                                        $item->qty_to_ship > 0
                                        && $item->product
                                    )
										@php
											$canShipQty = app('\Webkul\RMA\Helpers\Helper')->getRemainingQtyAfterRMA($item->id);
										@endphp

                                        <div class="flex justify-between gap-2.5 py-4">
                                            <div class="flex gap-2.5">
                                                @if ($item->product?->base_image_url)
                                                    <img
                                                        class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded"
                                                        src="{{ $item->product?->base_image_url }}"
                                                    >
                                                @else
                                                    <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert">
                                                        <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}">

                                                        <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400">
                                                            @lang('admin::app.sales.invoices.view.product-image')
                                                        </p>
                                                    </div>
                                                @endif

                                                <div class="grid place-content-start gap-1.5">
                                                    <!-- Item Name -->
                                                    <p 
                                                        class="text-base font-semibold text-gray-800 dark:text-white"
                                                        v-pre
                                                    >
                                                        {{ $item->name }}
                                                    </p>

                                                    <div class="flex flex-col place-items-start gap-1.5">
                                                        <p class="text-gray-600 dark:text-gray-300">
                                                            @lang('admin::app.sales.shipments.create.amount-per-unit', [
                                                                'amount' => core()->formatBasePrice($item->base_price),
                                                                'qty'    => $item->qty_ordered,
                                                            ])
                                                        </p>

                                                        <!--Additional Attributes -->
                                                        @if (isset($item->additional['attributes']))
                                                            @foreach ($item->additional['attributes'] as $attribute)
                                                                <p 
                                                                    class="text-gray-600 dark:text-gray-300"
                                                                    v-pre
                                                                >
                                                                    @if (
                                                                        ! isset($attribute['attribute_type'])
                                                                        || $attribute['attribute_type'] !== 'file'
                                                                    )
                                                                        {{ $attribute['attribute_name'] }} : {{ $attribute['option_label'] }}
                                                                    @else
                                                                        {{ $attribute['attribute_name'] }} :

                                                                        <a
                                                                            href="{{ Storage::url($attribute['option_label']) }}"
                                                                            class="text-blue-600 hover:underline"
                                                                            download="{{ File::basename($attribute['option_label']) }}"
                                                                        >
                                                                            {{ File::basename($attribute['option_label']) }}
                                                                        </a>
                                                                    @endif
                                                                </p>
                                                            @endforeach
                                                        @endif

                                                        <!-- Item SKU -->
                                                        <p class="text-gray-600 dark:text-gray-300">
                                                            @lang('admin::app.sales.shipments.create.sku', ['sku' => $item->sku])
                                                        </p>

                                                        <!--Item Status -->
                                                        <p class="text-gray-600 dark:text-gray-300">
                                                            {{ $item->qty_ordered ? trans('admin::app.sales.shipments.create.item-ordered', ['qty_ordered' => $item->qty_ordered]) : '' }}

                                                            {{ $item->qty_invoiced ? trans('admin::app.sales.shipments.create.item-invoice', ['qty_invoiced' => $item->qty_invoiced]) : '' }}

                                                            {{ $item->qty_shipped ? trans('admin::app.sales.shipments.create.item-shipped', ['qty_shipped' => $item->qty_shipped]) : '' }}

                                                            {{ $item->qty_refunded ? trans('admin::app.sales.shipments.create.item-refunded', ['qty_refunded' => $item->qty_refunded]) : '' }}

                                                            {{ $item->qty_canceled ? trans('admin::app.sales.shipments.create.item-canceled', ['qty_canceled' => $item->qty_canceled]) : '' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Information -->
                                        @foreach ($order->channel->inventory_sources as $inventorySource)
                                            <div class="grid grid-cols-2 gap-2.5 border-b border-slate-300 py-2.5 dark:border-gray-800">
                                                <div class="grid gap-1">
                                                    <!--Inventory Source -->
                                                    <p
                                                        class="text-base font-semibold text-gray-800 dark:text-white"
                                                        v-pre
                                                    >
                                                        {{ $inventorySource->name }}
                                                    </p>

                                                    <!-- Available Quantity -->
                                                    <p class="text-gray-600 dark:text-gray-300">
                                                        @lang('admin::app.sales.shipments.create.qty-available') :

                                                        @php
                                                            $product = $item->getTypeInstance()->getOrderedItem($item)->product;

                                                            $sourceQty = $product?->type == 'bundle' ? $item->qty_ordered : $product?->inventory_source_qty($inventorySource->id);
                                                        @endphp

                                                        {{ $sourceQty }}
                                                    </p>
                                                </div>

                                                <div class="grid ltr:text-right rtl:text-left">
                                                    @php
                                                        $inputName = "shipment[items][$item->id][$inventorySource->id]";
                                                    @endphp

                                                    <!-- Quantity To Ship -->
                                                    <x-admin::form.control-group class="!mb-0">
                                                        <x-admin::form.control-group.label class="required !block">
                                                            @lang('admin::app.sales.shipments.create.qty-to-ship')
                                                        </x-admin::form.control-group.label>

                                                        <x-admin::form.control-group.control
                                                            type="text"
                                                            class="!w-[100px]"
                                                            :id="$inputName"
                                                            :name="$inputName"
                                                            :rules="'required|numeric|min_value:0|max_value:' . $canShipQty['qty']"
                                                            :value="$canShipQty['qty']"
                                                            :label="trans('admin::app.sales.shipments.create.qty-to-ship')"
                                                            data-original-quantity="{{ $canShipQty['qty'] }}"
                                                            ::disabled="'{{ empty($sourceQty) }}' || source != '{{ $inventorySource->id }}'"
                                                            :ref="$inputName"
                                                        />

                                                        <x-admin::form.control-group.error :control-name="$inputName" />
                                                    </x-admin::form.control-group>
                                                </div>

												@if ($canShipQty['message'])
													<p class="mt-1 text-xs italic text-green-600">{{ $canShipQty['message'] }}</p>
												@endif
                                            </div>
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </x-slot>
                </x-admin::drawer>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
    app.component('v-create-shipment', {
        template: '#v-create-shipment-template',

        data() {
            return {
                source: "",
            };
        },

        methods: {
            onSourceChange() {
                this.setOriginalQuantityToAllShipmentInputElements();
            },

            getAllShipmentInputElements() {
                let allRefs = this.$refs;

                let allInputElements = [];

                Object.keys(allRefs).forEach((key) => {
                    if (key.startsWith('shipment')) {
                        allInputElements.push(allRefs[key]);
                    }
                });

                return allInputElements;
            },

            setOriginalQuantityToAllShipmentInputElements() {
                this.getAllShipmentInputElements().forEach((element) => {
                    let data = Object.assign({}, element.dataset);

                    element.value = data.originalQuantity;
                });
            }
        },
    });
    </script>
@endPushOnce
