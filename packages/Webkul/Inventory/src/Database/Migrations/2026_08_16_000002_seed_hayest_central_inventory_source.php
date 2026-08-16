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
        DB::table('inventory_sources')->updateOrInsert(
            ['code' => 'hayest_central'],
            [
                'name' => 'مستودع هايست المركزي (صنعاء)',
                'description' => 'المستودع الرئيسي الفعلي لعمليات التجهيز والتسليم في صنعاء',
                'contact_name' => 'مدير المستودع المركزي',
                'contact_email' => 'warehouse-sanaa@higesto.com',
                'contact_number' => 777000000,
                'status' => 1,
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'Sana\'a',
                'street' => 'شارع الستين الجنوبي - المركز اللوجستي',
                'postcode' => '00000',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $source = DB::table('inventory_sources')->where('code', 'hayest_central')->first();
        $channel = DB::table('channels')->first();

        if ($source && $channel) {
            DB::table('channel_inventory_sources')->updateOrInsert(
                [
                    'channel_id' => $channel->id,
                    'inventory_source_id' => $source->id,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $source = DB::table('inventory_sources')->where('code', 'hayest_central')->first();

        if ($source) {
            DB::table('channel_inventory_sources')->where('inventory_source_id', $source->id)->delete();
            DB::table('inventory_sources')->where('id', $source->id)->delete();
        }
    }
};
