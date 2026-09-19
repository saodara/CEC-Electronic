<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->paginate(10);

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        $brand = new Brand(['is_active' => true]);

        return view('admin.brands.create', compact('brand'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $this->storeUploadedLogo($request, $data);

        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('status', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $brand->name === $data['name'] ? $brand->slug : $this->uniqueSlug($data['name'], $brand->id);
        $this->storeUploadedLogo($request, $data, $brand);

        $brand->update($data);

        return redirect()->route('admin.brands.index')->with('status', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        // products.brand_id is nullOnDelete, so its products are kept, just unbranded.
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('status', 'Brand deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'is_active' => false,
            'sort_order' => 0,
        ];
    }

    private function storeUploadedLogo(Request $request, array &$data, ?Brand $brand = null): void
    {
        if (! $request->hasFile('logo')) {
            unset($data['logo']);
            return;
        }

        $path = $request->file('logo')->store('brands', 'public');
        $data['logo'] = $path;

        if ($brand?->logo && ! Str::startsWith($brand->logo, ['http://', 'https://', '/', 'images/'])) {
            Storage::disk('public')->delete($brand->logo);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Brand::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
