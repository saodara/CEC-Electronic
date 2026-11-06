<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->change();
            $table->decimal('grand_total', 10, 2)->default(0)->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->change();
            $table->decimal('line_total', 10, 2)->change();
        });

        // What each item cost when sold, so later cost changes don't rewrite past profit.
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('unit_price');
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->unique()->constrained()->restrictOnDelete();
            $table->string('supplier_reference')->nullable();
            $table->date('bill_date');
            $table->date('due_date');
            $table->decimal('total', 10, 2);
            $table->string('status')->default('unpaid');
            $table->date('paid_on')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->date('issued_on');
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->integer('quantity_change');
            $table->integer('stock_after');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->decimal('amount', 10, 2);
            $table->date('spent_on');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('bills');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_cost')->change();
            $table->unsignedInteger('line_total')->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedInteger('subtotal')->default(0)->change();
            $table->unsignedInteger('grand_total')->default(0)->change();
        });
    }
};
