<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Laptops', 'slug' => 'laptops', 'sort_order' => 1],
            ['name' => 'Desktop PC', 'slug' => 'desktops', 'sort_order' => 2],
            ['name' => 'Gaming', 'slug' => 'gaming', 'sort_order' => 3],
            ['name' => 'Monitor & Display', 'slug' => 'monitors', 'sort_order' => 4],
            ['name' => 'Projectors', 'slug' => 'projectors', 'sort_order' => 5],
            ['name' => 'Computer Components', 'slug' => 'components', 'sort_order' => 6],
            ['name' => 'Printers & Scanners', 'slug' => 'printers', 'sort_order' => 7],
            ['name' => 'Accessories', 'slug' => 'accessories', 'sort_order' => 8],
            ['name' => 'Phones & Tablets', 'slug' => 'phones', 'sort_order' => 9],
        ])->mapWithKeys(function (array $category) {
            $model = Category::updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);

            return [$category['slug'] => $model];
        });

        $imageDirectory = public_path('images/Product Image');
        $files = File::exists($imageDirectory) ? File::files($imageDirectory) : [];

        foreach ($files as $index => $file) {
            $name = $this->cleanProductName($file->getFilenameWithoutExtension());
            $slug = Str::slug($name);
            $categorySlug = $this->guessCategorySlug($name);
            $price = $this->guessPrice($name);

            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categories[$categorySlug]->id,
                    'sku' => 'CEC-' . strtoupper(substr($categorySlug, 0, 3)) . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'price' => $price,
                    'compare_at_price' => $price >= 80 ? $price + $this->discountAmount($price) : null,
                    'stock_quantity' => $this->stockForCategory($categorySlug, $index),
                    'is_active' => true,
                    'is_featured' => $index < 24,
                    'image' => 'images/Product Image/' . $file->getFilename(),
                    'category' => $categorySlug,
                    'description' => $this->descriptionFor($name, $categorySlug),
                    'specifications' => $this->specificationsFor($name, $categorySlug),
                ]
            );
        }
    }

    private function cleanProductName(string $filename): string
    {
        $name = str_replace(['_', '  '], [' ', ' '], $filename);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    private function guessCategorySlug(string $name): string
    {
        $lower = Str::lower($name);

        return match (true) {
            Str::contains($lower, ['printer', 'scanner', 'receipt']) => 'printers',
            Str::contains($lower, ['projector']) => 'projectors',
            Str::contains($lower, ['monitor', 'display', 'flat panel']) => 'monitors',
            Str::contains($lower, ['macbook', 'laptop', 'notebook', 'vivobook', 'thinkpad', 'ideapad', 'legion', 'cyborg', 'nitro']) => 'laptops',
            Str::contains($lower, ['gaming mouse', 'gaming', 'gamepad']) => 'gaming',
            Str::contains($lower, ['desktop', 'tower', 'mini', 'imac', 'aio', 'custom pc', 'pos']) => 'desktops',
            Str::contains($lower, ['ssd', 'hdd', 'ram', 'm.2', 'ddr', 'nvme']) => 'components',
            Str::contains($lower, ['tablet']) => 'phones',
            default => 'accessories',
        };
    }

    private function guessPrice(string $name): int
    {
        $lower = Str::lower($name);

        return match (true) {
            Str::contains($lower, ['macbook pro 16', 'm3 max']) => 2499,
            Str::contains($lower, ['macbook', 'imac']) => 1299,
            Str::contains($lower, ['gaming', 'legion', 'cyborg', 'tuf']) => 1099,
            Str::contains($lower, ['laptop', 'thinkpad', 'ideapad', 'vivobook', 'inspiron']) => 649,
            Str::contains($lower, ['projector', 'flat panel']) => 799,
            Str::contains($lower, ['desktop', 'tower', 'aio', 'pos']) => 549,
            Str::contains($lower, ['printer']) => 229,
            Str::contains($lower, ['monitor']) => 189,
            Str::contains($lower, ['ssd', 'hdd']) => 89,
            Str::contains($lower, ['ram']) => 45,
            Str::contains($lower, ['keyboard', 'mouse', 'webcam', 'hub', 'adapter', 'usb']) => 35,
            default => 99,
        };
    }

    private function discountAmount(int $price): int
    {
        return match (true) {
            $price >= 1000 => 100,
            $price >= 500 => 50,
            $price >= 100 => 20,
            default => 10,
        };
    }

    private function stockForCategory(string $categorySlug, int $index): int
    {
        return match ($categorySlug) {
            'components', 'accessories' => 25 + ($index % 40),
            'laptops', 'desktops', 'gaming' => 5 + ($index % 12),
            default => 8 + ($index % 18),
        };
    }

    private function descriptionFor(string $name, string $categorySlug): string
    {
        return match ($categorySlug) {
            'laptops' => $name . ' with local CEC Electronic support, warranty service, and store pickup or delivery.',
            'gaming' => $name . ' for gaming setups with strong performance and CEC after-sales support.',
            'desktops' => $name . ' ready for office, school, POS, and business use.',
            'monitors' => $name . ' display for office, gaming, meeting rooms, and home workstations.',
            'projectors' => $name . ' projector solution for classroom, office, and meeting room presentation.',
            'components' => $name . ' upgrade component for computer repair, storage, and performance improvement.',
            'printers' => $name . ' printer and scanner product for business, school, and home office work.',
            'phones' => $name . ' mobile and tablet device available from CEC Electronic.',
            default => $name . ' accessory available from CEC Electronic with local support.',
        };
    }

    private function specificationsFor(string $name, string $categorySlug): array
    {
        return [
            'Category' => Category::where('slug', $categorySlug)->value('name') ?: ucfirst($categorySlug),
            'Warranty' => 'CEC Electronic store warranty',
            'Image' => 'Local product image',
            'Support' => 'Store pickup and delivery available',
        ];
    }
}
