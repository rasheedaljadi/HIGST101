<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Repositories\ProductRepository;

class AddTestProduct extends Command
{
    protected $signature = 'dev:add-test-product';

    protected $description = 'يضيف منتجاً تجريبياً للنظام';

    public function __construct(protected ProductRepository $productRepository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('=== إضافة منتج تجريبي ===');

        // 1. جمع البيانات الأساسية
        $family = AttributeFamily::first();

        if (! $family) {
            $this->error('لم يتم العثور على Attribute Family!');

            return self::FAILURE;
        }

        $this->line("✓ Attribute Family: {$family->name} (ID: {$family->id})");

        $channel = Channel::with('locales')->first();

        if (! $channel) {
            $this->error('لم يتم العثور على Channel!');

            return self::FAILURE;
        }

        $this->line("✓ Channel: {$channel->code}");

        $locales = $channel->locales->pluck('code')->toArray();

        if (empty($locales)) {
            $locales = Locale::where('status', 1)->pluck('code')->toArray();
        }

        $this->line('✓ Locales: '.implode(', ', $locales));

        // 2. إنشاء المنتج
        $sku = 'TEST-PROD-'.time();

        $this->newLine();
        $this->info('جاري إنشاء المنتج...');

        DB::beginTransaction();

        try {
            // إنشاء المنتج الأساسي
            $product = $this->productRepository->create([
                'type' => 'simple',
                'attribute_family_id' => $family->id,
                'sku' => $sku,
            ]);

            $this->line("✓ تم إنشاء المنتج: ID = {$product->id}, SKU = {$product->sku}");

            // بناء بيانات التحديث
            $urlKey = 'test-product-'.time();

            $updateData = [
                'channel' => $channel->code,
                'locale' => $locales[0] ?? 'ar',
                'sku' => $sku,
                'name' => 'منتج تجريبي - Test Product',
                'url_key' => $urlKey,
                'short_description' => 'هذا منتج تجريبي تم إضافته لاختبار النظام.',
                'description' => 'هذا منتج تجريبي كامل يُستخدم لاختبار النظام. يحتوي على جميع البيانات الأساسية المطلوبة لعرض المنتج في المتجر.',
                'price' => 99.99,
                'weight' => 0.5,
                'status' => 1,
                'visible_individually' => 1,
                'new' => 1,
                'featured' => 1,
                'inventories' => [1 => 100],
                'categories' => [1],
                'channels' => [$channel->id],
            ];

            $product = $this->productRepository->update($updateData, $product->id);

            $this->line('✓ تم تحديث بيانات المنتج');

            DB::commit();

            $this->newLine();
            $this->info('=== تم إضافة المنتج بنجاح! ===');
            $this->table(
                ['الحقل', 'القيمة'],
                [
                    ['ID', $product->id],
                    ['SKU', $product->sku],
                    ['النوع', $product->type],
                    ['السعر', '99.99'],
                    ['المخزون', '100'],
                    ['Admin URL', "http://127.0.0.1:8000/admin/catalog/products/{$product->id}/edit"],
                    ['Shop URL', "http://127.0.0.1:8000/products/{$urlKey}"],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('ERROR: '.$e->getMessage());
            $this->line('File: '.$e->getFile().':'.$e->getLine());
            $this->newLine();
            $this->line($e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
