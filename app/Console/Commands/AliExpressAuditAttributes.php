<?php

namespace App\Console\Commands;

use App\Services\AliExpress\AliExpressAttributeDictionary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audits AliExpress variant attributes for dictionary coverage gaps.
 *
 * Because the dropshipping API exposes no category-attribute definitions, new
 * axes (and option values) only surface as products are imported. This command
 * reports:
 *   1. ae_* attribute axes whose NAME has no Arabic entry in the dictionary;
 *   2. ae_* option VALUES that pass through untranslated (still English);
 *   3. (optional) axis names / values seen in stored import payload snapshots
 *      that the dictionary does not yet cover.
 *
 * The output is a prioritised to-do list for extending
 * {@see AliExpressAttributeDictionary} — turning "guess every axis up front"
 * into "translate what actually appears".
 *
 * Usage:
 *   php artisan aliexpress:audit-attributes
 *   php artisan aliexpress:audit-attributes --snapshots   (also scan import payloads)
 *   php artisan aliexpress:audit-attributes --prefix=ae_
 */
class AliExpressAuditAttributes extends Command
{
    protected $signature = 'aliexpress:audit-attributes
        {--prefix=ae_ : Only audit attributes whose code starts with this prefix}
        {--snapshots : Also scan stored import payload snapshots for unseen axes/values}';

    protected $description = 'Report AliExpress attribute axes and option values not yet covered by the offline dictionary';

    public function handle(): int
    {
        $prefix = (string) $this->option('prefix');

        $this->auditAttributes($prefix);

        if ($this->option('snapshots')) {
            $this->newLine();
            $this->auditSnapshots();
        }

        return self::SUCCESS;
    }

    /**
     * Audit the live ae_* attributes + options for untranslated entries.
     */
    protected function auditAttributes(string $prefix): void
    {
        $attributes = DB::table('attributes')
            ->where('code', 'like', $prefix.'%')
            ->get(['id', 'code', 'admin_name']);

        if ($attributes->isEmpty()) {
            $this->warn("No attributes found with prefix '{$prefix}'.");

            return;
        }

        $untranslatedAxes = [];
        $untranslatedValues = [];

        foreach ($attributes as $attribute) {
            $axisName = $this->englishAxisName($attribute);

            if (! AliExpressAttributeDictionary::hasAxisName($axisName)) {
                $untranslatedAxes[] = sprintf('%s  (code: %s)', $axisName, $attribute->code);
            }

            $options = DB::table('attribute_options')
                ->where('attribute_id', $attribute->id)
                ->pluck('admin_name');

            foreach ($options as $value) {
                $value = (string) $value;

                if (! AliExpressAttributeDictionary::isValueTranslated($value, (string) $attribute->code)) {
                    $untranslatedValues[$attribute->code][] = $value;
                }
            }
        }

        $this->info('── Live attribute audit ──');
        $this->line(sprintf('Attributes scanned: %d', $attributes->count()));

        if ($untranslatedAxes === []) {
            $this->line('✅ All axis names are translated.');
        } else {
            $this->newLine();
            $this->warn(sprintf('⚠️  %d axis name(s) WITHOUT Arabic translation:', count($untranslatedAxes)));
            foreach ($untranslatedAxes as $axis) {
                $this->line('   • '.$axis);
            }
        }

        if ($untranslatedValues === []) {
            $this->line('✅ All option values are translated (or intentionally numeric).');
        } else {
            $this->newLine();
            $total = array_sum(array_map('count', $untranslatedValues));
            $this->warn(sprintf('⚠️  %d option value(s) WITHOUT Arabic translation:', $total));
            foreach ($untranslatedValues as $code => $values) {
                $this->line(sprintf('   [%s]', $code));
                $this->line('      '.implode(' | ', array_values(array_unique($values))));
            }
        }
    }

    /**
     * Scan stored import payload snapshots for axis names / values the
     * dictionary does not yet cover (surfaces gaps even before products are
     * fully created, and for axes that were collapsed away).
     */
    protected function auditSnapshots(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('aliexpress_product_imports')) {
            $this->warn('No aliexpress_product_imports table; skipping snapshot audit.');

            return;
        }

        $snapshots = DB::table('aliexpress_product_imports')
            ->whereNotNull('payload_snapshot')
            ->pluck('payload_snapshot');

        $axisNames = [];   // axisName => true
        $axisValues = [];  // axisName => [value => true]

        foreach ($snapshots as $json) {
            $data = is_array($json) ? $json : json_decode((string) $json, true);

            foreach (($data['axes'] ?? []) as $axis) {
                $name = (string) ($axis['name'] ?? '');

                if ($name === '') {
                    continue;
                }

                $axisNames[$name] = true;

                foreach (($axis['values'] ?? []) as $v) {
                    $v = trim((string) $v);
                    if ($v !== '') {
                        $axisValues[$name][$v] = true;
                    }
                }
            }
        }

        $this->info('── Snapshot audit ──');
        $this->line(sprintf('Snapshots scanned: %d · distinct axes: %d', $snapshots->count(), count($axisNames)));

        $missingAxes = [];
        $missingValues = [];

        foreach ($axisNames as $name => $_) {
            if (! AliExpressAttributeDictionary::hasAxisName($name)) {
                $missingAxes[] = $name;
            }

            foreach (array_keys($axisValues[$name] ?? []) as $value) {
                if (! AliExpressAttributeDictionary::isValueTranslated((string) $value, $name)) {
                    $missingValues[$name][] = (string) $value;
                }
            }
        }

        if ($missingAxes === []) {
            $this->line('✅ All snapshot axis names are translated.');
        } else {
            $this->newLine();
            $this->warn(sprintf('⚠️  %d snapshot axis name(s) WITHOUT translation:', count($missingAxes)));
            foreach ($missingAxes as $name) {
                $this->line('   • '.$name);
            }
        }

        if ($missingValues === []) {
            $this->line('✅ All snapshot values are translated (or intentionally numeric).');
        } else {
            $this->newLine();
            $total = array_sum(array_map('count', $missingValues));
            $this->warn(sprintf('⚠️  %d snapshot value(s) WITHOUT translation:', $total));
            foreach ($missingValues as $name => $values) {
                $this->line(sprintf('   [%s]', $name));
                $this->line('      '.implode(' | ', array_values(array_unique($values))));
            }
        }
    }

    /**
     * Resolve the English axis name for an attribute: prefer the en
     * translation, fall back to admin_name.
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
