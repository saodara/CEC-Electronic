<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Order::query()
            ->select([
                'customer_phone',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('MAX(customer_email) as customer_email'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(grand_total) as total_spent'),
                DB::raw('MAX(created_at) as last_order_at'),
            ])
            ->groupBy('customer_phone')
            ->orderByDesc('last_order_at')
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(string $phone): View
    {
        $orders = Order::query()
            ->where('customer_phone', $phone)
            ->withCount('items')
            ->latest()
            ->get();

        abort_if($orders->isEmpty(), 404);

        $customer = $orders->first();
        $stats = [
            'orders' => $orders->count(),
            'spent' => $orders->sum('grand_total'),
            'unpaid' => $orders->where('payment_status', '!=', 'paid')->count(),
            'latest' => $orders->max('created_at'),
        ];

        return view('admin.customers.show', compact('customer', 'orders', 'stats'));
    }
}
