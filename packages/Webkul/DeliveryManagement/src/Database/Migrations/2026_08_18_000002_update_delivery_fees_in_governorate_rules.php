<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('delivery_governorate_rules')
            ->whereIn('delivery_fee', [1000.00, 1500.00])
            ->orWhere('delivery_fee', '>=', 100.00)
            ->update([
                'delivery_fee' => 5.00,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('delivery_governorate_rules')
            ->where('delivery_type', 'home_delivery')
            ->where('state_code', 'SAN')
            ->where('delivery_fee', 5.00)
            ->update([
                'delivery_fee' => 1500.00,
                'updated_at' => now(),
            ]);

        DB::table('delivery_governorate_rules')
            ->where('delivery_type', 'delivery_point')
            ->where('delivery_fee', 5.00)
            ->update([
                'delivery_fee' => 1000.00,
                'updated_at' => now(),
            ]);
    }
};
