<?php

namespace Webkul\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HayestCentralInventorySourceSeeder extends Seeder
{
    /**
     * Seed hayest_central inventory source.
     */
    public function run(): void
    {
        DB::table('inventory_sources')->updateOrInsert(
            ['code' => 'hayest_central'],
            [
                'name' => 'Hayest Central Warehouse',
                'description' => 'Hayest Central receiving and distribution warehouse in Sanaa',
                'contact_name' => 'Hayest Operations',
                'contact_email' => 'warehouse@hayest.com',
                'contact_number' => '+967-1-000000',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'Sanaa',
                'street' => 'Sixty Meter Road',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => 'dropship_distribution',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
