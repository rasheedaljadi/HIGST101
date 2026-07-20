<?php

namespace App\Console\Commands;

use App\Services\AliExpress\AliExpressAttributeDictionary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Converts existing AliExpress variant attributes (ae_*) from dropdown to
 * visual swatches — color circles for color axes, text chips for the rest —
 * matching AliExpress's product page. Also backfills HEX swatch values on color
 * options.
 *
 * Run once after deploying swatch support to upgrade products imported before
 * it existed. New imports already create swatch attributes directly.
 *
 * Usage:
 *   php artisan aliexpress:fix-swatches
 *   php artisan aliexpress:fix-swatches --prefix=ae_
 */
class AliExpressFixSwatches extends Command
{
    protected $signature = 'aliexpress:fix-swatches
        {--prefix=ae_ : Only process attributes whose code starts with this prefix}';

    protected $description = 'Convert AliExpress variant attributes to visual swatches (color circles / text chips)';

    public function handle(): int
    {
        $prefix = (string) $this->option('prefix');

        $attributes = DB::table('attributes')
            ->where('code', 'like', $prefix.'%')
            ->get(['id', 'code', 'admin_name', 'swatch_type']);

        if ($attributes->isEmpty()) {
            $this->warn("No attributes found with prefix '{$prefix}'.");

            return self::SUCCESS;
        }

        $attrUpdated = 0;
        $optUpdated = 0;

        foreach ($attributes as $attribute) {
            $axisName = $this->englishAxisName($attribute);
            $swatchType = AliExpressAttributeDictionary::swatchTypeForAxis($axisName);

            if ($attribute->swatch_type !== $swatchType) {
                DB::table('attributes')->where('id', $attribute->id)->update([
                    'swatch_type' => $swatchType,
                ]);
                $attrUpdated++;
            }

            // Backfill HEX swatch values for color options.
            if ($swatchType === 'color') {
                $options = DB::table('attribute_options')
                    ->where('attribute_id', $attribute->id)
                    ->get(['id', 'admin_name', 'swatch_value']);

                foreach ($options as $option) {
                    $hex = AliExpressAttributeDictionary::colorHex((string) $option->admin_name);

                    if ($hex !== null && $option->swatch_value !== $hex) {
                        DB::table('attribute_options')->where('id', $option->id)->update([
                            'swatch_value' => $hex,
                        ]);
                        $optUpdated++;
                    }
                }
            }

            $this->line(sprintf('  • %-16s => swatch=%s', $attribute->code, $swatchType));
        }

        $this->newLine();
        $this->info("Done. {$attrUpdated} attribute(s) converted, {$optUpdated} color swatch value(s) set.");
        $this->line('Clear cache so the storefront reflects the change: php artisan cache:clear');

        return self::SUCCESS;
    }

    /**
     * Resolve the English axis name (prefer en translation, fall back to admin_name).
     */
    protected function englishAxisName(object $attribute): string
    {
        $en = DB::table('attribute_translations')
            ->where('attribute_id', $attribute->id)
            ->whereRaw('LOWER(locale) = ?', ['en'])
            ->value('name');

        if (is_string($en) && trim($en) !== '') {
            return $en;
        }

        return (string) $attribute->admin_name;
    }
}
