<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseProduct extends Command
{
    protected $signature = 'dev:diagnose-product {id=3160}';

    protected $description = 'تشخيص مشاكل المنتج';

    public function handle(): int
    {
        $id = $this->argument('id');

        $this->info("=== تشخيص المنتج ID: {$id} ===");

        // 1. المنتج الأساسي
        $product = DB::table('products')->where('id', $id)->first();

        if (! $product) {
            $this->error("المنتج {$id} غير موجود في جدول products!");

            return self::FAILURE;
        }

        $this->info('✓ المنتج موجود في جدول products:');
        $this->table(['الحقل', 'القيمة'], collect((array) $product)->map(fn ($v, $k) => [$k, $v])->values()->toArray());

        // 2. product_flat
        $flatCount = DB::table('product_flat')->where('product_id', $id)->count();
        $this->line("product_flat records: {$flatCount}");

        if ($flatCount > 0) {
            $flat = DB::table('product_flat')->where('product_id', $id)->first();
            $this->line('  url_key: '.($flat->url_key ?? 'NULL'));
            $this->line('  name: '.($flat->name ?? 'NULL'));
            $this->line('  status: '.($flat->status ?? 'NULL'));
        }

        // 3. channels
        $this->newLine();
        $this->info('=== Channels ===');
        $channels = DB::table('channels')->get();
        foreach ($channels as $ch) {
            $this->line("  [{$ch->id}] code={$ch->code}");
        }

        // 4. locales
        $this->info('=== Locales ===');
        $locales = DB::table('locales')->get();
        foreach ($locales as $loc) {
            $this->line("  [{$loc->id}] code={$loc->code} status=".($loc->status ?? '?'));
        }

        // 5. channel_locale (relation)
        $this->info('=== Channel-Locale Relations ===');
        $chLocales = DB::table('channel_locales')->get();
        foreach ($chLocales as $cl) {
            $this->line("  channel_id={$cl->channel_id} locale_id={$cl->locale_id}");
        }

        // 6. url_rewrites
        $this->info('=== URL Rewrites ===');
        if (Schema::hasTable('url_rewrites')) {
            $urls = DB::table('url_rewrites')->where('entity_id', $id)->get();
            foreach ($urls as $u) {
                $this->line("  url_path={$u->url_path} entity_type={$u->entity_type}");
            }

            if ($urls->isEmpty()) {
                $this->warn('  لا توجد URL rewrites للمنتج!');
            }
        } else {
            $this->warn('  جدول url_rewrites غير موجود');
        }

        // 7. attributes required
        $this->info('=== Required Attributes ===');
        $required = DB::table('attributes')
            ->where('is_required', 1)
            ->whereIn('code', ['name', 'url_key', 'price', 'weight', 'description', 'short_description'])
            ->get(['id', 'code', 'type']);

        foreach ($required as $attr) {
            $this->line("  [{$attr->id}] {$attr->code} ({$attr->type})");
        }

        // 8. product_attribute_values
        $this->info('=== Product Attribute Values ===');
        $attrValues = DB::table('product_attribute_values')
            ->where('product_id', $id)
            ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
            ->get(['attributes.code', 'product_attribute_values.text_value', 'product_attribute_values.boolean_value', 'product_attribute_values.float_value', 'product_attribute_values.integer_value', 'product_attribute_values.channel', 'product_attribute_values.locale']);

        if ($attrValues->isEmpty()) {
            $this->warn('  لا توجد attribute values للمنتج!');
        } else {
            foreach ($attrValues as $av) {
                $val = $av->text_value ?? $av->float_value ?? $av->integer_value ?? $av->boolean_value ?? 'NULL';
                $this->line("  {$av->code} = {$val} [ch={$av->channel}, loc={$av->locale}]");
            }
        }

        return self::SUCCESS;
    }
}
