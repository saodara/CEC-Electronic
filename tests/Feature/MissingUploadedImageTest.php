<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MissingUploadedImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_uploads_show_a_fallback_instead_of_a_broken_image(): void
    {
        Storage::fake('public');
        $placeholder = asset('images/product-placeholder.svg');

        $this->assertSame($placeholder, Product::factory()->make(['image' => 'products/gone.jpg'])->image_url);
        $this->assertSame($placeholder, (new Category(['image' => 'categories/gone.jpg']))->image_url);
        $this->assertNull((new Brand(['name' => 'Acme', 'logo' => 'brands/gone.png']))->logo_url);
    }

    public function test_uploads_that_exist_still_show(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('photo.jpg')->store('products', 'public');

        $this->assertSame(asset('storage/'.$path), Product::factory()->make(['image' => $path])->image_url);
        $this->assertSame(asset('storage/'.$path), (new Category(['image' => $path]))->image_url);
        $this->assertSame(asset('storage/'.$path), (new Brand(['name' => 'Acme', 'logo' => $path]))->logo_url);
    }

    public function test_built_in_images_are_not_checked_on_disk(): void
    {
        Storage::fake('public');

        $this->assertSame(asset('images/Brand/acer-logo.jpg'), (new Brand(['name' => 'Acer', 'logo' => 'images/Brand/acer-logo.jpg']))->logo_url);
    }
}
