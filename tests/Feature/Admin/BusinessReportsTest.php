<?php

namespace Tests\Feature\Admin;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-15 05:00:00', 'UTC'));
        $this->actingAsAdmin();
    }

    private function sale(array $order, array $items, string $createdAt = '2026-11-10 05:00:00'): Order
    {
        $sale = Order::factory()->create($order + [
            'status' => 'completed',
            'payment_status' => 'paid',
            'discount_total' => 0,
            'shipping_total' => 0,
            'customer_phone' => '012000000',
            'customer_name' => 'Dara',
            'created_at' => $createdAt,
        ]);

        foreach ($items as $item) {
            $sale->items()->create($item + ['product_name' => 'Item', 'quantity' => 1]);
        }

        return $sale;
    }

    public function test_guest_cannot_open_reports(): void
    {
        auth()->logout();

        foreach (['profit-loss', 'products', 'customers', 'suppliers'] as $report) {
            $this->get(route('admin.reports.'.$report))->assertRedirect(route('admin.login'));
        }
    }

    public function test_profit_and_loss_adds_up(): void
    {
        $product = Product::factory()->create(['cost_price' => 30]);

        // Saved cost wins over the product's current cost.
        $this->sale(['subtotal' => 200, 'shipping_total' => 5, 'grand_total' => 205], [
            ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'unit_cost' => 40, 'line_total' => 200],
        ]);
        // Older order with no saved cost falls back to the product's cost.
        $this->sale(['subtotal' => 100, 'grand_total' => 100], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'unit_cost' => null, 'line_total' => 100],
        ]);
        // None of these count.
        $this->sale(['subtotal' => 900, 'payment_status' => 'unpaid'], [['unit_price' => 900, 'line_total' => 900, 'unit_cost' => 1]]);
        $this->sale(['subtotal' => 900, 'status' => 'cancelled'], [['unit_price' => 900, 'line_total' => 900, 'unit_cost' => 1]]);
        $this->sale(['subtotal' => 900, 'payment_status' => 'refunded'], [['unit_price' => 900, 'line_total' => 900, 'unit_cost' => 1]]);
        $this->sale(['subtotal' => 900], [['unit_price' => 900, 'line_total' => 900, 'unit_cost' => 1]], '2026-10-10 05:00:00');

        Expense::create(['title' => 'Rent', 'category' => 'Rent', 'amount' => 50, 'spent_on' => '2026-11-01']);
        Expense::create(['title' => 'Power', 'category' => 'Utilities', 'amount' => 25, 'spent_on' => '2026-11-15']);
        Expense::create(['title' => 'Old', 'category' => 'Rent', 'amount' => 999, 'spent_on' => '2026-10-31']);

        $this->get(route('admin.reports.profit-loss'))
            ->assertOk()
            ->assertViewHas('revenue', 300.0)
            ->assertViewHas('shipping', 5.0)
            ->assertViewHas('cost', 110.0)
            ->assertViewHas('grossProfit', 195.0)
            ->assertViewHas('expenseTotal', 75.0)
            ->assertViewHas('netProfit', 120.0)
            ->assertViewHas('missingCost', 0)
            ->assertSee('Net profit')
            ->assertSee('$120.00');
    }

    public function test_profit_and_loss_shows_a_loss_and_warns_about_missing_costs(): void
    {
        $this->sale(['subtotal' => 10, 'grand_total' => 10], [['unit_price' => 10, 'line_total' => 10, 'unit_cost' => null]]);
        Expense::create(['title' => 'Rent', 'category' => 'Rent', 'amount' => 50, 'spent_on' => '2026-11-01']);

        $this->get(route('admin.reports.profit-loss'))
            ->assertViewHas('netProfit', -40.0)
            ->assertViewHas('missingCost', 1)
            ->assertSee('Net loss')
            ->assertSee('-$40.00')
            ->assertSee('no cost price');
    }

    public function test_report_dates_follow_phnom_penh_time_and_can_be_chosen(): void
    {
        $this->sale(['subtotal' => 1, 'grand_total' => 1], [], '2026-10-31 16:59:59');
        $this->sale(['subtotal' => 2, 'grand_total' => 2], [], '2026-10-31 17:00:00');
        $this->sale(['subtotal' => 4, 'grand_total' => 4], [], '2026-11-30 16:59:59');
        $this->sale(['subtotal' => 8, 'grand_total' => 8], [], '2026-11-30 17:00:00');

        $this->get(route('admin.reports.profit-loss', ['from' => '2026-11-01', 'to' => '2026-11-30']))
            ->assertViewHas('revenue', 6.0);

        // Reversed or invalid dates don't break the page.
        $this->get(route('admin.reports.profit-loss', ['from' => '2026-11-30', 'to' => '2026-11-01']))
            ->assertViewHas('revenue', 6.0);
        $this->get(route('admin.reports.profit-loss', ['from' => 'nope', 'to' => ['x']]))->assertOk();
    }

    public function test_product_revenue_ranks_products_and_flags_missing_costs(): void
    {
        $this->sale(['subtotal' => 50], [['product_name' => 'Mouse', 'quantity' => 5, 'unit_price' => 10, 'unit_cost' => 6, 'line_total' => 50]]);
        $this->sale(['subtotal' => 300], [['product_name' => 'Monitor', 'quantity' => 2, 'unit_price' => 150, 'unit_cost' => null, 'line_total' => 300]]);
        $this->sale(['subtotal' => 20], [['product_name' => 'Mouse', 'quantity' => 2, 'unit_price' => 10, 'unit_cost' => 6, 'line_total' => 20]]);

        $this->get(route('admin.reports.products'))
            ->assertOk()
            ->assertSeeInOrder(['Monitor', '$300.00', 'No cost price set', 'Mouse', '7', '$70.00', '$42.00', '$28.00'])
            ->assertSee('9 sold for $370.00');
    }

    public function test_customer_report_totals_paid_orders_per_customer(): void
    {
        $this->sale(['grand_total' => 100, 'customer_phone' => '011', 'customer_name' => 'Sok'], []);
        $this->sale(['grand_total' => 50, 'customer_phone' => '011', 'customer_name' => 'Sok'], []);
        $this->sale(['grand_total' => 500, 'customer_phone' => '022', 'customer_name' => 'Dara'], []);
        $this->sale(['grand_total' => 999, 'customer_phone' => '033', 'customer_name' => 'Unpaid', 'payment_status' => 'unpaid'], []);

        $this->get(route('admin.reports.customers'))
            ->assertOk()
            ->assertSeeInOrder(['Dara', '$500.00', 'Sok', '$150.00'])
            ->assertDontSee('Unpaid')
            ->assertSee('2 customers, 3 orders, $650.00');
    }

    public function test_supplier_report_splits_paid_and_unpaid_bills(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Acme']);
        $received = fn (float $total, string $date) => PurchaseOrder::create([
            'po_number' => 'PO-'.$total, 'supplier_id' => $supplier->id, 'status' => 'received',
            'subtotal' => $total, 'grand_total' => $total, 'received_date' => $date,
        ]);
        $bill = fn (PurchaseOrder $order, string $status, string $date) => Bill::create([
            'bill_number' => 'B-'.$order->id, 'supplier_id' => $supplier->id, 'purchase_order_id' => $order->id,
            'bill_date' => $date, 'due_date' => $date, 'total' => $order->grand_total, 'status' => $status,
        ]);

        $bill($received(100, '2026-11-02'), 'paid', '2026-11-02');
        $bill($received(40, '2026-11-03'), 'unpaid', '2026-11-03');
        $bill($received(999, '2026-10-03'), 'unpaid', '2026-10-03');
        $bill($received(7, '2026-11-15'), 'paid', '2026-11-15');

        $this->get(route('admin.reports.suppliers'))
            ->assertOk()
            ->assertViewHas('rows', function ($rows) {
                $row = $rows->firstWhere('name', 'Acme');

                return (int) $row->received_count === 3
                    && (float) $row->received_total === 147.0
                    && (float) $row->billed_total === 147.0
                    && (float) $row->paid_total === 107.0
                    && (float) $row->unpaid_total === 40.0;
            });
    }

    public function test_reports_render_with_no_data(): void
    {
        foreach (['profit-loss', 'products', 'customers', 'suppliers'] as $report) {
            $this->get(route('admin.reports.'.$report))->assertOk();
        }
    }
}
