<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->change();
            $table->decimal('compare_at_price', 10, 2)->nullable()->change();
            $table->decimal('cost_price', 10, 2)->nullable()->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('line_total', 10, 2)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->change();
            $table->decimal('shipping_total', 10, 2)->default(0)->change();
            $table->decimal('discount_total', 10, 2)->default(0)->change();
            $table->decimal('grand_total', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('price')->default(0)->change();
            $table->unsignedInteger('compare_at_price')->nullable()->change();
            $table->unsignedInteger('cost_price')->nullable()->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->change();
            $table->unsignedInteger('line_total')->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('subtotal')->default(0)->change();
            $table->unsignedInteger('shipping_total')->default(0)->change();
            $table->unsignedInteger('discount_total')->default(0)->change();
            $table->unsignedInteger('grand_total')->default(0)->change();
        });
    }
};
