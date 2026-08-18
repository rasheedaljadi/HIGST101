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
                'name' => 'AliExpress Virtual Catalog Source',
                'description' => 'Virtual projection source for dropship catalog discovery. Non-salable physical inventory.',
                'contact_name' => 'AliExpress Integration Cloud',
                'contact_email' => 'integration@aliexpress.hayest.com',
                'contact_number' => '+86-00-000000',
                'country' => 'CN',
                'state' => 'GLOBAL',
                'city' => 'Cloud Platform',
                'street' => 'AliExpress Open Platform API Gateway',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => SourceType::VIRTUAL_PROJECTION->value,
            ],

            // 2. Saudi Sourcing Staging Hub
            [
                'code' => 'hayest_dropship_sa',
                'name' => 'Hayest Saudi Sourcing Hub',
                'description' => 'Cross-border procurement consolidation and inbound staging hub in Riyadh.',
                'contact_name' => 'Hayest Saudi Operations',
                'contact_email' => 'sa-sourcing@hayest.com',
                'contact_number' => '+966-11-0000000',
                'country' => 'SA',
                'state' => 'RIY',
                'city' => 'Riyadh',
                'street' => 'Sulay Logistics Park',
                'postcode' => '14264',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => SourceType::SOURCING_STAGING->value,
            ],

            // 3. Saudi Quarantine Hub
            [
                'code' => 'hayest_quarantine_sa',
                'name' => 'Hayest Saudi Quarantine',
                'description' => 'Quality inspection holding and procurement discrepancy quarantine in Riyadh.',
                'contact_name' => 'Hayest QA Audit SA',
                'contact_email' => 'sa-qa@hayest.com',
                'contact_number' => '+966-11-0000001',
                'country' => 'SA',
                'state' => 'RIY',
                'city' => 'Riyadh',
                'street' => 'Sulay Logistics Park Quarantine Bay',
                'postcode' => '14264',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => SourceType::QUARANTINE->value,
            ],

            // 4. Yemen Dropship Cross-Dock Hub
            [
                'code' => 'hayest_dropship_ye',
                'name' => 'Hayest Yemen Dropship Distribution Hub',
                'description' => 'Cross-dock transit and delivery dispatch hub for imported orders in Sanaa.',
                'contact_name' => 'Hayest CrossDock Operations',
                'contact_email' => 'dropship-hub@hayest.com',
                'contact_number' => '+967-1-444111',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'Sanaa',
                'street' => 'Airport Road Distribution Center',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => SourceType::DROPSHIP_DISTRIBUTION->value,
            ],

            // 5. Yemen Internal Stock Warehouse
            [
                'code' => 'hayest_internal_ye',
                'name' => 'Hayest Yemen Internal Stock Warehouse',
                'description' => 'Physical domestic warehouse for local ready stock and instant delivery in Sanaa.',
                'contact_name' => 'Hayest Domestic Logistics',
                'contact_email' => 'internal-stock@hayest.com',
                'contact_number' => '+967-1-444222',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'Sanaa',
                'street' => 'Sixty Meter Road Central Depot',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => SourceType::INTERNAL_STOCK->value,
            ],

            // 6. Yemen Quarantine Warehouse
            [
                'code' => 'hayest_quarantine_ye',
                'name' => 'Hayest Yemen Quarantine Warehouse',
                'description' => 'Domestic returns, transit damage, and customer disputes quarantine in Sanaa.',
                'contact_name' => 'Hayest Dispute & Returns Bay',
                'contact_email' => 'ye-quarantine@hayest.com',
                'contact_number' => '+967-1-444333',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'Sanaa',
                'street' => 'Sixty Meter Road Holding Section',
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
