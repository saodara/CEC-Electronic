<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $admins = User::query()->where('is_admin', true)->get(['id', 'email', 'phone']);

        $orderGroups = $this->customerOrders($admins)
            ->select([
                'customer_phone',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('MAX(customer_email) as customer_email'),
                DB::raw('MAX(user_id) as user_id'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(grand_total) as total_spent'),
                DB::raw('MAX(created_at) as last_order_at'),
            ])
            ->groupBy('customer_phone')
            ->get()
            ->map(fn ($group) => (object) [
                'customer_name' => $group->customer_name,
                'customer_phone' => $group->customer_phone,
                'customer_email' => $group->customer_email,
                'orders_count' => (int) $group->orders_count,
                'total_spent' => (float) $group->total_spent,
                'last_order_at' => $group->last_order_at ? Carbon::parse($group->last_order_at) : null,
                'joined_at' => null,
                'registered' => $group->user_id !== null,
                'user_id' => $group->user_id,
            ]);

        // Registered customers who have not ordered yet still belong on the list.
        $customers = $orderGroups;
        User::query()->where('is_admin', false)->get(['id', 'name', 'email', 'phone', 'created_at'])
            ->each(function (User $user) use (&$customers) {
                $match = $customers->first(fn ($row) => (int) $row->user_id === $user->id
                    || ($user->phone && $row->customer_phone === $user->phone));

                if ($match) {
                    $match->registered = true;
                    $match->joined_at = $user->created_at;

                    return;
                }

                $customers->push((object) [
                    'customer_name' => $user->name,
                    'customer_phone' => $user->phone,
                    'customer_email' => $user->email,
                    'orders_count' => 0,
                    'total_spent' => 0.0,
                    'last_order_at' => null,
                    'joined_at' => $user->created_at,
                    'registered' => true,
                    'user_id' => $user->id,
                ]);
            });

        $customers = $customers
            ->sortByDesc(fn ($row) => ($row->last_order_at ?? $row->joined_at)?->getTimestamp() ?? 0)
            ->values();

        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $customers = new LengthAwarePaginator(
            $customers->forPage($page, $perPage)->values(),
            $customers->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.customers.index', compact('customers'));
    }

    public function show(string $phone): View
    {
        $admins = User::query()->where('is_admin', true)->get(['id', 'email', 'phone']);

        $orders = $this->customerOrders($admins)
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

    /**
     * Orders placed by customers, leaving out anything placed by an admin
     * account or with an admin's phone number or email.
     */
    private function customerOrders(Collection $admins): Builder
    {
        $adminPhones = $admins->pluck('phone')->filter()->values()->all();
        $adminEmails = $admins->pluck('email')->filter()->map(fn ($email) => strtolower($email))->values()->all();

        return Order::query()
            ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhereNotIn('user_id', $admins->pluck('id')->all()))
            ->when($adminPhones, fn (Builder $query) => $query->whereNotIn('customer_phone', $adminPhones))
            ->when($adminEmails, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->whereNull('customer_email')
                ->orWhereNotIn(DB::raw('LOWER(customer_email)'), $adminEmails)));
    }
}
