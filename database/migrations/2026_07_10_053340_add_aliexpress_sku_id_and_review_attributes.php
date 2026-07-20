<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $attributes = [
            [
                'code' => 'aliexpress_sku_id',
                'admin_name' => 'AliExpress SKU ID',
                'type' => 'text',
            ],
            [
                'code' => 'needs_review',
                'admin_name' => 'Needs Review',
                'type' => 'boolean',
            ],
        ];

        foreach ($attributes as $attr) {
            if (Illuminate\Support\Facades\DB::table('attributes')->where('code', $attr['code'])->exists()) {
                continue;
            }

            $attributeId = Illuminate\Support\Facades\DB::table('attributes')->insertGetId([
                'code' => $attr['code'],
                'admin_name' => $attr['admin_name'],
                'type' => $attr['type'],
                'swatch_type' => null,
                'validation' => null,
                'position' => 99,
                'is_required' => 0,
                'is_unique' => 0,
                'is_filterable' => 0,
                'is_comparable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'enable_wysiwyg' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Illuminate\Support\Facades\DB::table('attribute_translations')->insert([
                'attribute_id' => $attributeId,
                'locale' => 'en',
                'name' => $attr['admin_name'],
            ]);

            $families = Illuminate\Support\Facades\DB::table('attribute_families')->get();
            foreach ($families as $family) {
                $group = Illuminate\Support\Facades\DB::table('attribute_groups')
                    ->where('attribute_family_id', $family->id)
                    ->where(function ($q) {
                        $q->where('name', 'General')
                          ->orWhere('code', 'general');
                    })
                    ->first() ?? Illuminate\Support\Facades\DB::table('attribute_groups')
                    ->where('attribute_family_id', $family->id)
                    ->orderBy('position', 'asc')
                    ->first();

                if ($group) {
                    $groupMappingExists = Illuminate\Support\Facades\DB::table('attribute_group_mappings')
                        ->where('attribute_id', $attributeId)
                        ->where('attribute_group_id', $group->id)
                        ->exists();

                    if (!$groupMappingExists) {
                        Illuminate\Support\Facades\DB::table('attribute_group_mappings')->insert([
                            'attribute_id' => $attributeId,
                            'attribute_group_id' => $group->id,
                            'position' => 99,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $codes = ['aliexpress_sku_id', 'needs_review'];
        $attributeIds = Illuminate\Support\Facades\DB::table('attributes')->whereIn('code', $codes)->pluck('id');
        
        Illuminate\Support\Facades\DB::table('attribute_group_mappings')->whereIn('attribute_id', $attributeIds)->delete();
        Illuminate\Support\Facades\DB::table('attribute_translations')->whereIn('attribute_id', $attributeIds)->delete();
        Illuminate\Support\Facades\DB::table('attributes')->whereIn('id', $attributeIds)->delete();
    }
};
