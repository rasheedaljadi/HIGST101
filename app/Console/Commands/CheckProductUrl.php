<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckProductUrl extends Command
{
    protected $signature = 'dev:check-product-url {id=3160}';

    protected $description = 'يتحقق من url_key للمنتج وسبب الـ 404';

    public function handle(): int
    {
        $productId = $this->argument('id');

        $this->info("=== فحص URL للمنتج ID: {$productId} ===");

        // 1. معلومات الـ attribute
        $urlKeyAttr = DB::table('attributes')->where('code', 'url_key')->first();

        if (! $urlKeyAttr) {
            $this->error('attribute url_key غير موجود!');

            return self::FAILURE;
        }

        $this->line("url_key attribute: ID={$urlKeyAttr->id}, type={$urlKeyAttr->type}, column_name={$urlKeyAttr->column_name}");
        $this->line("  value_per_channel={$urlKeyAttr->value_per_channel}, value_per_locale={$urlKeyAttr->value_per_locale}");

        // 2. قيمة الـ url_key في product_attribute_values
        $this->newLine();
        $this->info('=== قيم url_key في product_attribute_values ===');
        $values = DB::table('product_attribute_values')
            ->where('product_id', $productId)
            ->where('attribute_id', $urlKeyAttr->id)
            ->get();

        if ($values->isEmpty()) {
            $this->warn("لا توجد قيمة url_key في product_attribute_values للمنتج {$productId}!");
        } else {
            foreach ($values as $v) {
                $this->line("  text_value={$v->text_value}, channel={$v->channel}, locale={$v->locale}");
            }
        }

        // 3. product_flat
        $this->newLine();
        $this->info('=== product_flat ===');
        $flat = DB::table('product_flat')->where('product_id', $productId)->get();

        if ($flat->isEmpty()) {
            $this->warn('لا توجد بيانات في product_flat!');
        } else {
            foreach ($flat as $f) {
                $this->line("  url_key={$f->url_key}, channel={$f->channel}, locale={$f->locale}, status={$f->status}");
            }
        }

        // 4. كل attribute values
        $this->newLine();
        $this->info('=== كل قيم الـ attributes للمنتج ===');
        $allValues = DB::table('product_attribute_values')
            ->where('product_id', $productId)
            ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
            ->select('attributes.code', 'attributes.column_name', 'product_attribute_values.text_value',
                'product_attribute_values.boolean_value', 'product_attribute_values.float_value',
                'product_attribute_values.integer_value', 'product_attribute_values.channel',
                'product_attribute_values.locale')
            ->get();

        if ($allValues->isEmpty()) {
            $this->warn('لا توجد أي قيم attributes للمنتج!');
        } else {
            $rows = [];

            foreach ($allValues as $av) {
                $val = $av->text_value ?? $av->float_value ?? $av->integer_value ?? ($av->boolean_value !== null ? (string) $av->boolean_value : 'NULL');
                $rows[] = [$av->code, $val, $av->channel ?? '-', $av->locale ?? '-'];
            }

            $this->table(['attribute', 'value', 'channel', 'locale'], $rows);
        }

        return self::SUCCESS;
    }
}
