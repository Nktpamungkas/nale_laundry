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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('name', 150);
            $table->string('category', 80)->nullable();
            $table->string('unit', 30);
            $table->decimal('minimum_stock', 18, 3)->default(0);
            $table->decimal('current_stock', 18, 3)->default(0);
            $table->decimal('average_cost', 18, 2)->default(0);
            $table->decimal('last_purchase_cost', 18, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->string('pricing_unit', 20)->default('kg');
            $table->decimal('sale_price', 18, 2);
            $table->decimal('labor_cost', 18, 2)->default(0);
            $table->decimal('overhead_cost', 18, 2)->default(0);
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('service_package_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->noActionOnDelete();
            $table->decimal('quantity_per_unit', 18, 4);
            $table->decimal('waste_percent', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['service_package_id', 'inventory_item_id'], 'uq_package_material');
        });

        Schema::create('laundry_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('customer_id')->constrained()->noActionOnDelete();
            $table->dateTime('received_at');
            $table->dateTime('due_at')->nullable();
            $table->string('status', 30)->default('received')->index();
            $table->text('status_note')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('hpp_total', 18, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->dateTime('pickup_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->timestamps();
        });

        Schema::create('laundry_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundry_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_package_id')->constrained()->noActionOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 18, 3)->default(1);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->decimal('material_cost', 18, 2)->default(0);
            $table->decimal('labor_cost', 18, 2)->default(0);
            $table->decimal('overhead_cost', 18, 2)->default(0);
            $table->decimal('hpp_total', 18, 2)->default(0);
            $table->decimal('profit_amount', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundry_order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->index();
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->dateTime('changed_at');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundry_order_id')->constrained()->cascadeOnDelete();
            $table->dateTime('payment_date');
            $table->decimal('amount', 18, 2);
            $table->string('method', 30);
            $table->string('reference_no', 60)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->noActionOnDelete();
            $table->dateTime('movement_date');
            $table->string('movement_type', 30)->index();
            $table->string('funding_source', 30)->nullable();
            $table->string('reference_type', 60)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity_in', 18, 3)->default(0);
            $table->decimal('quantity_out', 18, 3)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'idx_stock_reference');
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 40)->unique();
            $table->date('opname_date');
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->noActionOnDelete();
            $table->decimal('system_stock', 18, 3);
            $table->decimal('actual_stock', 18, 3);
            $table->decimal('difference_stock', 18, 3);
            $table->decimal('adjustment_cost', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_opname_id', 'inventory_item_id'], 'uq_opname_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('laundry_order_items');
        Schema::dropIfExists('laundry_orders');
        Schema::dropIfExists('service_package_materials');
        Schema::dropIfExists('service_packages');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('customers');
    }
};
