<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Product photos uploaded before the Fly volume existed were deleted on
 * deploy. This points those products at photos kept in the repo
 * (public/images/products-restored/<slug>.jpg). A product is only changed
 * when its current image is empty or its file is missing, so a photo
 * re-uploaded by hand is never replaced.
 */
return new class extends Migration
{
    public function up(): void
    {
        $changed = 0;

        foreach (glob(public_path('images/products-restored/*.jpg')) ?: [] as $file) {
            $product = DB::table('products')->where('slug', basename($file, '.jpg'))->first(['id', 'image']);

            if (! $product || ! $this->imageIsMissing($product->image)) {
                continue;
            }

            DB::table('products')->where('id', $product->id)->update([
                'image' => 'images/products-restored/'.basename($file),
                'updated_at' => now(),
            ]);
            $changed++;
        }

        if ($changed > 0) {
            Cache::flush();
        }
    }

    public function down(): void
    {
        // The old image paths pointed at deleted files, so there is nothing to go back to.
    }

    private function imageIsMissing(?string $image): bool
    {
        if (! $image) {
            return true;
        }

        if (Str::startsWith($image, ['http://', 'https://', '/'])) {
            return false;
        }

        if (Str::startsWith($image, 'images/')) {
            return ! file_exists(public_path($image));
        }

        return ! Storage::disk('public')->exists($image);
    }
};
