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
        // Kolom sudah ada di migrasi utama (2026_02_28_000100), jadi cukup exit.
        if (Schema::hasColumn('stock_movements', 'funding_source')) {
            return;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu drop apa pun; kolom bagian dari migrasi utama.
    }
};
