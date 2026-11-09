<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Third batch of restored product photos. Re-runs the first restore, which
 * only touches products whose current image is empty or missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        (require __DIR__.'/2026_11_09_000001_restore_missing_product_images.php')->up();
    }

    public function down(): void
    {
        //
    }
};
