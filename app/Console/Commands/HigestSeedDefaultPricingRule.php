<?php

namespace App\Console\Commands;

use App\Models\HigestPricingRule;
use Illuminate\Console\Command;

class HigestSeedDefaultPricingRule extends Command
{
    protected $signature = 'higest:pricing:seed-default-rule {--margin=30 : Default margin percentage}';

    protected $description = 'Seed default global pricing rule if no active global rule exists';

    public function handle(): int
    {
        $margin = (float) $this->option('margin');

        $rule = HigestPricingRule::global()->first();

        if ($rule !== null) {
            $this->info("Global pricing rule already exists (ID #{$rule->id}: {$rule->name}, {$rule->value}%).");

            return Command::SUCCESS;
        }

        $rule = HigestPricingRule::create([
            'name' => 'Default Global Margin',
            'scope' => 'global',
            'scope_id' => null,
            'type' => 'percentage',
            'value' => $margin,
            'priority' => 0,
            'version' => 1,
            'status' => true,
        ]);

        $this->info("Successfully seeded default global pricing rule (ID #{$rule->id}: {$margin}% margin).");

        return Command::SUCCESS;
    }
}
