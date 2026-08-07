<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flash_deals', function (Blueprint $table) {
            if (! Schema::hasColumn('flash_deals', 'subtitle')) {
                $table->string('subtitle')->nullable()->after('title');
            }
            if (! Schema::hasColumn('flash_deals', 'description')) {
                $table->text('description')->nullable()->after('subtitle');
            }
            if (! Schema::hasColumn('flash_deals', 'banner_image')) {
                $table->string('banner_image')->nullable()->after('description');
            }
            if (! Schema::hasColumn('flash_deals', 'background_image')) {
                $table->string('background_image')->nullable()->after('banner_image');
            }
            if (! Schema::hasColumn('flash_deals', 'accent_color')) {
                $table->string('accent_color')->default('#FFC000')->after('background_image');
            }
            if (! Schema::hasColumn('flash_deals', 'secondary_color')) {
                $table->string('secondary_color')->default('#002060')->after('accent_color');
            }
            if (! Schema::hasColumn('flash_deals', 'promotional_message')) {
                $table->string('promotional_message')->nullable()->after('secondary_color');
            }
            if (! Schema::hasColumn('flash_deals', 'offer_description')) {
                $table->text('offer_description')->nullable()->after('promotional_message');
            }
            if (! Schema::hasColumn('flash_deals', 'view_all_url')) {
                $table->string('view_all_url')->nullable()->after('offer_description');
            }
        });

        Schema::table('flash_deal_products', function (Blueprint $table) {
            if (! Schema::hasColumn('flash_deal_products', 'offer_end_time')) {
                $table->dateTime('offer_end_time')->nullable()->after('sold_qty');
            }
            if (! Schema::hasColumn('flash_deal_products', 'badge')) {
                $table->string('badge')->nullable()->after('offer_end_time');
            }
            if (! Schema::hasColumn('flash_deal_products', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('badge');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flash_deals', function (Blueprint $table) {
            $table->dropColumn([
                'subtitle',
                'description',
                'banner_image',
                'background_image',
                'accent_color',
                'secondary_color',
                'promotional_message',
                'offer_description',
                'view_all_url',
            ]);
        });

        Schema::table('flash_deal_products', function (Blueprint $table) {
            $table->dropColumn([
                'offer_end_time',
                'badge',
                'sort_order',
            ]);
        });
    }
};
