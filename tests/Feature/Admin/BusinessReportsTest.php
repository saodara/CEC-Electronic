<?php

namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
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

        foreach (['profit-loss', 'products', 'customers'] as $report) {
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

    public function test_profit_and_loss_offers_a_pdf_for_each_date_range(): void
    {
        $page = $this->get(route('admin.reports.profit-loss'))->assertOk()->assertSee('Export PDF');

        foreach ([
            'Today' => ['2026-11-15', '2026-11-15'],
            'Last 7 days' => ['2026-11-09', '2026-11-15'],
            'This month' => ['2026-11-01', '2026-11-15'],
            'Last year' => ['2025-01-01', '2025-12-31'],
            'This year' => ['2026-01-01', '2026-11-15'],
        ] as $label => [$from, $to]) {
            $page->assertSee($label)->assertSee("reports/profit-loss/pdf?from={$from}&amp;to={$to}", false);

            $download = $this->get(route('admin.reports.profit-loss.pdf', ['from' => $from, 'to' => $to]))
                ->assertOk()
                ->assertDownload("profit-loss-{$from}-to-{$to}.pdf");
            $this->assertStringStartsWith('%PDF', $download->getContent());
        }

        $this->get(route('admin.reports.products'))->assertOk()->assertDontSee('Export PDF');
    }

    public function test_profit_and_loss_pdf_uses_the_chosen_dates_and_real_records(): void
    {
        $this->sale(['subtotal' => 200, 'shipping_total' => 5, 'grand_total' => 205], [['unit_price' => 200, 'line_total' => 200, 'unit_cost' => 50]], '2025-06-10 05:00:00');
        $this->sale(['subtotal' => 900, 'grand_total' => 900], [['unit_price' => 900, 'line_total' => 900, 'unit_cost' => 1]]);
        Expense::create(['title' => 'Rent', 'category' => 'Rent', 'amount' => 30, 'spent_on' => '2025-06-01']);
        Expense::create(['title' => '[DEMO] Ads', 'category' => 'Marketing', 'amount' => 70, 'spent_on' => '2025-06-01']);

        $data = null;
        View::composer('admin.reports.business.profit-loss-pdf', function ($view) use (&$data) {
            $data = $view->getData();
        });

        $this->get(route('admin.reports.profit-loss.pdf', ['from' => '2025-01-01', 'to' => '2025-12-31']))->assertOk();
        $html = view('admin.reports.business.profit-loss-pdf', $data)->render();

        $this->assertSame(125.0, $data['netProfit']);
        $this->assertStringContainsString('1 Jan 2025 to 31 Dec 2025', $html);
        $this->assertStringContainsString('Net profit', $html);
        $this->assertStringContainsString('$125.00', $html);
        $this->assertStringContainsString('Rent', $html);
        $this->assertStringNotContainsString('Marketing', $html);
    }

    public function test_profit_and_loss_lists_the_products_sold_on_the_page_and_pdf(): void
    {
        $this->sale(['subtotal' => 50, 'grand_total' => 50], [['product_name' => 'Mouse', 'quantity' => 5, 'unit_price' => 10, 'unit_cost' => 6, 'line_total' => 50]]);
        $this->sale(['subtotal' => 300, 'grand_total' => 300], [['product_name' => 'Monitor', 'quantity' => 2, 'unit_price' => 150, 'unit_cost' => null, 'line_total' => 300]]);
        $this->sale(['subtotal' => 900, 'grand_total' => 900, 'payment_status' => 'unpaid'], [['product_name' => 'Unpaid Laptop', 'unit_price' => 900, 'line_total' => 900, 'unit_cost' => 1]]);

        $expected = ['Products sold', 'Monitor', '2', '$300.00', 'No cost price set', 'Mouse', '5', '$50.00', '$30.00', '$20.00'];

        $this->get(route('admin.reports.profit-loss'))
            ->assertOk()
            ->assertSeeInOrder($expected)
            ->assertDontSee('Unpaid Laptop');

        $data = null;
        View::composer('admin.reports.business.profit-loss-pdf', function ($view) use (&$data) {
            $data = $view->getData();
        });
        $this->get(route('admin.reports.profit-loss.pdf'))->assertOk();
        $html = view('admin.reports.business.profit-loss-pdf', $data)->render();

        $position = 0;
        foreach ($expected as $text) {
            $found = strpos($html, $text, $position);
            $this->assertNotFalse($found, "PDF is missing {$text}");
            $position = $found;
        }
        $this->assertStringNotContainsString('Unpaid Laptop', $html);
    }

    private function pdfHtml(string $view, array $query): string
    {
        $data = null;
        View::composer($view, function ($composed) use (&$data) {
            $data = $composed->getData();
        });
        $this->get(route('admin.reports.customers.pdf', $query))->assertOk();

        return view($view, $data)->render();
    }

    private function customerSales(): void
    {
        $this->sale(['order_number' => 'SOK-1', 'grand_total' => 100, 'customer_phone' => '011 22', 'customer_name' => 'Sok'], [
            ['product_name' => 'Mouse', 'quantity' => 2, 'unit_price' => 50, 'line_total' => 100],
        ]);
        $this->sale(['order_number' => 'SOK-OLD', 'grand_total' => 40, 'customer_phone' => '011 22', 'customer_name' => 'Sok'], [
            ['product_name' => 'Old Cable', 'unit_price' => 40, 'line_total' => 40],
        ], '2026-06-10 05:00:00');
        $this->sale(['order_number' => 'SOK-UNPAID', 'grand_total' => 70, 'customer_phone' => '011 22', 'customer_name' => 'Sok', 'payment_status' => 'unpaid'], []);
        $this->sale(['order_number' => 'DARA-1', 'grand_total' => 500, 'customer_phone' => '022', 'customer_name' => 'Dara'], [
            ['product_name' => 'Monitor', 'unit_price' => 500, 'line_total' => 500],
        ]);
    }

    public function test_customer_report_exports_the_whole_list_for_each_date_range(): void
    {
        $this->customerSales();

        $this->get(route('admin.reports.customers'))
            ->assertOk()
            ->assertSee('Export PDF')
            ->assertSee('reports/customers/pdf?from=2025-01-01&amp;to=2025-12-31', false);

        $download = $this->get(route('admin.reports.customers.pdf', ['from' => '2026-11-01', 'to' => '2026-11-15']))
            ->assertOk()
            ->assertDownload('customers-2026-11-01-to-2026-11-15.pdf');
        $this->assertStringStartsWith('%PDF', $download->getContent());

        $html = $this->pdfHtml('admin.reports.business.customers-pdf', ['from' => '2026-11-01', 'to' => '2026-11-15']);

        $this->assertLessThan(strpos($html, 'Sok'), strpos($html, 'Dara'));
        $this->assertStringContainsString('$500.00', $html);
        $this->assertStringContainsString('$100.00', $html);
        $this->assertStringNotContainsString('$140.00', $html);
    }

    public function test_each_customer_can_be_exported_on_their_own(): void
    {
        $this->customerSales();
        $query = ['phone' => '011 22', 'from' => '2026-11-01', 'to' => '2026-11-15'];

        $this->get(route('admin.reports.customers', ['from' => '2026-11-01', 'to' => '2026-11-15']))
            ->assertOk()
            ->assertSee(e(route('admin.reports.customers.pdf', $query)), false);

        $download = $this->get(route('admin.reports.customers.pdf', $query))
            ->assertOk()
            ->assertDownload('customer-011-22-2026-11-01-to-2026-11-15.pdf');
        $this->assertStringStartsWith('%PDF', $download->getContent());

        $html = $this->pdfHtml('admin.reports.business.customer-pdf', $query);

        foreach (['Sok', '011 22', 'SOK-1', 'Mouse', '$100.00', '1 Nov 2026 to 15 Nov 2026'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        foreach (['SOK-OLD', 'SOK-UNPAID', 'Dara', 'Monitor'] as $text) {
            $this->assertStringNotContainsString($text, $html);
        }
    }

    public function test_supplier_report_is_gone_from_reports(): void
    {
        $this->get('/admin/reports/suppliers')->assertNotFound();

        $this->get(route('admin.reports.profit-loss'))
            ->assertOk()
            ->assertDontSee('/admin/reports/suppliers', false);
    }

    public function test_reports_leave_out_sample_records(): void
    {
        Expense::create(['title' => '[DEMO] Shop rent', 'category' => 'Rent', 'amount' => 120, 'spent_on' => '2026-11-01']);
        Expense::create(['title' => 'Real rent', 'category' => 'Rent', 'amount' => 30, 'spent_on' => '2026-11-01']);

        $this->get(route('admin.reports.profit-loss'))->assertViewHas('expenseTotal', 30.0);
    }

    public function test_reports_offer_quick_date_ranges(): void
    {
        foreach (['profit-loss', 'products', 'customers'] as $report) {
            $this->get(route('admin.reports.'.$report))
                ->assertOk()
                ->assertSee('Last month')
                ->assertSee('from=2026-10-01&amp;to=2026-10-31', false);
        }
    }

    public function test_reports_render_with_no_data(): void
    {
        foreach (['profit-loss', 'products', 'customers'] as $report) {
            $this->get(route('admin.reports.'.$report))->assertOk();
        }
    }
}
