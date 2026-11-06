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
        $order = Order::factory()->create(['status' => 'pending', 'grand_total' => 75]);

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
        $this->get(route('admin.stock-adjustments.index'))->assertOk()->assertSee('-4')->assertSee('+1');
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
