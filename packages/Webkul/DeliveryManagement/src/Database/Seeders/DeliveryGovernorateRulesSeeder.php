<?php

namespace Webkul\DeliveryManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryGovernorateRulesSeeder extends Seeder
{
    /**
     * Seed initial governorate delivery and payment eligibility rules.
     */
    public function run(): void
    {
        $yemenStates = DB::table('country_states')
            ->where('country_code', 'YE')
            ->get();

        if ($yemenStates->isEmpty()) {
            // Fallback default Yemeni governorates
            $stateCodes = ['SAN', 'SN', 'AD', 'TA', 'IB', 'HD', 'HJ', 'MR', 'DH', 'AB', 'LA', 'SH', 'BA', 'JA', 'MRB', 'AM', 'SD', 'RAY', 'SUQ', 'MH', 'AL'];
        } else {
            $stateCodes = $yemenStates->pluck('code')->toArray();
        }

        foreach ($stateCodes as $stateCode) {
            $isSanaaHome = ($stateCode === 'SAN');

            // 1. Home Delivery Rule
            DB::table('delivery_governorate_rules')->updateOrInsert(
                [
                    'state_code' => $stateCode,
                    'delivery_type' => 'home_delivery',
                ],
                [
                    'is_enabled' => $isSanaaHome,
                    'allowed_payment_methods' => json_encode($isSanaaHome ? ['cashondelivery', 'moneytransfer'] : []),
                    'delivery_fee' => $isSanaaHome ? 1500.00 : 0.00,
                    'min_order_amount' => 0.00,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // 2. Delivery Point Rule
            DB::table('delivery_governorate_rules')->updateOrInsert(
                [
                    'state_code' => $stateCode,
                    'delivery_type' => 'delivery_point',
                ],
                [
                    'is_enabled' => true,
                    'allowed_payment_methods' => json_encode(['moneytransfer']),
                    'delivery_fee' => 1000.00,
                    'min_order_amount' => 0.00,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
