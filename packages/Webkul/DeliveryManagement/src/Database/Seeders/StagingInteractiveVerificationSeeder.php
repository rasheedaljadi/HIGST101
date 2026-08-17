<?php

namespace Webkul\DeliveryManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Webkul\Core\Models\Channel;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\Inventory\Database\Seeders\HayestCentralInventorySourceSeeder;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class StagingInteractiveVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HayestCentralInventorySourceSeeder::class);
        $this->call(DeliveryGovernorateRulesSeeder::class);

        // 1. Roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['permission_type' => 'all', 'permissions' => ['all']]
        );

        $courierRole = Role::firstOrCreate(
            ['name' => 'Courier'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        $pointRole = Role::firstOrCreate(
            ['name' => 'PointAgent'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        // 2. Delivery Points
        $tahrirPoint = DeliveryPoint::updateOrCreate(
            ['code' => 'PNT-SAN-TAHRIR'],
            [
                'name' => 'نقطة التحرير - صنعاء',
                'name_ar' => 'نقطة التحرير - صنعاء',
                'state_code' => 'SAN',
                'city' => 'Sanaa',
                'address' => 'ميدان التحرير، جوار البريد العام، مبنى الأمانة',
                'contact_name' => 'جمال السنيدار',
                'contact_phone' => '777112233',
                'is_active' => 1,
                'max_capacity' => 150,
            ]
        );

        $mansouraPoint = DeliveryPoint::updateOrCreate(
            ['code' => 'PNT-ADE-MANSOURA'],
            [
                'name' => 'نقطة المنصورة - عدن',
                'name_ar' => 'نقطة المنصورة - عدن',
                'state_code' => 'ADE',
                'city' => 'Aden',
                'address' => 'شارع التسعين، جولة السفينة، عدن',
                'contact_name' => 'فهد اليافعي',
                'contact_phone' => '771223344',
                'is_active' => 1,
                'max_capacity' => 100,
            ]
        );

        // 3. Admins
        $supervisor = Admin::updateOrCreate(
            ['email' => 'supervisor@hayest.test'],
            [
                'name' => 'مشرف عمليات التوصيل',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'status' => 1,
            ]
        );

        $courierSanaa = Admin::updateOrCreate(
            ['email' => 'courier_sanaa@hayest.test'],
            [
                'name' => 'أحمد الصنعاني (مندوب صنعاء)',
                'password' => Hash::make('password123'),
                'role_id' => $courierRole->id,
                'status' => 1,
            ]
        );

        $courierAden = Admin::updateOrCreate(
            ['email' => 'courier_aden@hayest.test'],
            [
                'name' => 'صالح العدني (مندوب عدن)',
                'password' => Hash::make('password123'),
                'role_id' => $courierRole->id,
                'status' => 1,
            ]
        );

        $pointAgent = Admin::updateOrCreate(
            ['email' => 'point_agent_tahrir@hayest.test'],
            [
                'name' => 'جمال السنيدار (موظف نقطة التحرير)',
                'password' => Hash::make('password123'),
                'role_id' => $pointRole->id,
                'delivery_point_id' => $tahrirPoint->id,
                'status' => 1,
            ]
        );

        // 4. Products & Stock
        $hayestSource = InventorySource::where('code', 'hayest_central')->first();
        $attrFamilyId = DB::table('attribute_families')->value('id') ?? 1;

        $productA = Product::firstOrCreate(
            ['sku' => 'STG-PHONE-PRO-01'],
            ['type' => 'simple', 'attribute_family_id' => $attrFamilyId]
        );

        DB::table('product_inventories')->updateOrInsert(
            ['product_id' => $productA->id, 'inventory_source_id' => $hayestSource->id],
            ['qty' => 50]
        );

        $channelId = DB::table('channels')->where('code', 'default')->value('id')
            ?? DB::table('channels')->insertGetId([
                'code' => 'default',
                'theme' => 'default',
                'hostname' => 'localhost',
                'default_locale_id' => 1,
                'base_currency_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // 5. Order 1: Assigned to Courier Sanaa (COD - 25,000 YER)
        $order1 = Order::firstOrCreate(
            ['increment_id' => 'STG-ORD-SAN-COD-01'],
            [
                'status' => 'processing',
                'is_guest' => 1,
                'channel_id' => $channelId,
                'channel_type' => Channel::class,
                'channel_name' => 'Default',
                'customer_email' => 'tareq_sanaa@example.test',
                'customer_first_name' => 'طارق',
                'customer_last_name' => 'المتوكل',
                'shipping_method' => 'homedelivery_standard',
                'shipping_title' => 'التوصيل المنزلي السريع - صنعاء',
                'grand_total' => 25000,
                'base_grand_total' => 25000,
                'order_currency_code' => 'YER',
                'base_currency_code' => 'YER',
                'total_item_count' => 1,
                'total_qty_ordered' => 1,
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order1->id, 'product_id' => $productA->id],
            [
                'product_type' => Product::class,
                'type' => 'simple',
                'sku' => $productA->sku,
                'name' => 'سماعة لاسلكية عازلة للضوضاء Pro',
                'qty_ordered' => 1,
                'qty_shipped' => 1,
                'qty_invoiced' => 0,
                'price' => 25000,
                'base_price' => 25000,
                'total' => 25000,
                'base_total' => 25000,
            ]
        );

        $addr1 = [
            'order_id' => $order1->id,
            'first_name' => 'طارق',
            'last_name' => 'المتوكل',
            'email' => 'tareq_sanaa@example.test',
            'phone' => '771234567',
            'address' => 'شارع حدة، أمام مجمع الكميم، عمارة الأمل، شقة 4',
            'city' => 'صنعاء',
            'state' => 'SAN',
            'country' => 'YE',
        ];
        OrderAddress::firstOrCreate(['order_id' => $order1->id, 'address_type' => 'order_shipping'], $addr1);
        OrderAddress::firstOrCreate(['order_id' => $order1->id, 'address_type' => 'order_billing'], $addr1);
        OrderPayment::firstOrCreate(['order_id' => $order1->id], ['method' => 'cashondelivery', 'method_title' => 'الدفع عند الاستلام']);

        DeliveryAssignment::updateOrCreate(
            ['order_id' => $order1->id],
            [
                'delivery_boy_id' => $courierSanaa->id,
                'delivery_type' => 'home_delivery',
                'status' => DeliveryAssignment::STATUS_ASSIGNED,
                'customer_address_snapshot' => $addr1,
                'attempt_count' => 0,
                'max_attempts' => 3,
                'idempotency_key' => 'SEED-ASSIGN-1',
            ]
        );

        // 6. Order 2: Assigned to Courier Sanaa (Prepaid - 40,000 YER)
        $order2 = Order::firstOrCreate(
            ['increment_id' => 'STG-ORD-SAN-PRE-02'],
            [
                'status' => 'processing',
                'is_guest' => 1,
                'channel_id' => $channelId,
                'channel_type' => Channel::class,
                'channel_name' => 'Default',
                'customer_email' => 'huda_sanaa@example.test',
                'customer_first_name' => 'هدى',
                'customer_last_name' => 'الهمداني',
                'shipping_method' => 'homedelivery_standard',
                'shipping_title' => 'التوصيل المنزلي المعتمد - صنعاء',
                'grand_total' => 40000,
                'base_grand_total' => 40000,
                'order_currency_code' => 'YER',
                'base_currency_code' => 'YER',
                'total_item_count' => 1,
                'total_qty_ordered' => 1,
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order2->id, 'product_id' => $productA->id],
            [
                'product_type' => Product::class,
                'type' => 'simple',
                'sku' => $productA->sku,
                'name' => 'ساعة ذكية مقاومة للماء Ultra 9',
                'qty_ordered' => 1,
                'qty_shipped' => 1,
                'qty_invoiced' => 1,
                'price' => 40000,
                'base_price' => 40000,
                'total' => 40000,
                'base_total' => 40000,
            ]
        );

        $addr2 = [
            'order_id' => $order2->id,
            'first_name' => 'هدى',
            'last_name' => 'الهمداني',
            'email' => 'huda_sanaa@example.test',
            'phone' => '772345678',
            'address' => 'شارع الستين الجنوبي، قرب فندق شيراتون، صنعاء',
            'city' => 'صنعاء',
            'state' => 'SAN',
            'country' => 'YE',
        ];
        OrderAddress::firstOrCreate(['order_id' => $order2->id, 'address_type' => 'order_shipping'], $addr2);
        OrderAddress::firstOrCreate(['order_id' => $order2->id, 'address_type' => 'order_billing'], $addr2);
        OrderPayment::firstOrCreate(['order_id' => $order2->id], ['method' => 'moneytransfer', 'method_title' => 'حوالة بنكية مدفوعة مسبقاً']);

        DeliveryAssignment::updateOrCreate(
            ['order_id' => $order2->id],
            [
                'delivery_boy_id' => $courierSanaa->id,
                'delivery_type' => 'home_delivery',
                'status' => DeliveryAssignment::STATUS_ASSIGNED,
                'customer_address_snapshot' => $addr2,
                'attempt_count' => 0,
                'max_attempts' => 3,
                'idempotency_key' => 'SEED-ASSIGN-2',
            ]
        );

        // 7. Order 3: Assigned to Courier Aden (Isolation test - 15,000 YER)
        $order3 = Order::firstOrCreate(
            ['increment_id' => 'STG-ORD-ADE-COD-03'],
            [
                'status' => 'processing',
                'is_guest' => 1,
                'channel_id' => $channelId,
                'channel_type' => Channel::class,
                'channel_name' => 'Default',
                'customer_email' => 'khalid_aden@example.test',
                'customer_first_name' => 'خالد',
                'customer_last_name' => 'البركاني',
                'shipping_method' => 'homedelivery_standard',
                'shipping_title' => 'توصيل منزلي - عدن',
                'grand_total' => 15000,
                'base_grand_total' => 15000,
                'order_currency_code' => 'YER',
                'base_currency_code' => 'YER',
                'total_item_count' => 1,
                'total_qty_ordered' => 1,
            ]
        );

        $addr3 = [
            'order_id' => $order3->id,
            'first_name' => 'خالد',
            'last_name' => 'البركاني',
            'email' => 'khalid_aden@example.test',
            'phone' => '733445566',
            'address' => 'المعلا، الشارع الرئيسي، بجانب بنك اليمن والكويت',
            'city' => 'عدن',
            'state' => 'ADE',
            'country' => 'YE',
        ];
        OrderAddress::firstOrCreate(['order_id' => $order3->id, 'address_type' => 'order_shipping'], $addr3);
        OrderAddress::firstOrCreate(['order_id' => $order3->id, 'address_type' => 'order_billing'], $addr3);
        OrderPayment::firstOrCreate(['order_id' => $order3->id], ['method' => 'cashondelivery', 'method_title' => 'الدفع عند الاستلام']);

        DeliveryAssignment::updateOrCreate(
            ['order_id' => $order3->id],
            [
                'delivery_boy_id' => $courierAden->id,
                'delivery_type' => 'home_delivery',
                'status' => DeliveryAssignment::STATUS_ASSIGNED,
                'customer_address_snapshot' => $addr3,
                'attempt_count' => 0,
                'max_attempts' => 3,
                'idempotency_key' => 'SEED-ASSIGN-3',
            ]
        );

        // 8. Order 4: Assigned to Delivery Point Al-Tahrir (Prepaid Pickup)
        $order4 = Order::firstOrCreate(
            ['increment_id' => 'STG-ORD-PNT-SAN-04'],
            [
                'status' => 'processing',
                'is_guest' => 1,
                'channel_id' => $channelId,
                'channel_type' => Channel::class,
                'channel_name' => 'Default',
                'customer_email' => 'yasir_pickup@example.test',
                'customer_first_name' => 'ياسر',
                'customer_last_name' => 'الحكيمي',
                'shipping_method' => 'deliverypoint_pickup',
                'shipping_title' => 'استلام من نقطة التوزيع (نقطة التحرير)',
                'grand_total' => 12000,
                'base_grand_total' => 12000,
                'order_currency_code' => 'YER',
                'base_currency_code' => 'YER',
                'total_item_count' => 1,
                'total_qty_ordered' => 1,
            ]
        );

        $addr4 = [
            'order_id' => $order4->id,
            'first_name' => 'ياسر',
            'last_name' => 'الحكيمي',
            'email' => 'yasir_pickup@example.test',
            'phone' => '775566778',
            'address' => 'صنعاء - استلام من نقطة التحرير',
            'city' => 'صنعاء',
            'state' => 'SAN',
            'country' => 'YE',
        ];
        OrderAddress::firstOrCreate(['order_id' => $order4->id, 'address_type' => 'order_shipping'], $addr4);
        OrderAddress::firstOrCreate(['order_id' => $order4->id, 'address_type' => 'order_billing'], $addr4);
        OrderPayment::firstOrCreate(['order_id' => $order4->id], ['method' => 'moneytransfer', 'method_title' => 'حوالة بنكية مدفوعة']);

        DeliveryAssignment::updateOrCreate(
            ['order_id' => $order4->id],
            [
                'delivery_point_id' => $tahrirPoint->id,
                'delivery_type' => 'delivery_point',
                'status' => DeliveryAssignment::STATUS_ASSIGNED,
                'customer_address_snapshot' => $addr4,
                'delivery_point_snapshot' => [
                    'point_id' => $tahrirPoint->id,
                    'code' => $tahrirPoint->code,
                    'name' => $tahrirPoint->name,
                    'governorate_code' => $tahrirPoint->state_code,
                    'city' => $tahrirPoint->city,
                    'address' => $tahrirPoint->address,
                ],
                'attempt_count' => 0,
                'max_attempts' => 3,
                'idempotency_key' => 'SEED-ASSIGN-4',
            ]
        );

        // 9. Order 5: Ready for Assignment (Unassigned)
        $order5 = Order::firstOrCreate(
            ['increment_id' => 'STG-ORD-READY-05'],
            [
                'status' => 'processing',
                'is_guest' => 1,
                'channel_id' => $channelId,
                'channel_type' => Channel::class,
                'channel_name' => 'Default',
                'customer_email' => 'munir_ready@example.test',
                'customer_first_name' => 'منير',
                'customer_last_name' => 'القاضي',
                'shipping_method' => 'homedelivery_standard',
                'shipping_title' => 'التوصيل المنزلي',
                'grand_total' => 22000,
                'base_grand_total' => 22000,
                'order_currency_code' => 'YER',
                'base_currency_code' => 'YER',
                'total_item_count' => 1,
                'total_qty_ordered' => 1,
            ]
        );

        $addr5 = [
            'order_id' => $order5->id,
            'first_name' => 'منير',
            'last_name' => 'القاضي',
            'email' => 'munir_ready@example.test',
            'phone' => '778899001',
            'address' => 'صنعاء، حي الأصبحي، شارع المقالح',
            'city' => 'صنعاء',
            'state' => 'SAN',
            'country' => 'YE',
        ];
        OrderAddress::firstOrCreate(['order_id' => $order5->id, 'address_type' => 'order_shipping'], $addr5);
        OrderAddress::firstOrCreate(['order_id' => $order5->id, 'address_type' => 'order_billing'], $addr5);
        OrderPayment::firstOrCreate(['order_id' => $order5->id], ['method' => 'cashondelivery', 'method_title' => 'الدفع عند الاستلام']);

        DeliveryAssignment::updateOrCreate(
            ['order_id' => $order5->id],
            [
                'delivery_type' => 'home_delivery',
                'status' => DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT,
                'customer_address_snapshot' => $addr5,
                'attempt_count' => 0,
                'max_attempts' => 3,
                'idempotency_key' => 'SEED-ASSIGN-5',
            ]
        );
    }
}
