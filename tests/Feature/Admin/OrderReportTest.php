<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class OrderReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-15 12:00:00', 'UTC'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.reports.orders', ['period' => 'month']))
            ->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.reports.orders', ['period' => 'month']))
            ->assertRedirect(route('admin.login'));
    }

    public function test_daily_report_shows_all_payment_statuses_and_only_paid_active_orders_as_revenue(): void
    {
        $this->actingAsAdmin();

        $this->order('completed', 'paid', 100);
        $this->order('processing', 'paid', 50.5);
        $this->order('pending', 'unpaid', 30);
        $this->order('completed', 'refunded', 40);
        $this->order('cancelled', 'paid', 200);
        $this->order('cancelled', 'unpaid', 300);
        $this->order('completed', 'paid', 999, '2026-11-09 12:00:00');

        $this->get(route('admin.reports.orders', ['period' => 'day', 'date' => '2026-11-10']))
            ->assertOk()
            ->assertViewIs('admin.reports.orders')
            ->assertViewHas('summary', fn (array $summary) => $summary === [
                'orders' => 6,
                'revenue' => 150.5,
                'awaiting_payment' => 30.0,
                'cancelled' => 2,
            ])
            ->assertViewHas('byStatus', fn ($byStatus) => $byStatus['completed'] === 2
                && $byStatus['cancelled'] === 2
                && $byStatus['pending'] === 1)
            ->assertViewHas('byPayment', fn ($byPayment) => $byPayment['paid'] === 3
                && $byPayment['unpaid'] === 2
                && $byPayment['refunded'] === 1)
            ->assertSee('10 November 2026')
            ->assertSee('Awaiting payment')
            ->assertSee('By payment status');
    }

    public function test_weekly_monthly_and_yearly_reports_only_include_paid_orders(): void
    {
        $this->actingAsAdmin();

        $paid = $this->order('completed', 'paid', 100);
        $paidCancelled = $this->order('cancelled', 'paid', 200);
        $hidden = [
            $this->order('pending', 'unpaid', 30),
            $this->order('completed', 'refunded', 40),
            $this->order('pending', 'failed', 50),
            $this->order('cancelled', 'unpaid', 60),
        ];

        foreach (['week', 'month', 'year'] as $period) {
            $parameters = ['period' => $period, 'date' => '2026-11-10'];

            $this->get(route('admin.reports.orders', $parameters))
                ->assertOk()
                ->assertViewHas('summary', fn (array $summary) => $summary === [
                    'orders' => 2,
                    'revenue' => 100.0,
                    'awaiting_payment' => 0.0,
                    'cancelled' => 1,
                ])
                ->assertViewHas('orders', fn ($orders) => $this->listed($orders, [$paid, $paidCancelled], $hidden))
                ->assertSee('Paid orders')
                ->assertDontSee('Awaiting payment')
                ->assertDontSee('By payment status');

            $pdf = $this->pdfHtml($parameters);

            $this->assertStringContainsString('Paid orders only', $pdf);
            $this->assertStringContainsString($paid->order_number, $pdf);
            $this->assertStringNotContainsString('Awaiting payment', $pdf);
            $this->assertStringNotContainsString('By payment status', $pdf);

            foreach ($hidden as $order) {
                $this->assertStringNotContainsString($order->order_number, $pdf);
            }
        }
    }

    public function test_daily_pdf_keeps_every_payment_status(): void
    {
        $this->actingAsAdmin();

        $unpaid = $this->order('pending', 'unpaid', 30);

        $pdf = $this->pdfHtml(['period' => 'day', 'date' => '2026-11-10']);

        $this->assertStringContainsString($unpaid->order_number, $pdf);
        $this->assertStringContainsString('Awaiting payment', $pdf);
        $this->assertStringNotContainsString('Paid orders only', $pdf);
    }

    public function test_month_boundaries_follow_phnom_penh_time(): void
    {
        $this->actingAsAdmin();

        $lastOctober = $this->order('completed', 'paid', 10, '2026-10-31 16:59:59');
        $firstNovember = $this->order('completed', 'paid', 20, '2026-10-31 17:00:00');
        $lastNovember = $this->order('completed', 'paid', 30, '2026-11-30 16:59:59');
        $firstDecember = $this->order('completed', 'paid', 40, '2026-11-30 17:00:00');

        $this->get(route('admin.reports.orders', ['period' => 'month']))
            ->assertOk()
            ->assertViewHas('orders', function ($orders) use ($lastOctober, $firstNovember, $lastNovember, $firstDecember) {
                $ids = collect($orders->items())->pluck('id');

                return $ids->contains($firstNovember->id)
                    && $ids->contains($lastNovember->id)
                    && ! $ids->contains($lastOctober->id)
                    && ! $ids->contains($firstDecember->id);
            });
    }

    public function test_totals_cover_the_whole_month_when_the_list_is_paginated(): void
    {
        $this->actingAsAdmin();

        foreach (range(1, 12) as $i) {
            $this->order('completed', 'paid', 10);
        }

        $this->get(route('admin.reports.orders', ['period' => 'month', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 2)
            ->assertViewHas('summary', fn (array $summary) => $summary['orders'] === 12
                && $summary['revenue'] === 120.0);
    }

    public function test_empty_month_renders(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.reports.orders', ['period' => 'month']))
            ->assertOk()
            ->assertSee('No orders in this period.');
    }

    public function test_week_filter_covers_monday_to_sunday_in_phnom_penh_time(): void
    {
        $this->actingAsAdmin();

        $before = $this->order('completed', 'paid', 1, '2026-11-08 16:59:59');
        $monday = $this->order('completed', 'paid', 2, '2026-11-08 17:00:00');
        $sunday = $this->order('completed', 'paid', 4, '2026-11-15 16:59:59');
        $after = $this->order('completed', 'paid', 8, '2026-11-15 17:00:00');

        $this->get(route('admin.reports.orders', ['period' => 'week', 'date' => '2026-11-11']))
            ->assertOk()
            ->assertSee('9 Nov - 15 Nov 2026')
            ->assertViewHas('summary', fn (array $summary) => $summary['orders'] === 2 && $summary['revenue'] === 6.0)
            ->assertViewHas('orders', fn ($orders) => $this->listed($orders, [$monday, $sunday], [$before, $after]));
    }

    public function test_day_filter_covers_one_phnom_penh_day(): void
    {
        $this->actingAsAdmin();

        $before = $this->order('completed', 'paid', 1, '2026-11-09 16:59:59');
        $start = $this->order('completed', 'paid', 2, '2026-11-09 17:00:00');
        $end = $this->order('completed', 'paid', 4, '2026-11-10 16:59:59');
        $after = $this->order('completed', 'paid', 8, '2026-11-10 17:00:00');

        $this->get(route('admin.reports.orders', ['period' => 'day', 'date' => '2026-11-10']))
            ->assertOk()
            ->assertSee('Tuesday, 10 November 2026')
            ->assertViewHas('summary', fn (array $summary) => $summary['orders'] === 2 && $summary['revenue'] === 6.0)
            ->assertViewHas('orders', fn ($orders) => $this->listed($orders, [$start, $end], [$before, $after]));
    }

    public function test_month_filter_can_show_an_earlier_month(): void
    {
        $this->actingAsAdmin();

        $october = $this->order('completed', 'paid', 5, '2026-10-20 12:00:00');
        $november = $this->order('completed', 'paid', 7, '2026-11-10 12:00:00');

        $this->get(route('admin.reports.orders', ['period' => 'month', 'date' => '2026-10-05']))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertViewHas('orders', fn ($orders) => $this->listed($orders, [$october], [$november]));
    }

    public function test_previous_and_next_links_move_by_the_selected_period(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.reports.orders', ['period' => 'week', 'date' => '2026-11-04']))
            ->assertViewHas('previousDate', '2026-10-26')
            ->assertViewHas('nextDate', '2026-11-09');

        $this->get(route('admin.reports.orders', ['period' => 'month', 'date' => '2026-01-31']))
            ->assertViewHas('previousDate', '2025-12-01')
            ->assertViewHas('nextDate', '2026-02-01');
    }

    public function test_next_link_is_hidden_for_the_current_period(): void
    {
        $this->actingAsAdmin();

        foreach (['day', 'week', 'month', 'year'] as $period) {
            $this->get(route('admin.reports.orders', ['period' => $period]))
                ->assertOk()
                ->assertViewHas('nextDate', null);
        }
    }

    public function test_invalid_dates_fall_back_to_today(): void
    {
        $this->actingAsAdmin();

        foreach (['nope', '2026-02-31', ['x']] as $date) {
            $this->get(route('admin.reports.orders', ['period' => 'week', 'date' => $date]))
                ->assertOk()
                ->assertViewHas('period', 'week')
                ->assertViewHas('selectedDate', '2026-11-15');
        }
    }

    public function test_unknown_report_type_is_not_found(): void
    {
        $this->actingAsAdmin();

        $this->get('/admin/reports/decade')->assertNotFound();
    }

    public function test_year_report_covers_one_phnom_penh_year(): void
    {
        $this->actingAsAdmin();

        $before = $this->order('completed', 'paid', 1, '2024-12-31 16:59:59');
        $start = $this->order('completed', 'paid', 2, '2024-12-31 17:00:00');
        $end = $this->order('completed', 'paid', 4, '2025-12-31 16:59:59');
        $after = $this->order('completed', 'paid', 8, '2025-12-31 17:00:00');

        $this->get(route('admin.reports.orders', ['period' => 'year', 'date' => '2025-01-01']))
            ->assertOk()
            ->assertSee('Yearly orders: 2025')
            ->assertViewHas('summary', fn (array $summary) => $summary['orders'] === 2 && $summary['revenue'] === 6.0)
            ->assertViewHas('orders', fn ($orders) => $this->listed($orders, [$start, $end], [$before, $after]))
            ->assertViewHas('previousDate', '2024-01-01')
            ->assertViewHas('nextDate', '2026-01-01');
    }

    public function test_year_list_runs_from_this_year_back_to_the_first_order(): void
    {
        $this->actingAsAdmin();

        $this->order('completed', 'paid', 1, '2024-06-01 12:00:00');

        $this->get(route('admin.reports.orders', ['period' => 'year']))
            ->assertOk()
            ->assertViewHas('yearOptions', [2026, 2025, 2024]);

        $this->get(route('admin.reports.orders', ['period' => 'year', 'date' => '2021-01-01']))
            ->assertOk()
            ->assertViewHas('yearOptions', [2026, 2025, 2024, 2023, 2022, 2021]);
    }

    public function test_pagination_links_keep_the_filter(): void
    {
        $this->actingAsAdmin();

        foreach (range(1, 12) as $i) {
            $this->order('completed', 'paid', 10, '2026-11-10 12:00:00');
        }

        $this->get(route('admin.reports.orders', ['period' => 'day', 'date' => '2026-11-10']))
            ->assertOk()
            ->assertViewHas('orders', function ($orders) {
                $url = $orders->url(2);

                return str_contains($url, '/reports/day') && str_contains($url, 'date=2026-11-10');
            });
    }

    public function test_guest_cannot_export_pdf(): void
    {
        $this->get(route('admin.reports.orders.pdf', ['period' => 'month']))
            ->assertRedirect(route('admin.login'));
    }

    public function test_each_report_downloads_a_pdf_named_after_its_period(): void
    {
        $this->actingAsAdmin();
        $this->order('completed', 'paid', 100);

        $expected = [
            'day' => 'orders-report-day-2026-11-10.pdf',
            'week' => 'orders-report-week-2026-11-09.pdf',
            'month' => 'orders-report-month-2026-11-01.pdf',
            'year' => 'orders-report-year-2026-01-01.pdf',
        ];

        foreach ($expected as $period => $filename) {
            $response = $this->get(route('admin.reports.orders.pdf', ['period' => $period, 'date' => '2026-11-10']))
                ->assertOk()
                ->assertDownload($filename);

            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_pdf_lists_every_order_in_the_period_without_pagination(): void
    {
        $this->actingAsAdmin();

        $orders = collect(range(1, 12))->map(fn () => $this->order('completed', 'paid', 10));
        $outside = $this->order('completed', 'paid', 10, '2026-10-20 12:00:00');

        $text = $this->pdfHtml(['period' => 'month']);

        $orders->each(fn (Order $order) => $this->assertStringContainsString($order->order_number, $text));
        $this->assertStringNotContainsString($outside->order_number, $text);
        $this->assertStringNotContainsString('Listing the latest', $text);
    }

    public function test_pdf_caps_the_list_and_says_so_while_totals_cover_every_order(): void
    {
        $this->actingAsAdmin();

        Order::factory()->count(201)->create([
            'status' => 'completed',
            'payment_status' => 'paid',
            'grand_total' => 10,
            'created_at' => '2026-11-10 12:00:00',
        ]);

        $text = $this->pdfHtml(['period' => 'month']);

        $this->assertStringContainsString('Listing the latest 200 of 201 orders', $text);
        $this->assertStringContainsString('$2,010.00', $text);
    }

    public function test_report_page_links_to_pdf_for_the_selected_period(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.reports.orders', ['period' => 'week', 'date' => '2026-11-04']))
            ->assertOk()
            ->assertSee(route('admin.reports.orders.pdf', ['period' => 'week', 'date' => '2026-11-04']), false);
    }

    public function test_sidebar_has_a_link_for_each_report(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.dashboard'))->assertOk();

        foreach (['day', 'week', 'month', 'year'] as $period) {
            $response->assertSee(route('admin.reports.orders', ['period' => $period]));
        }
    }

    /**
     * The HTML handed to dompdf, since the PDF's own text streams are compressed.
     */
    private function pdfHtml(array $parameters): string
    {
        $data = null;
        View::composer('admin.reports.orders-pdf', function ($view) use (&$data) {
            $data = $view->getData();
        });

        $this->get(route('admin.reports.orders.pdf', $parameters))->assertOk();

        return view('admin.reports.orders-pdf', $data)->render();
    }

    private function listed($orders, array $included, array $excluded): bool
    {
        $ids = collect($orders->items())->pluck('id');

        return collect($included)->every(fn (Order $order) => $ids->contains($order->id))
            && collect($excluded)->every(fn (Order $order) => ! $ids->contains($order->id));
    }

    private function order(string $status, string $paymentStatus, float $total, string $createdAt = '2026-11-10 12:00:00'): Order
    {
        return Order::factory()->create([
            'status' => $status,
            'payment_status' => $paymentStatus,
            'grand_total' => $total,
            'created_at' => $createdAt,
        ]);
    }
}
