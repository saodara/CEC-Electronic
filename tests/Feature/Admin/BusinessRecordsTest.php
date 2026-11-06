<?php

namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    public function test_stock_adjustment_feature_is_gone(): void
    {
        $this->actingAsAdmin();

        $this->get('/admin/stock-adjustments')->assertNotFound();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Stock Adjustment');
        $this->assertFalse(Schema::hasTable('stock_adjustments'));
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
