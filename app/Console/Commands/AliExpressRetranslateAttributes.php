<?php

namespace App\Console\Commands;

use App\Services\AliExpress\AliExpressAttributeDictionary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-applies the offline attribute dictionary to the Arabic labels of existing
 * AliExpress-created attributes (ae_*) and their options, without re-importing
 * any product.
 *
 * This fixes attributes/options that were created before the dictionary existed
 * (their Arabic label still held the raw English value). It mirrors the
 * category command's `--retranslate` flow.
 *
 * Usage:
 *   php artisan aliexpress:retranslate-attributes
 *   php artisan aliexpress:retranslate-attributes --prefix=ae_
 */
class AliExpressRetranslateAttributes extends Command
{
    protected $signature = 'aliexpress:retranslate-attributes
        {--prefix=ae_ : Only process attributes whose code starts with this prefix}';

    protected $description = 'Re-translate existing AliExpress variant attribute names and option labels to Arabic (offline dictionary)';

    public function handle(): int
    {
        $prefix = (string) $this->option('prefix');

        $arabicLocale = DB::table('locales')->whereRaw('LOWER(code) = ?', ['ar'])->value('code');

        if ($arabicLocale === null) {
            $this->error('No Arabic locale found in the locales table.');

            return self::FAILURE;
        }

        $attributes = DB::table('attributes')
            ->where('code', 'like', $prefix.'%')
            ->get(['id', 'code', 'admin_name']);

        if ($attributes->isEmpty()) {
            $this->warn("No attributes found with prefix '{$prefix}'.");

            return self::SUCCESS;
        }

        $namesUpdated = 0;
        $optionsUpdated = 0;

        foreach ($attributes as $attribute) {
            $namesUpdated += $this->retranslateAttributeName($attribute, $arabicLocale);
            $optionsUpdated += $this->retranslateOptions($attribute, $arabicLocale);
        }

        $this->info(sprintf(
            'Done. %d attribute names and %d option labels re-translated across %d attributes.',
            $namesUpdated,
            $optionsUpdated,
            $attributes->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * Re-translate one attribute's Arabic display name. Returns 1 when changed.
     */
    protected function retranslateAttributeName(object $attribute, string $arabicLocale): int
    {
        $source = $this->englishAttributeName($attribute, $arabicLocale);

        $arabic = AliExpressAttributeDictionary::translateAxisName($source);

        if ($arabic === $source) {
            return 0; // No dictionary entry; leave as-is.
        }

        DB::table('attribute_translations')->updateOrInsert(
            ['attribute_id' => $attribute->id, 'locale' => $arabicLocale],
            ['name' => $arabic],
        );

        return 1;
    }

    /**
     * Re-translate all of an attribute's option Arabic labels. Returns count.
     */
    protected function retranslateOptions(object $attribute, string $arabicLocale): int
    {
        $options = DB::table('attribute_options')
            ->where('attribute_id', $attribute->id)
            ->get(['id', 'admin_name']);

        $updated = 0;

        foreach ($options as $option) {
            $source = (string) $option->admin_name;

            if (trim($source) === '') {
                continue;
            }

            $arabic = AliExpressAttributeDictionary::translate($source, (string) $attribute->code);

            if ($arabic === $source) {
                continue; // No dictionary match; keep original.
            }

            DB::table('attribute_option_translations')->updateOrInsert(
                ['attribute_option_id' => $option->id, 'locale' => $arabicLocale],
                ['label' => $arabic],
            );

            $updated++;
        }

        return $updated;
    }

    /**
     * Resolve the English source name for an attribute: prefer the en
     * translation, fall back to admin_name.
     */
    protected function englishAttributeName(object $attribute, string $arabicLocale): string
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
