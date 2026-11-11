<?php

namespace Tests\Feature\Console;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $supplier = Supplier::factory()->create();
        Product::factory()->count(6)->create(['supplier_id' => $supplier->id, 'price' => 100, 'stock_quantity' => 7, 'cost_price' => null]);
        $this->product = Product::firstOrFail();
    }

    public function test_seed_fills_each_empty_page_without_touching_real_records(): void
    {
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => 'bakong']);
        $cancelled = Order::factory()->create(['status' => 'cancelled']);

        $this->artisan('demo:data', ['action' => 'seed'])->assertSuccessful();

        $this->assertSame(5, PurchaseOrder::where('po_number', 'like', 'DEMO-%')->count());
        $this->assertSame(['cancelled', 'draft', 'ordered', 'received', 'received'], PurchaseOrder::pluck('status')->sort()->values()->all());
        $this->assertSame(2, Bill::count());
        $this->assertSame(1, Invoice::where('order_id', $order->id)->count());
        $this->assertSame(0, Invoice::where('order_id', $cancelled->id)->count());
        $this->assertSame(3, StockAdjustment::count());
        $this->assertSame(4, Expense::count());
        $this->assertGreaterThan(0, (float) PurchaseOrder::firstOrFail()->grand_total);

        Product::all()->each(function (Product $product) {
            $this->assertSame(7, $product->stock_quantity);
            $this->assertNull($product->cost_price);
        });
        $this->assertNull($order->fresh()->stock_deducted_at);
    }

    public function test_every_page_opens_with_sample_records(): void
    {
        Order::factory()->create(['payment_status' => 'paid']);
        $this->artisan('demo:data', ['action' => 'seed']);
        $this->actingAsAdmin();

        foreach (['purchase-orders.index', 'invoices.index', 'stock-adjustments.index', 'expenses.index'] as $page) {
            $this->get(route('admin.'.$page))->assertOk()->assertSee('DEMO');
        }

        $this->get(route('admin.reports.profit-loss'))->assertOk()->assertViewHas('expenseTotal', 0.0);

        $this->get(route('admin.purchase-orders.show', PurchaseOrder::firstOrFail()))->assertOk();
        $this->get(route('admin.invoices.show', Invoice::firstOrFail()))->assertOk();
    }

    public function test_seeding_twice_does_not_duplicate(): void
    {
        $this->artisan('demo:data', ['action' => 'seed']);
        $this->artisan('demo:data', ['action' => 'seed'])->assertSuccessful();

        $this->assertSame(5, PurchaseOrder::count());
    }

    public function test_remove_deletes_only_sample_records(): void
    {
        $realOrder = Order::factory()->create();
        $realInvoice = Invoice::create(['invoice_number' => 'INV-1', 'order_id' => $realOrder->id, 'issued_on' => '2026-11-01']);
        $realPurchase = PurchaseOrder::create(['po_number' => 'PO-1', 'supplier_id' => Supplier::firstOrFail()->id, 'status' => 'received', 'subtotal' => 5, 'grand_total' => 5]);
        $realBill = Bill::create(['bill_number' => 'BILL-1', 'supplier_id' => $realPurchase->supplier_id, 'purchase_order_id' => $realPurchase->id, 'bill_date' => '2026-11-01', 'due_date' => '2026-11-30', 'total' => 5]);
        $realExpense = Expense::create(['title' => 'Real rent', 'category' => 'Rent', 'amount' => 9, 'spent_on' => '2026-11-01']);
        $realAdjustment = StockAdjustment::create(['product_id' => $this->product->id, 'product_name' => 'Real', 'quantity_change' => 1, 'stock_after' => 8, 'reason' => 'Other']);
        Order::factory()->create();

        $this->artisan('demo:data', ['action' => 'seed']);
        $this->artisan('demo:data', ['action' => 'remove'])->assertSuccessful();

        $this->assertSame([$realPurchase->id], PurchaseOrder::pluck('id')->all());
        $this->assertSame([$realBill->id], Bill::pluck('id')->all());
        $this->assertSame([$realInvoice->id], Invoice::pluck('id')->all());
        $this->assertSame([$realExpense->id], Expense::pluck('id')->all());
        $this->assertSame([$realAdjustment->id], StockAdjustment::pluck('id')->all());
        $this->assertSame(6, Product::count());
    }

    public function test_unknown_action_fails(): void
    {
        $this->artisan('demo:data', ['action' => 'nope'])->assertFailed();
    }
}
