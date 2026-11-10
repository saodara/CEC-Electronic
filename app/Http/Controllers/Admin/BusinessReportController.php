<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BusinessReportController extends Controller
{
    /**
     * Cost per item sold: the cost saved at checkout, or for older orders the product's current cost.
     */
    private const ITEM_COST_SQL = 'order_items.quantity * COALESCE(order_items.unit_cost, products.cost_price, 0)';

    public function profitLoss(Request $request): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.reports.business.profit-loss', $this->profitLossData($from, $to));
    }

    public function profitLossPdf(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return Pdf::loadView('admin.reports.business.profit-loss-pdf', [
            ...$this->profitLossData($from, $to),
            'generatedAt' => CarbonImmutable::now(config('app.shop_timezone')),
        ])->setPaper('a4')->download('profit-loss-'.$from->toDateString().'-to-'.$to->toDateString().'.pdf');
    }

    private function profitLossData(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sales = $this->paidOrders($from, $to)
            ->selectRaw('COALESCE(SUM(subtotal - discount_total), 0) as revenue, COALESCE(SUM(shipping_total), 0) as shipping')
            ->toBase()
            ->first();

        $items = $this->soldItems($from, $to)
            ->selectRaw('COALESCE(SUM('.self::ITEM_COST_SQL.'), 0) as cost')
            ->selectRaw('COALESCE(SUM(CASE WHEN order_items.unit_cost IS NULL AND products.cost_price IS NULL THEN 1 ELSE 0 END), 0) as missing_cost')
            ->toBase()
            ->first();

        $expenses = $this->onDates(Expense::query(), 'spent_on', $from, $to)
            ->where('title', 'not like', '[DEMO]%')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->toBase()
            ->get();

        $revenue = (float) $sales->revenue;
        $shipping = (float) $sales->shipping;
        $cost = (float) $items->cost;
        $expenseTotal = (float) $expenses->sum('total');
        $grossProfit = $revenue + $shipping - $cost;

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'shipping' => $shipping,
            'cost' => $cost,
            'grossProfit' => $grossProfit,
            'expenses' => $expenses,
            'expenseTotal' => $expenseTotal,
            'netProfit' => $grossProfit - $expenseTotal,
            'missingCost' => (int) $items->missing_cost,
            'soldProducts' => $this->productRows($from, $to)->get(),
        ];
    }

    public function products(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $rows = $this->productRows($from, $to)->paginate(20)->withQueryString();

        $totals = $this->soldItems($from, $to)
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity_sold, COALESCE(SUM(order_items.line_total), 0) as revenue')
            ->toBase()
            ->first();

        return view('admin.reports.business.products', compact('from', 'to', 'rows', 'totals'));
    }

    public function customers(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $rows = $this->customerRows($from, $to)->paginate(20)->withQueryString();
        $totals = $this->customerTotals($from, $to);

        return view('admin.reports.business.customers', compact('from', 'to', 'rows', 'totals'));
    }

    /**
     * The whole customer list, or one customer's orders when a phone number is given.
     */
    public function customersPdf(Request $request): Response
    {
        [$from, $to] = $this->range($request);
        $phone = $request->query('phone');
        $period = $from->toDateString().'-to-'.$to->toDateString();
        $generatedAt = CarbonImmutable::now(config('app.shop_timezone'));
        $timezone = config('app.shop_timezone');

        if (is_string($phone) && $phone !== '') {
            $orders = $this->paidOrders($from, $to)->where('customer_phone', $phone)->with('items')->latest()->get();

            return Pdf::loadView('admin.reports.business.customer-pdf', compact('from', 'to', 'phone', 'orders', 'generatedAt', 'timezone'))
                ->setPaper('a4')
                ->download('customer-'.(Str::slug($phone) ?: 'unknown').'-'.$period.'.pdf');
        }

        return Pdf::loadView('admin.reports.business.customers-pdf', [
            'from' => $from,
            'to' => $to,
            'rows' => $this->customerRows($from, $to)->get(),
            'totals' => $this->customerTotals($from, $to),
            'generatedAt' => $generatedAt,
            'timezone' => $timezone,
        ])->setPaper('a4')->download('customers-'.$period.'.pdf');
    }

    private function customerRows(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $this->paidOrders($from, $to)
            ->select('customer_phone')
            ->selectRaw('MAX(customer_name) as customer_name, MAX(customer_email) as customer_email')
            ->selectRaw('COUNT(*) as orders_count, SUM(grand_total) as total_spent, MAX(created_at) as last_order_at')
            ->groupBy('customer_phone')
            ->orderByDesc('total_spent');
    }

    private function customerTotals(CarbonImmutable $from, CarbonImmutable $to): object
    {
        return $this->paidOrders($from, $to)
            ->selectRaw('COUNT(DISTINCT customer_phone) as customers, COUNT(*) as orders_count, COALESCE(SUM(grand_total), 0) as total_spent')
            ->toBase()
            ->first();
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable} first and last day to include, in the shop's timezone
     */
    private function range(Request $request): array
    {
        $timezone = config('app.shop_timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $from = $this->date($request->query('from'), $timezone) ?? $today->startOfMonth();
        $to = $this->date($request->query('to'), $timezone) ?? $today;

        return $from->lte($to) ? [$from, $to] : [$to, $from];
    }

    private function date(mixed $value, string $timezone): ?CarbonImmutable
    {
        if (Validator::make(['date' => $value], ['date' => ['required', 'date_format:Y-m-d']])->fails()) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value, $timezone)->startOfDay();
    }

    private function onDates($query, string $column, CarbonImmutable $from, CarbonImmutable $to)
    {
        return $query
            ->whereDate($column, '>=', $from->toDateString())
            ->whereDate($column, '<=', $to->toDateString());
    }

    /**
     * Sales count once paid, unless cancelled, the same rule as the order reports.
     */
    private function paidOrders(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return Order::query()
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $from->utc())
            ->where('created_at', '<', $to->addDay()->utc());
    }

    /**
     * One row per product sold, best sellers first.
     */
    private function productRows(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $this->soldItems($from, $to)
            ->select('order_items.product_id', 'order_items.product_name')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->selectRaw('SUM('.self::ITEM_COST_SQL.') as cost')
            ->selectRaw('SUM(CASE WHEN order_items.unit_cost IS NULL AND products.cost_price IS NULL THEN 1 ELSE 0 END) as missing_cost')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('revenue')
            ->orderBy('order_items.product_name');
    }

    private function soldItems(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', $from->utc())
            ->where('orders.created_at', '<', $to->addDay()->utc());
    }
}
