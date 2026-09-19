<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Brands that previously lived in config/brands.php.
     */
    private const INITIAL_BRANDS = [
        ['Acer', 'acer', 'images/Brand/acer-logo.jpg'],
        ['AOC', 'aoc', 'images/Brand/aoc-logo.png'],
        ['Apple', 'apple', 'images/Brand/apple-logo.png'],
        ['Asus', 'asus', 'images/Brand/asus-logo.png'],
        ['Cabletime', 'cabletime', 'images/Brand/CABLETIME.png'],
        ['Dell', 'dell', 'images/Brand/Dell.png'],
        ['Epson', 'epson', 'images/Brand/EPSON.png'],
        ['HikVISION', 'hikvision', 'images/Brand/HikVISION.png'],
        ['Huawei', 'huawei', 'images/Brand/2148933-3840x2160-desktop-4k-huawei-logo-wallpaper.jpg'],
        ['Lenovo', 'lenovo', 'images/Brand/lenovo-logo.png'],
        ['Logitech', 'logitech', 'images/Brand/logitech-logo.png'],
        ['MSI', 'msi', 'images/Brand/msi.png'],
        ['NEC', 'nec', 'images/Brand/NEC.png'],
        ['PROLINK', 'prolink', 'images/Brand/prolink-logo.jpeg'],
        ['Rongta', 'rongta', 'images/Brand/rongta-logo.png'],
        ['Samsung', 'samsung', 'images/Brand/samsung-logo.png'],
        ['SanDisk', 'sandisk', 'images/Brand/sandisk-logo.png'],
        ['Transcend', 'transcend', 'images/Brand/transcend-logo.jpeg'],
    ];

    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')
                ->constrained('brands')->nullOnDelete();
        });

        $now = now();

        foreach (self::INITIAL_BRANDS as $index => [$name, $slug, $logo]) {
            $brandId = DB::table('brands')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'logo' => $logo,
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Backfill from the product name only. The old storefront also matched
            // descriptions, but that mislabels accessories that merely mention a
            // brand (e.g. "charger for Dell laptops"). Admins can adjust the rest.
            DB::table('products')
                ->whereNull('brand_id')
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($name) . '%'])
                ->update(['brand_id' => $brandId]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });

        Schema::dropIfExists('brands');
    }
};
