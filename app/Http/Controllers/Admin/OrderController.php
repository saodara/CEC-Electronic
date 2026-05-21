<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryProvider;
use App\Models\DeliveryZone;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with(['deliveryProvider', 'deliveryZone'])
            ->withCount('items')
            ->latest()
            ->paginate(15);
        $paymentNotificationsCount = Order::query()
            ->whereNotNull('payment_confirmed_at')
            ->whereNull('admin_payment_seen_at')
            ->count();

        return view('admin.orders.index', compact('orders', 'paymentNotificationsCount'));
    }

    public function show(Order $order): View
    {
        if ($order->payment_confirmed_at && ! $order->admin_payment_seen_at) {
            $order->update(['admin_payment_seen_at' => now()]);
            $order->refresh();
        }

        $order->load(['items', 'deliveryProvider', 'deliveryZone', 'shipments.deliveryProvider']);
        $deliveryProviders = DeliveryProvider::where('is_active', true)->orderBy('name')->get();
        $deliveryZones = DeliveryZone::where('is_active', true)->orderBy('name')->get();

        return view('admin.orders.show', compact('order', 'deliveryProviders', 'deliveryZones'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'payment_status' => ['required', 'string', 'max:50'],
            'delivery_zone_id' => ['nullable', 'exists:delivery_zones,id'],
            'delivery_provider_id' => ['nullable', 'exists:delivery_providers,id'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipped_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        if ($data['payment_status'] === 'paid' && $order->payment_status !== 'paid') {
            $data['payment_confirmed_at'] = now();
            $data['admin_payment_seen_at'] = now();
        }

        $order->update($data);

        if (! empty($data['delivery_provider_id']) || ! empty($data['tracking_number'])) {
            $order->shipments()->updateOrCreate(
                ['tracking_number' => $data['tracking_number'] ?? null],
                [
                    'delivery_provider_id' => $data['delivery_provider_id'] ?? null,
                    'tracking_number' => $data['tracking_number'] ?? null,
                    'status' => $data['delivered_at'] ? 'delivered' : ($data['shipped_at'] ? 'shipped' : 'pending'),
                    'delivery_fee' => $order->shipping_total,
                    'picked_up_at' => $data['shipped_at'] ?? null,
                    'delivered_at' => $data['delivered_at'] ?? null,
                ]
            );
        }

        return back()->with('status', 'Order updated.');
    }
}
