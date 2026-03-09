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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 80)->unique();
            $table->string('status', 30)->default('active')->index();
            $table->string('plan', 60)->nullable();
            $table->string('timezone', 60)->default('Asia/Jakarta');
            $table->string('currency', 10)->default('IDR');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $defaultTenantId = DB::table('tenants')->insertGetId([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'status' => 'active',
            'plan' => 'default',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key', 120);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key'], 'uq_tenant_setting_key');
        });

        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->string('path', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at'], 'idx_activity_action_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->dateTime('last_login_at')->nullable()->after('remember_token');
            $table->dateTime('last_active_at')->nullable()->after('last_login_at');

            if (Schema::hasColumn('users', 'email')) {
                $table->dropUnique('users_email_unique');
                $table->unique(['tenant_id', 'email'], 'uq_users_tenant_email');
            }
        });

        Schema::table('customers', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('customers_code_unique');
            $table->unique(['tenant_id', 'code'], 'uq_customers_tenant_code');
        });

        Schema::table('inventory_items', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('inventory_items_sku_unique');
            $table->unique(['tenant_id', 'sku'], 'uq_inventory_tenant_sku');
        });

        Schema::table('service_packages', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('service_packages_code_unique');
            $table->unique(['tenant_id', 'code'], 'uq_service_packages_tenant_code');
        });

        Schema::table('service_package_materials', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('uq_package_material');
            $table->unique(['tenant_id', 'service_package_id', 'inventory_item_id'], 'uq_package_material_tenant');
        });

        Schema::table('laundry_orders', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('laundry_orders_order_number_unique');
            $table->unique(['tenant_id', 'order_number'], 'uq_orders_tenant_number');
        });

        Schema::table('laundry_order_items', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('order_status_histories', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('stock_opnames', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('stock_opnames_opname_number_unique');
            $table->unique(['tenant_id', 'opname_number'], 'uq_opname_tenant_number');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique('uq_opname_item');
            $table->unique(['tenant_id', 'stock_opname_id', 'inventory_item_id'], 'uq_opname_item_tenant');
        });

        Schema::table('whatsapp_notifications', function (Blueprint $table) use ($defaultTenantId) {
            $table->foreignId('tenant_id')->default($defaultTenantId)->after('id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropUnique('uq_opname_item_tenant');
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique(['stock_opname_id', 'inventory_item_id'], 'uq_opname_item');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropUnique('uq_opname_tenant_number');
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('opname_number', 'stock_opnames_opname_number_unique');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('laundry_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::table('laundry_orders', function (Blueprint $table) {
            $table->dropUnique('uq_orders_tenant_number');
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('order_number', 'laundry_orders_order_number_unique');
        });

        Schema::table('service_package_materials', function (Blueprint $table) {
            $table->dropUnique('uq_package_material_tenant');
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique(['service_package_id', 'inventory_item_id'], 'uq_package_material');
        });

        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropUnique('uq_service_packages_tenant_code');
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('code', 'service_packages_code_unique');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique('uq_inventory_tenant_sku');
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('sku', 'inventory_items_sku_unique');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('uq_customers_tenant_code');
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('code', 'customers_code_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropUnique('uq_users_tenant_email');
            $table->unique('email', 'users_email_unique');
            $table->dropColumn(['last_login_at', 'last_active_at']);
        });

        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('tenants');
    }
};
