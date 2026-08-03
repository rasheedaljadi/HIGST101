<?php

namespace App\Console\Commands;

use App\Models\HigestPricingRule;
use App\Models\HigestSourceOffer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HigestVerifyPricing extends Command
{
    protected $signature = 'higest:pricing:verify {--check=all : Verification check (source-offers, positive-margin, special-price-clean, all)}';

    protected $description = 'Perform diagnostic integrity checks on the HIGEST Pricing Engine domain';

    public function handle(): int
    {
        $check = $this->option('check');

        $this->info("Running HIGEST Pricing Engine verification checks [mode: {$check}]...");

        $errors = 0;

        if ($check === 'all' || $check === 'rules') {
            $rulesCount = HigestPricingRule::active()->count();
            if ($rulesCount === 0) {
                $this->error('FAIL: No active pricing rules found!');
                $errors++;
            } else {
                $this->info("PASS: Found {$rulesCount} active pricing rule(s).");
            }
        }

        if ($check === 'all' || $check === 'source-offers') {
            $offersCount = HigestSourceOffer::count();
            $this->info("INFO: Total source offer records: {$offersCount}.");
        }

        if ($check === 'all' || $check === 'positive-margin') {
            $invalidOffers = DB::table('higest_source_offers as hso')
                ->join('product_attribute_values as pav', function ($join) {
                    $join->on('hso.variant_id', '=', 'pav.product_id')
                        ->where('pav.attribute_id', function ($query) {
                            $query->select('id')->from('attributes')->where('code', 'price')->limit(1);
                        });
                })
                ->whereRaw('pav.float_value <= hso.acquisition_cost')
                ->count();

            if ($invalidOffers > 0) {
                $this->warn("WARNING: Found {$invalidOffers} variant(s) where selling price <= acquisition cost!");
            } else {
                $this->info('PASS: All active variants have selling price > acquisition cost.');
            }
        }

        if ($check === 'all' || $check === 'special-price-clean') {
            $specialPriceAttrId = DB::table('attributes')->where('code', 'special_price')->value('id');

            if ($specialPriceAttrId) {
                $specialPriceCount = DB::table('product_attribute_values')
                    ->where('attribute_id', $specialPriceAttrId)
                    ->whereNotNull('float_value')
                    ->count();

                $this->info("INFO: Active promotional special_prices in catalog: {$specialPriceCount}.");
            }
        }

        if ($errors > 0) {
            $this->error("Verification finished with {$errors} error(s).");

            return Command::FAILURE;
        }

        $this->info('Verification completed cleanly.');

        return Command::SUCCESS;
    }
}
