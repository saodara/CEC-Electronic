<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'products' => Product::count(),
            'categories' => Category::count(),
            'orders' => Order::count(),
            'customers' => Order::query()->distinct('customer_phone')->count('customer_phone'),
            'revenue' => Order::sum('grand_total'),
            'payment_notifications' => Order::query()
                ->whereNotNull('payment_confirmed_at')
                ->whereNull('admin_payment_seen_at')
                ->count(),
            'low_stock' => Product::query()->where('stock_quantity', '<=', 5)->count(),
            'value' => Product::sum('price'),
        ];

        $latestProducts = Product::query()->latest()->take(6)->get();
        $latestOrders = Order::query()->latest()->take(6)->get();
        $paymentNotifications = Order::query()
            ->whereNotNull('payment_confirmed_at')
            ->whereNull('admin_payment_seen_at')
            ->latest('payment_confirmed_at')
            ->take(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'latestProducts', 'latestOrders', 'paymentNotifications'));
    }

    public function index(): View
    {
        $products = Product::query()->with(['categoryRelation', 'supplier'])->latest()->paginate(12);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $product = new Product();
        $categories = Category::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('product', 'categories', 'suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $this->storeUploadedImage($request, $data);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::query()->orderBy('name')->get();
        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $data['slug'] = $product->name === $data['name']
            ? $product->slug
            : $this->uniqueSlug($data['name'], $product->id);
        $this->storeUploadedImage($request, $data, $product);

        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0.01'],
            'cost_price' => ['nullable', 'numeric', 'min:0.01'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:120', Rule::unique('products', 'sku')->ignore($product?->id)],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'category' => ['nullable', 'string', 'max:80'],
        ]) + [
            'stock_quantity' => 0,
            'is_active' => false,
            'is_featured' => false,
        ];
    }

    private function storeUploadedImage(Request $request, array &$data, ?Product $product = null): void
    {
        if (! $request->hasFile('image')) {
            unset($data['image']);
            return;
        }

        $path = $request->file('image')->store('products', 'public');
        $data['image'] = $path;

        if ($product?->image && ! Str::startsWith($product->image, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($product->image);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Product::query()
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
