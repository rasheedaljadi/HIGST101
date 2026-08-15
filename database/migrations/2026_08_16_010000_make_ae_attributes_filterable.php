<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('attributes')
            ->where('code', 'like', 'ae_%')
            ->whereIn('type', ['select', 'multiselect', 'checkbox'])
            ->update(['is_filterable' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
