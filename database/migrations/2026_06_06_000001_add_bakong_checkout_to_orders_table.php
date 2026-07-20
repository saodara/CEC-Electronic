<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('bakong_session_id')->nullable()->after('payment_method');
            $table->string('bakong_checkout_url')->nullable()->after('bakong_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['bakong_session_id', 'bakong_checkout_url']);
        });
    }
};
