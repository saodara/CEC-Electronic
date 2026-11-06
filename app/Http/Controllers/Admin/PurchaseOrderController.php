<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'bill'])
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return view('admin.purchase-orders.index', compact('purchaseOrders'));
    }

    public function create(): View
    {
        return view('admin.purchase-orders.create', $this->formData(new PurchaseOrder));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $purchaseOrder = DB::transaction(function () use ($data) {
            $purchaseOrder = PurchaseOrder::create($this->header($data) + [
                'po_number' => $this->uniqueNumber('PO', 'po_number', PurchaseOrder::class),
                'status' => 'draft',
            ]);
            $this->saveItems($purchaseOrder, $data['items']);

            return $purchaseOrder;
        });

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('status', 'Purchase order created.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'items.product', 'bill']);

        return view('admin.purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        if ($purchaseOrder->status !== 'draft') {
            return $this->rejected($purchaseOrder, 'Only draft purchase orders can be edited.');
        }

        return view('admin.purchase-orders.edit', $this->formData($purchaseOrder->load('items')));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== 'draft') {
            return $this->rejected($purchaseOrder, 'Only draft purchase orders can be edited.');
        }

        $data = $this->validatedData($request);

        DB::transaction(function () use ($purchaseOrder, $data) {
            $purchaseOrder->update($this->header($data));
            $purchaseOrder->items()->delete();
            $this->saveItems($purchaseOrder, $data['items']);
        });

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('status', 'Purchase order updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== 'draft') {
            return $this->rejected($purchaseOrder, 'Only draft purchase orders can be deleted. Cancel this one instead.');
        }

        $purchaseOrder->delete();

        return redirect()->route('admin.purchase-orders.index')->with('status', 'Purchase order deleted.');
    }

    public function markOrdered(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return $this->changeStatus($purchaseOrder, ['draft'], 'ordered', 'Purchase order marked as ordered.');
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        return $this->changeStatus($purchaseOrder, ['draft', 'ordered'], 'cancelled', 'Purchase order cancelled.');
    }

    /**
     * Add the goods to stock. The row lock and status check stop a double click counting them twice.
     */
    public function receive(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $received = DB::transaction(function () use ($purchaseOrder) {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);

            if ($locked->status !== 'ordered') {
                return false;
            }

            foreach ($locked->items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                $product?->update([
                    'stock_quantity' => $product->stock_quantity + $item->quantity,
                    'cost_price' => $item->unit_cost,
                ]);
            }

            $locked->update(['status' => 'received', 'received_date' => $this->today()]);

            return true;
        });

        return $received
            ? redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('status', 'Goods received. Stock and product costs were updated.')
            : $this->rejected($purchaseOrder, 'Only ordered purchase orders can be received.');
    }

    public function createBill(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== 'received') {
            return $this->rejected($purchaseOrder, 'A bill can be created after the goods are received.');
        }

        $bill = Bill::query()->firstOrCreate(['purchase_order_id' => $purchaseOrder->id], [
            'bill_number' => $this->uniqueNumber('BILL', 'bill_number', Bill::class),
            'supplier_id' => $purchaseOrder->supplier_id,
            'bill_date' => $this->today(),
            'due_date' => now(config('app.shop_timezone'))->addDays(30)->toDateString(),
            'total' => $purchaseOrder->grand_total,
            'status' => 'unpaid',
        ]);

        return redirect()->route('admin.bills.show', $bill)->with('status', 'Bill ready.');
    }

    private function changeStatus(PurchaseOrder $purchaseOrder, array $from, string $to, string $message): RedirectResponse
    {
        $changed = PurchaseOrder::query()
            ->whereKey($purchaseOrder->id)
            ->whereIn('status', $from)
            ->update(['status' => $to]);

        return $changed
            ? back()->with('status', $message)
            : $this->rejected($purchaseOrder, 'This purchase order can no longer be changed that way.');
    }

    private function rejected(PurchaseOrder $purchaseOrder, string $message): RedirectResponse
    {
        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->withErrors(['purchase_order' => $message]);
    }

    private function formData(PurchaseOrder $purchaseOrder): array
    {
        return [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'cost_price']),
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ], [], [
            'items.*.product_id' => 'product',
            'items.*.quantity' => 'quantity',
            'items.*.unit_cost' => 'unit cost',
        ]);
    }

    private function header(array $data): array
    {
        return [
            'supplier_id' => $data['supplier_id'],
            'expected_date' => $data['expected_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function saveItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        $total = 0;

        foreach ($items as $item) {
            $lineTotal = round($item['quantity'] * $item['unit_cost'], 2);
            $total += $lineTotal;

            $purchaseOrder->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $lineTotal,
            ]);
        }

        $purchaseOrder->update(['subtotal' => $total, 'grand_total' => $total]);
    }

    private function today(): string
    {
        return now(config('app.shop_timezone'))->toDateString();
    }

    private function uniqueNumber(string $prefix, string $column, string $model): string
    {
        do {
            $number = $prefix.'-'.now(config('app.shop_timezone'))->format('Ymd').'-'.random_int(1000, 9999);
        } while ($model::query()->where($column, $number)->exists());

        return $number;
    }
}
