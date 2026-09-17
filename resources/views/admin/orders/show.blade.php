@extends('admin.layout')

@section('title', $order->order_number.' - CEC Electronic Admin')
@section('heading', 'Order '.$order->order_number)

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">{{ $order->order_number }}</h2>
            <p class="muted" style="margin:6px 0 0">Placed {{ $order->placed_at?->format('M d, Y h:i A') ?? $order->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.orders.index') }}">Back to orders</a>
    </div>

    <section style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px">
        <div class="panel" style="padding:18px">
            <h3 style="margin-top:0">Items</h3>
            @foreach($order->items as $item)
                <div style="display:grid;grid-template-columns:1fr 80px 100px;gap:12px;border-bottom:1px solid var(--line);padding:12px 0">
                    <strong>{{ $item->product_name }}</strong>
                    <span class="muted">x {{ $item->quantity }}</span>
                    <strong style="text-align:right">${{ number_format($item->line_total, 2) }}</strong>
                </div>
            @endforeach
        </div>

        <aside class="panel" style="padding:18px">
            <h3 style="margin-top:0">Customer</h3>
            <p class="muted" style="line-height:1.7">{{ $order->customer_name }}<br>{{ $order->customer_phone }}<br>{{ $order->customer_email }}</p>

            @if($order->payment_confirmed_at)
                <div class="payment-verified-banner">
                    <span class="icon">&#10003;</span>
                    <span>
                        <strong>Payment verified</strong>
                        <span>Via {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }} &middot; {{ $order->payment_confirmed_at->format('M d, Y h:i A') }}</span>
                    </span>
                </div>
            @elseif($order->payment_method === 'bakong' && $order->bakong_qr_md5)
                @if(session('status'))
                    <div class="muted" style="margin-bottom:10px">{{ session('status') }}</div>
                @endif
                <form action="{{ route('admin.orders.verify-payment', $order) }}" method="post" style="margin-bottom:14px">
                    @csrf
                    <button class="btn secondary" style="width:100%" type="submit">Verify with Bakong now</button>
                </form>
            @endif

            <form action="{{ route('admin.orders.update', $order) }}" method="post">
                @csrf
                @method('PUT')
                <label>Status
                    <select name="status">
                        @foreach(['pending','processing','shipped','completed','cancelled'] as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:block;margin-top:12px">Payment
                    <select name="payment_status">
                        @foreach(['unpaid','paid','refunded','failed'] as $status)
                            <option value="{{ $status }}" @selected($order->payment_status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:block;margin-top:12px">Delivery zone
                    <select name="delivery_zone_id">
                        <option value="">Unassigned</option>
                        @foreach($deliveryZones as $zone)
                            <option value="{{ $zone->id }}" @selected($order->delivery_zone_id === $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:block;margin-top:12px">Delivery provider
                    <select name="delivery_provider_id">
                        <option value="">Unassigned</option>
                        @foreach($deliveryProviders as $provider)
                            <option value="{{ $provider->id }}" @selected($order->delivery_provider_id === $provider->id)>{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:block;margin-top:12px">Tracking number
                    <input name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}">
                </label>
                <label style="display:block;margin-top:12px">Shipped at
                    <input name="shipped_at" type="datetime-local" value="{{ old('shipped_at', $order->shipped_at?->format('Y-m-d\\TH:i')) }}">
                </label>
                <label style="display:block;margin-top:12px">Delivered at
                    <input name="delivered_at" type="datetime-local" value="{{ old('delivered_at', $order->delivered_at?->format('Y-m-d\\TH:i')) }}">
                </label>
                <button class="btn" style="width:100%;margin-top:14px" type="submit">Update order</button>
            </form>

            <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
            <div style="display:flex;justify-content:space-between;font-size:16px"><span>Total</span><strong style="color:var(--brand)">${{ number_format($order->grand_total, 2) }}</strong></div>
            <div class="muted" style="margin-top:12px;line-height:1.7">
                Zone: {{ $order->deliveryZone?->name ?: 'Unassigned' }}<br>
                Provider: {{ $order->deliveryProvider?->name ?: 'Unassigned' }}
            </div>
        </aside>
    </section>
@endsection
