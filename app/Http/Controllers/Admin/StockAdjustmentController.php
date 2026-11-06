<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(): View
    {
        $adjustments = StockAdjustment::query()->with('user')->latest()->paginate(10);

        return view('admin.stock-adjustments.index', compact('adjustments'));
    }

    public function create(): View
    {
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'stock_quantity']);

        return view('admin.stock-adjustments.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'direction' => ['required', Rule::in(['add', 'remove'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reason' => ['required', Rule::in(StockAdjustment::REASONS)],
            'notes' => ['nullable', 'string'],
        ]);

        $change = $data['direction'] === 'add' ? $data['quantity'] : -$data['quantity'];

        DB::transaction(function () use ($data, $change, $request) {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $stockAfter = $product->stock_quantity + $change;

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$product->stock_quantity} in stock, so you can't remove {$data['quantity']}.",
                ]);
            }

            $product->update(['stock_quantity' => $stockAfter]);

            StockAdjustment::create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity_change' => $change,
                'stock_after' => $stockAfter,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()?->id,
            ]);
        });

        return redirect()->route('admin.stock-adjustments.index')->with('status', 'Stock adjusted.');
    }
}
