<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existingAttribute = DB::table('attributes')->where('code', 'origin_type')->first();

        if (! $existingAttribute) {
            $attributeId = DB::table('attributes')->insertGetId([
                'code' => 'origin_type',
                'admin_name' => 'Origin Type',
                'type' => 'select',
                'position' => 50,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'is_filterable' => 1,
                'is_configurable' => 0,
                'is_user_defined' => 0,
                'is_visible_on_front' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Translations
            DB::table('attribute_translations')->insert([
                [
                    'locale' => 'ar',
                    'name' => 'نوع المصدر والأصل',
                    'attribute_id' => $attributeId,
                ],
                [
                    'locale' => 'en',
                    'name' => 'Origin Type',
                    'attribute_id' => $attributeId,
                ],
            ]);

            // Options: unclassified (1), internal (2), imported (3)
            $options = [
                ['admin_name' => 'unclassified', 'sort_order' => 1, 'ar' => 'غير مصنف', 'en' => 'Unclassified'],
                ['admin_name' => 'internal',     'sort_order' => 2, 'ar' => 'محلي (داخلي)', 'en' => 'Internal'],
                ['admin_name' => 'imported',     'sort_order' => 3, 'ar' => 'مستورد (خارجي)', 'en' => 'Imported'],
            ];

            foreach ($options as $opt) {
                $optionId = DB::table('attribute_options')->insertGetId([
                    'attribute_id' => $attributeId,
                    'admin_name' => $opt['admin_name'],
                    'sort_order' => $opt['sort_order'],
                ]);

                DB::table('attribute_option_translations')->insert([
                    [
                        'attribute_option_id' => $optionId,
                        'locale' => 'ar',
                        'label' => $opt['ar'],
                    ],
                    [
                        'attribute_option_id' => $optionId,
                        'locale' => 'en',
                        'label' => $opt['en'],
                    ],
                ]);
            }

            // Map to General group in all attribute families
            $families = DB::table('attribute_families')->get();
            foreach ($families as $family) {
                $generalGroup = DB::table('attribute_groups')
                    ->where('attribute_family_id', $family->id)
                    ->where(function ($q) {
                        $q->where('name', 'General')->orWhere('name', 'عام');
                    })
                    ->first() ?? DB::table('attribute_groups')->where('attribute_family_id', $family->id)->first();

                if ($generalGroup) {
                    DB::table('attribute_group_mappings')->updateOrInsert(
                        [
                            'attribute_group_id' => $generalGroup->id,
                            'attribute_id' => $attributeId,
                        ],
                        [
                            'position' => 50,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $attribute = DB::table('attributes')->where('code', 'origin_type')->first();

        if ($attribute) {
            DB::table('attribute_group_mappings')->where('attribute_id', $attribute->id)->delete();

            $optionIds = DB::table('attribute_options')->where('attribute_id', $attribute->id)->pluck('id');
            if ($optionIds->isNotEmpty()) {
                DB::table('attribute_option_translations')->whereIn('attribute_option_id', $optionIds)->delete();
                DB::table('attribute_options')->where('attribute_id', $attribute->id)->delete();
            }

            DB::table('attribute_translations')->where('attribute_id', $attribute->id)->delete();
            DB::table('attributes')->where('id', $attribute->id)->delete();
        }
    }
};
