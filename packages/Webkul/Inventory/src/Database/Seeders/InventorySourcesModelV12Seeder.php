<?php

namespace Webkul\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Enums\SourceType;

class InventorySourcesModelV12Seeder extends Seeder
{
    /**
     * Seed Design v1.3 Canonical 6 Official Inventory Sources.
     *
     * 100% Idempotent. Zero aliases or duplicate entries.
     */
    public function run(): void
    {
        // 1. Remove any legacy/staging aliases to enforce canonical naming
        DB::table('inventory_sources')
            ->whereIn('code', ['aliexpress_virtual', 'hayest_sourcing_sa'])
            ->delete();

        $sources = [
            // 1. AliExpress Virtual Catalog Projection Source
            [
                'code' => 'aliexpress_source',
                'name' => 'مصدر كتالوج علي إكسبرس الافتراضي',
                'description' => 'مصدر إسقاط افتراضي لعرض منتجات دروبشوبنج. غير قابل للبيع المباشر كمخزون مادي محلي.',
                'contact_name' => 'سحابة تكامل علي إكسبرس',
                'contact_email' => 'integration@aliexpress.hayest.com',
                'contact_number' => '+86-00-000000',
                'country' => 'CN',
                'state' => 'GLOBAL',
                'city' => 'منصة سحابية',
                'street' => 'بوابة AliExpress API',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 0,
                'source_type' => SourceType::VIRTUAL_PROJECTION->value,
            ],

            // 2. Saudi Sourcing Staging Hub
            [
                'code' => 'hayest_dropship_sa',
                'name' => 'محطة توريد وتجميع الرياض (السعودية)',
                'description' => 'محطة تجميع الشحنات الدولية واستلام الموردين في الرياض.',
                'contact_name' => 'عمليات هايست السعودية',
                'contact_email' => 'sa-sourcing@hayest.com',
                'contact_number' => '+966-11-0000000',
                'country' => 'SA',
                'state' => 'RIY',
                'city' => 'الرياض',
                'street' => 'منطقة السلي اللوجستية',
                'postcode' => '14264',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => SourceType::SOURCING_STAGING->value,
            ],

            // 3. Saudi Quarantine Hub
            [
                'code' => 'hayest_quarantine_sa',
                'name' => 'مستودع الحجر الصحي بالرياض (السعودية)',
                'description' => 'مستودع حجز وفحص الجودة وحالات التلف والتوريد غير المطابق بالرياض.',
                'contact_name' => 'تدقيق الجودة - السعودية',
                'contact_email' => 'sa-qa@hayest.com',
                'contact_number' => '+966-11-0000001',
                'country' => 'SA',
                'state' => 'RIY',
                'city' => 'الرياض',
                'street' => 'منطقة السلي اللوجستية - قسم الحجر',
                'postcode' => '14264',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => SourceType::QUARANTINE->value,
            ],

            // 4. Yemen Dropship Cross-Dock Hub
            [
                'code' => 'hayest_dropship_ye',
                'name' => 'مركز توزيع دروبشوبنج صنعاء (اليمن)',
                'description' => 'مركز فرز وتوزيع الشحنات المستوردة الواصلة إلى اليمن للتسليم المباشر.',
                'contact_name' => 'عمليات التوزيع والفرز',
                'contact_email' => 'dropship-hub@hayest.com',
                'contact_number' => '+967-1-444111',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'صنعاء',
                'street' => 'مركز التوزيع - طريق المطار',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => SourceType::DROPSHIP_DISTRIBUTION->value,
            ],

            // 5. Yemen Internal Stock Warehouse
            [
                'code' => 'hayest_internal_ye',
                'name' => 'مستودع المخزون الجاهز صنعاء (اليمن)',
                'description' => 'المستودع الرئيسي للمخزون الداخلي والبضائع الجاهزة للتسليم الفوري في صنعاء.',
                'contact_name' => 'إدارة المخازن الداخلية',
                'contact_email' => 'internal-stock@hayest.com',
                'contact_number' => '+967-1-444222',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'صنعاء',
                'street' => 'المستودع المركزي - شارع الستين',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => SourceType::INTERNAL_STOCK->value,
            ],

            // 6. Yemen Quarantine Warehouse
            [
                'code' => 'hayest_quarantine_ye',
                'name' => 'مستودع الحجر الصحي صنعاء (اليمن)',
                'description' => 'مستودع حجز المرتجعات والتوالف والفحص الفني قبل إعادة التوجيه في صنعاء.',
                'contact_name' => 'قسم المرتجعات والحجر',
                'contact_email' => 'ye-quarantine@hayest.com',
                'contact_number' => '+967-1-444333',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'صنعاء',
                'street' => 'شارع الستين - قسم الحجر والتسويات',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => SourceType::QUARANTINE->value,
            ],
        ];

        foreach ($sources as $source) {
            DB::table('inventory_sources')->updateOrInsert(
                ['code' => $source['code']],
                array_merge($source, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
