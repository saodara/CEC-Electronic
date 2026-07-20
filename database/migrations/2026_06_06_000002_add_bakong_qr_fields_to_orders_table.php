<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('bakong_qr_string')->nullable()->after('bakong_checkout_url');
            $table->string('bakong_qr_md5', 32)->nullable()->after('bakong_qr_string');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['bakong_qr_string', 'bakong_qr_md5']);
        });
    }
};
