<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * dompdf needs about 100 MB for this many table rows, and PHP's default memory limit is 128 MB.
     */
    private const PDF_ORDER_LIMIT = 200;

    public function orders(Request $request, string $period): View
    {
        $today = $this->today();
        $selected = $this->selectedDate($request, $today);
        [$rangeStart, $rangeEnd, $previousStart] = $this->range($period, $selected);
        $periodOrders = $this->reportOrders($period, $rangeStart, $rangeEnd);

        $orders = (clone $periodOrders)
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.reports.orders', [
            'period' => $period,
            'paidOnly' => $this->paidOnly($period),
            'yearOptions' => $period === 'year' ? $this->yearOptions($selected, $today) : [],
            'selectedDate' => $selected->toDateString(),
            'today' => $today->toDateString(),
            'periodLabel' => $this->periodLabel($period, $rangeStart, $rangeEnd),
            'previousDate' => $previousStart->toDateString(),
            'nextDate' => $rangeEnd->lte($today) ? $rangeEnd->toDateString() : null,
            'orders' => $orders,
            'timezone' => config('app.shop_timezone'),
            ...$this->totals($periodOrders),
        ]);
    }

    public function ordersPdf(Request $request, string $period): Response
    {
        $selected = $this->selectedDate($request, $this->today());
        [$rangeStart, $rangeEnd] = $this->range($period, $selected);
        $periodOrders = $this->reportOrders($period, $rangeStart, $rangeEnd);

        $pdf = Pdf::loadView('admin.reports.orders-pdf', [
            'period' => $period,
            'paidOnly' => $this->paidOnly($period),
            'periodLabel' => $this->periodLabel($period, $rangeStart, $rangeEnd),
            'orders' => (clone $periodOrders)->withCount('items')->latest()->take(self::PDF_ORDER_LIMIT)->get(),
            'timezone' => config('app.shop_timezone'),
            'generatedAt' => CarbonImmutable::now(config('app.shop_timezone')),
            ...$this->totals($periodOrders),
        ])->setPaper('a4');

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $dompdf->getCanvas()->page_text(
            500, 810, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.42, 0.45, 0.5]
        );

        return $pdf->download('orders-report-'.$period.'-'.$rangeStart->toDateString().'.pdf');
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.shop_timezone'))->startOfDay();
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable, CarbonImmutable} start, end (exclusive), start of the previous period
     */
    private function range(string $period, CarbonImmutable $selected): array
    {
        return match ($period) {
            'day' => [$selected, $selected->addDay(), $selected->subDay()],
            'week' => [
                $selected->startOfWeek(CarbonInterface::MONDAY),
                $selected->startOfWeek(CarbonInterface::MONDAY)->addWeek(),
                $selected->startOfWeek(CarbonInterface::MONDAY)->subWeek(),
            ],
            'month' => [$selected->startOfMonth(), $selected->startOfMonth()->addMonth(), $selected->startOfMonth()->subMonth()],
            'year' => [$selected->startOfYear(), $selected->startOfYear()->addYear(), $selected->startOfYear()->subYear()],
        };
    }

    /**
     * Daily reports show every order; the longer periods only count paid ones.
     */
    private function paidOnly(string $period): bool
    {
        return $period !== 'day';
    }

    private function reportOrders(string $period, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Order::query()
            ->where('created_at', '>=', $start->utc())
            ->where('created_at', '<', $end->utc())
            ->when($this->paidOnly($period), fn (Builder $query) => $query->where('payment_status', 'paid'));
    }

    /**
     * @return array{summary: array<string, int|float>, byStatus: Collection, byPayment: Collection}
     */
    private function totals(Builder $periodOrders): array
    {
        $groups = (clone $periodOrders)
            ->select([
                'status',
                'payment_status',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(grand_total) as total'),
            ])
            ->groupBy('status', 'payment_status')
            ->toBase()
            ->get();

        $active = $groups->where('status', '!=', 'cancelled');

        return [
            'summary' => [
                'orders' => (int) $groups->sum('orders_count'),
                'revenue' => (float) $active->where('payment_status', 'paid')->sum('total'),
                'awaiting_payment' => (float) $active->where('payment_status', 'unpaid')->sum('total'),
                'cancelled' => (int) $groups->where('status', 'cancelled')->sum('orders_count'),
            ],
            'byStatus' => $groups->groupBy('status')->map(fn ($rows) => (int) $rows->sum('orders_count'))->sortDesc(),
            'byPayment' => $groups->groupBy('payment_status')->map(fn ($rows) => (int) $rows->sum('orders_count'))->sortDesc(),
        ];
    }

    private function selectedDate(Request $request, CarbonImmutable $today): CarbonImmutable
    {
        $date = $request->query('date');

        if (Validator::make(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->fails()) {
            return $today;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $date, config('app.shop_timezone'))->startOfDay();
    }

    /**
     * Years to choose from, newest first: back to the first order, and always including the selected year.
     *
     * @return list<int>
     */
    private function yearOptions(CarbonImmutable $selected, CarbonImmutable $today): array
    {
        $firstOrderAt = Order::query()->min('created_at');
        $firstYear = $firstOrderAt
            ? CarbonImmutable::parse($firstOrderAt, 'UTC')->setTimezone(config('app.shop_timezone'))->year
            : $today->year;

        return range(max($today->year, $selected->year), min($firstYear, $selected->year));
    }

    private function periodLabel(string $period, CarbonImmutable $start, CarbonImmutable $end): string
    {
        return match ($period) {
            'day' => $start->format('l, j F Y'),
            'week' => $start->format('j M').' - '.$end->subDay()->format('j M Y'),
            'month' => $start->format('F Y'),
            'year' => $start->format('Y'),
        };
    }
}
