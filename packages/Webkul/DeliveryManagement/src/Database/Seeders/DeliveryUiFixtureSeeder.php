<?php

namespace Webkul\DeliveryManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\Inventory\Database\Seeders\HayestCentralInventorySourceSeeder;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class DeliveryUiFixtureSeeder extends Seeder
{
    /**
     * Seed pure UI fixture data (Roles, Admins, Delivery Points, Governorate Rules, and Hayest Central Source).
     *
     * GUARANTEES:
     * - ZERO products created.
     * - ZERO orders or order_items created.
     * - ZERO product_inventories or stock movements created.
     * - ZERO shipments or order_allocations created.
     * - 100% Idempotent (safe to run multiple times without duplicating or corrupting state).
     */
    public function run(): void
    {
        // 1. Governorate Rules & Central Source Seeders
        $this->call(HayestCentralInventorySourceSeeder::class);
        $this->call(DeliveryGovernorateRulesSeeder::class);

        // 2. Roles
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

        // 3. Delivery Points
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

        // 4. Admin Users
        Admin::updateOrCreate(
            ['email' => 'supervisor@hayest.test'],
            [
                'name' => 'مشرف عمليات التوصيل',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'status' => 1,
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'courier_sanaa@hayest.test'],
            [
                'name' => 'أحمد الصنعاني (مندوب صنعاء)',
                'password' => Hash::make('password123'),
                'role_id' => $courierRole->id,
                'status' => 1,
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'courier_aden@hayest.test'],
            [
                'name' => 'صالح العدني (مندوب عدن)',
                'password' => Hash::make('password123'),
                'role_id' => $courierRole->id,
                'status' => 1,
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'point_agent_tahrir@hayest.test'],
            [
                'name' => 'جمال السنيدار (موظف نقطة التحرير)',
                'password' => Hash::make('password123'),
                'role_id' => $pointRole->id,
                'delivery_point_id' => $tahrirPoint->id,
                'status' => 1,
            ]
        );
    }
}
