<?php

namespace Webkul\DeliveryManagement\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Use DeliveryUiFixtureSeeder for UI mock data or SeedE2EIntegrationTestFlow for integration test suites.
 *
 * This class now delegates solely to DeliveryUiFixtureSeeder to prevent polluting database tables
 * with unanchored product/order/stock fixtures.
 */
class StagingInteractiveVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DeliveryUiFixtureSeeder::class);
    }
}
