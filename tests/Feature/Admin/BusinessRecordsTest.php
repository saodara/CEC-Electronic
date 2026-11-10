<?php

namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_is_created_once_from_a_sale_order_and_downloads(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'paid', 'grand_total' => 75]);

        $this->get(route('admin.orders.show', $order))->assertSee('Create invoice');

        $this->post(route('admin.invoices.store', $order))->assertRedirect();
        $this->post(route('admin.invoices.store', $order))->assertRedirect();
        $this->assertSame(1, Invoice::count());
        $invoice = Invoice::firstOrFail();

        $this->get(route('admin.orders.show', $order))->assertSee('Open invoice');
        $this->get(route('admin.invoices.index'))->assertOk()->assertSee($invoice->invoice_number);
        $this->get(route('admin.invoices.show', $invoice))->assertOk()->assertSee('$75.00');

        $pdf = $this->get(route('admin.invoices.pdf', $invoice))->assertOk()->assertDownload($invoice->invoice_number.'.pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_invoice_index_only_lists_invoices_for_paid_orders(): void
    {
        $this->actingAsAdmin();
        $paid = Invoice::create([
            'invoice_number' => 'INV-PAID',
            'order_id' => Order::factory()->create(['payment_status' => 'paid'])->id,
            'issued_on' => now()->toDateString(),
        ]);

        foreach (['unpaid', 'failed', 'refunded'] as $status) {
            Invoice::create([
                'invoice_number' => 'INV-'.strtoupper($status),
                'order_id' => Order::factory()->create(['payment_status' => $status])->id,
                'issued_on' => now()->toDateString(),
            ]);
        }

        $this->get(route('admin.invoices.index'))
            ->assertOk()
            ->assertViewHas('invoices', fn ($invoices) => $invoices->total() === 1 && $invoices->first()->is($paid));
    }

    public function test_paying_an_order_issues_its_invoice_once(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);
        $this->assertSame(0, Invoice::count());

        $order->update(['payment_status' => 'paid', 'payment_confirmed_at' => now()]);
        $invoice = Invoice::where('order_id', $order->id)->firstOrFail();

        $order->update(['payment_status' => 'unpaid']);
        $order->update(['payment_status' => 'paid']);
        $this->assertSame(1, Invoice::count());

        $this->get(route('admin.invoices.index'))->assertOk()->assertSee($invoice->invoice_number);
    }

    public function test_paying_a_cancelled_order_does_not_issue_an_invoice(): void
    {
        $order = Order::factory()->create(['status' => 'cancelled', 'payment_status' => 'unpaid']);

        $order->update(['payment_status' => 'paid']);

        $this->assertSame(0, Invoice::count());
    }

    public function test_backfill_issues_invoices_only_for_paid_orders_missing_one(): void
    {
        $paid = Order::factory()->create(['status' => 'completed', 'payment_status' => 'paid', 'payment_confirmed_at' => '2026-11-01 12:00:00']);
        $existing = Order::factory()->create(['status' => 'pending', 'payment_status' => 'paid']);
        $kept = Invoice::create(['invoice_number' => 'INV-KEPT', 'order_id' => $existing->id, 'issued_on' => '2026-10-01']);
        Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);
        Order::factory()->create(['status' => 'cancelled', 'payment_status' => 'paid']);

        $migration = require database_path('migrations/2026_11_10_000001_issue_invoices_for_paid_orders.php');
        $migration->up();
        $migration->up();

        $this->assertSame(2, Invoice::count());
        $this->assertSame('2026-11-01', Invoice::where('order_id', $paid->id)->firstOrFail()->issued_on->toDateString());
        $this->assertSame('INV-KEPT', $kept->fresh()->invoice_number);
    }

    public function test_invoice_shows_ship_to_discount_and_balance_due(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create([
            'payment_status' => 'unpaid', 'subtotal' => 100, 'shipping_total' => 5, 'discount_total' => 10, 'grand_total' => 95,
            'shipping_address' => ['address_line_1' => 'Street 271', 'city' => 'Phnom Penh', 'country' => 'Cambodia'],
        ]);
        $this->post(route('admin.invoices.store', $order));
        $invoice = Invoice::firstOrFail();

        $this->get(route('admin.invoices.show', $invoice))->assertOk()
            ->assertSee('Unpaid')->assertSee('Ship to')->assertSee('Street 271')
            ->assertSee('-$10.00', false)->assertSee('Balance due')->assertSee('$95.00')
            ->assertDontSee(route('admin.orders.receipt', $order));

        $view = $this->get(route('admin.invoices.pdf', [$invoice, 'view' => 1]))->assertOk();
        $this->assertStringStartsWith('%PDF', $view->getContent());
        $this->assertStringContainsString('inline', (string) $view->headers->get('content-disposition'));

        $order->update(['payment_status' => 'paid', 'payment_confirmed_at' => now()]);
        $this->get(route('admin.invoices.show', $invoice))->assertSee('Paid')->assertSee(route('admin.orders.receipt', $order));
        $html = view('admin.invoices.pdf', ['invoice' => $invoice->fresh()->load('order.items')])->render();
        $this->assertStringContainsString('PAID', $html);
        $this->assertStringContainsString('Paid in full', $html);
    }

    public function test_cancelled_order_cannot_get_an_invoice(): void
    {
        $this->actingAsAdmin();
        $order = Order::factory()->create(['status' => 'cancelled']);

        $this->post(route('admin.invoices.store', $order))->assertSessionHasErrors('invoice');
        $this->assertSame(0, Invoice::count());
    }

    public function test_stock_adjustment_changes_stock_and_keeps_a_record(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->post(route('admin.stock-adjustments.store'), [
            'product_id' => $product->id, 'direction' => 'remove', 'quantity' => 4, 'reason' => 'Damaged',
        ])->assertSessionHasNoErrors();
        $this->assertSame(6, $product->fresh()->stock_quantity);

        $this->post(route('admin.stock-adjustments.store'), [
            'product_id' => $product->id, 'direction' => 'add', 'quantity' => 1, 'reason' => 'Customer return',
        ])->assertSessionHasNoErrors();
        $this->assertSame(7, $product->fresh()->stock_quantity);

        $latest = StockAdjustment::latest('id')->firstOrFail();
        $this->assertSame(1, $latest->quantity_change);
        $this->assertSame(7, $latest->stock_after);
        $this->get(route('admin.stock-adjustments.index'))->assertOk()->assertSee('Removed 4')->assertSee('Added 1');
        $this->get(route('admin.stock-adjustments.create'))->assertOk();
    }

    public function test_stock_cannot_go_below_zero(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['stock_quantity' => 2]);

        $this->post(route('admin.stock-adjustments.store'), [
            'product_id' => $product->id, 'direction' => 'remove', 'quantity' => 3, 'reason' => 'Damaged',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertSame(0, StockAdjustment::count());
    }

    public function test_expenses_can_be_added_changed_and_deleted(): void
    {
        $this->actingAsAdmin();
        $this->get(route('admin.expenses.create'))->assertOk();

        $this->post(route('admin.expenses.store'), [
            'title' => 'Shop rent', 'category' => 'Rent', 'amount' => 500, 'spent_on' => '2026-11-01',
        ])->assertSessionHasNoErrors();
        $expense = Expense::firstOrFail();
        $this->get(route('admin.expenses.index'))->assertOk()->assertSee('Shop rent')->assertSee('$500.00');
        $this->get(route('admin.expenses.edit', $expense))->assertOk();

        $this->put(route('admin.expenses.update', $expense), [
            'title' => 'Shop rent', 'category' => 'Rent', 'amount' => 550, 'spent_on' => '2026-11-01',
        ])->assertSessionHasNoErrors();
        $this->assertSame('550.00', $expense->fresh()->amount);

        $this->post(route('admin.expenses.store'), ['title' => '', 'category' => 'Nope', 'amount' => 0, 'spent_on' => 'x'])
            ->assertSessionHasErrors(['title', 'category', 'amount', 'spent_on']);

        $this->delete(route('admin.expenses.destroy', $expense))->assertSessionHasNoErrors();
        $this->assertSame(0, Expense::count());
    }
}
