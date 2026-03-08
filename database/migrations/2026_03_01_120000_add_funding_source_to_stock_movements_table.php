<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('stock_movements', 'funding_source')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->string('funding_source', 30)->nullable()->after('movement_type');
            });
        }

        DB::table('stock_movements')
            ->where('movement_type', 'purchase')
            ->whereNull('funding_source')
            ->update(['funding_source' => 'kas_toko']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stock_movements', 'funding_source')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropColumn('funding_source');
            });
        }
    }
};
