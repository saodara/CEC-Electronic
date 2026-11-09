<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RestoreMissingProductImagesTest extends TestCase
{
    use RefreshDatabase;

    private function runRestore(): void
    {
        (require database_path('migrations/2026_11_09_000001_restore_missing_product_images.php'))->up();
    }

    public function test_products_with_missing_photos_get_the_restored_photo(): void
    {
        Storage::fake('public');
        $lost = Product::factory()->create(['slug' => 'archer-ax73-wifi-6-router', 'image' => 'products/gone.jpg']);
        $empty = Product::factory()->create(['slug' => 'archer-gx90', 'image' => null]);

        $this->runRestore();

        $this->assertSame('images/products-restored/archer-ax73-wifi-6-router.jpg', $lost->fresh()->image);
        $this->assertSame('images/products-restored/archer-gx90.jpg', $empty->fresh()->image);
        $this->assertSame(asset('images/products-restored/archer-gx90.jpg'), $empty->fresh()->image_url);
        $this->assertFileExists(public_path($lost->fresh()->image));
    }

    public function test_photos_that_still_exist_are_left_alone(): void
    {
        Storage::fake('public');
        $uploaded = UploadedFile::fake()->image('new.jpg')->store('products', 'public');
        $reuploaded = Product::factory()->create(['slug' => 'archer-ax55-wifi-6-router', 'image' => $uploaded]);
        $builtIn = Product::factory()->create(['slug' => 'archer-ax23-wifi-6-router', 'image' => 'images/product-placeholder.svg']);
        $other = Product::factory()->create(['slug' => 'not-in-the-folder', 'image' => 'products/gone.jpg']);

        $this->runRestore();

        $this->assertSame($uploaded, $reuploaded->fresh()->image);
        $this->assertSame('images/product-placeholder.svg', $builtIn->fresh()->image);
        $this->assertSame('products/gone.jpg', $other->fresh()->image);
    }

    public function test_every_restored_photo_belongs_to_a_known_product_slug(): void
    {
        $files = glob(public_path('images/products-restored/*.jpg'));

        $this->assertCount(61, $files);
        foreach ($files as $file) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', basename($file, '.jpg'));
            $this->assertLessThan(300 * 1024, filesize($file), basename($file).' is too large');
        }
    }
}
