<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    {{-- dompdf: table layout and DejaVu Sans only (no flex/grid, and DejaVu has the "$" glyphs). --}}
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .topbar { height: 10px; background: #0057a8; }
        .topbar-accent { height: 3px; background: #f6b300; }
        .page { padding: 26px 44px 0; }

        .company-name { font-size: 18px; font-weight: bold; color: #063a74; margin: 0 0 3px; }
        .company-line { color: #6b7280; font-size: 10px; line-height: 1.6; }
        .doc-title { font-size: 24px; font-weight: bold; color: #0057a8; letter-spacing: 2px; text-align: right; margin: 0 0 6px; }
        .doc-meta { text-align: right; line-height: 1.7; color: #4b5563; }
        .doc-meta strong { color: #1f2937; }
        .badge { display: inline-block; margin-top: 6px; padding: 3px 14px; background: #ecfdf3; border: 1px solid #087443;
                 color: #087443; font-weight: bold; letter-spacing: 2px; font-size: 11px; }

        .rule { border-top: 1px solid #d1d5db; margin: 20px 0; }

        .boxes td { width: 50%; padding: 0; }
        .boxes td.box { padding: 12px 14px; }
        .box { background: #f5f8fc; border-left: 3px solid #0057a8; line-height: 1.65; }
        .box-gap { width: 16px !important; }
        .label { font-size: 9px; font-weight: bold; color: #0057a8; text-transform: uppercase; letter-spacing: 1.4px; margin-bottom: 4px; }
        .box .name { font-size: 12px; font-weight: bold; color: #111827; }
        .kv td { padding: 1px 0; }
        .kv td:first-child { color: #6b7280; width: 42%; }

        .items { margin-top: 22px; }
        .items th { background: #063a74; color: #fff; text-align: left; padding: 9px 10px; font-size: 10px;
                    text-transform: uppercase; letter-spacing: 1px; }
        .items td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .items tr.alt td { background: #f9fafb; }
        .items .num { text-align: right; white-space: nowrap; }
        .items .idx { width: 28px; color: #9ca3af; }
        .sku { color: #6b7280; font-size: 9px; margin-top: 2px; }

        .summary td { padding: 0; }
        .totals { margin-top: 14px; }
        .totals td { padding: 5px 10px; }
        .totals .val { text-align: right; white-space: nowrap; }
        .totals .grand td { background: #0057a8; color: #fff; font-size: 14px; font-weight: bold; padding: 10px; }
        .note { margin-top: 14px; padding-right: 20px; color: #4b5563; line-height: 1.7; font-size: 10px; }
        .note strong { color: #087443; }

        .footer { position: fixed; left: 0; right: 0; bottom: 0; }
        .footer-inner { padding: 12px 44px 14px; border-top: 1px solid #d1d5db; text-align: center; color: #6b7280; font-size: 9px; line-height: 1.7; }
        .footer-inner strong { color: #063a74; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $address = $order->shipping_address ?? [];
        $addressLines = array_filter([
            $address['address_line_1'] ?? null,
            $address['address_line_2'] ?? null,
            trim(($address['city'] ?? '').(! empty($address['province']) ? ', '.$address['province'] : ''), ', '),
            $address['country'] ?? null,
        ]);
        $paidAt = $order->payment_confirmed_at ?? $order->updated_at;
        $method = $order->payment_method === 'bakong'
            ? 'KHQR (Bakong)'
            : ucfirst(str_replace('_', ' ', (string) $order->payment_method));
    @endphp

    <div class="topbar"></div>
    <div class="topbar-accent"></div>

    <div class="footer">
        <div class="footer-inner">
            <strong>Thank you for shopping with CEC Electronic!</strong><br>
            This is a computer-generated receipt and does not require a signature.<br>
            Hotline 012 220 152 / 093 456 747 &nbsp;&middot;&nbsp; Phnom Penh, Cambodia
        </div>
    </div>

    <div class="page">
        <table>
            <tr>
                <td style="width:56%">
                    <table>
                        <tr>
                            <td style="width:92px">
                                <img src="{{ public_path('images/receipt-logo.png') }}" alt="CEC" style="width:80px;height:40px">
                            </td>
                            <td>
                                <div class="company-name">CEC Electronic</div>
                                <div class="company-line">
                                    Computer, laptop &amp; IT store<br>
                                    Phnom Penh, Cambodia<br>
                                    012 220 152 / 093 456 747
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:44%">
                    <div class="doc-title">RECEIPT</div>
                    <div class="doc-meta">
                        Receipt no. <strong>{{ $order->order_number }}</strong><br>
                        Date paid <strong>{{ $paidAt->format('M d, Y') }}</strong><br>
                        <span class="badge">PAID</span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="rule"></div>

        <table class="boxes">
            <tr>
                <td class="box">
                    <div>
                        <div class="label">Billed to</div>
                        <div class="name">{{ $order->customer_name }}</div>
                        @if($order->customer_phone){{ $order->customer_phone }}<br>@endif
                        @if($order->customer_email){{ $order->customer_email }}<br>@endif
                        @foreach($addressLines as $line){{ $line }}<br>@endforeach
                    </div>
                </td>
                <td class="box-gap"></td>
                <td class="box">
                    <div>
                        <div class="label">Payment details</div>
                        <table class="kv">
                            <tr><td>Method</td><td>{{ $method }}</td></tr>
                            <tr><td>Paid on</td><td>{{ $paidAt->format('M d, Y h:i A') }}</td></tr>
                            <tr><td>Status</td><td><strong style="color:#087443">Paid in full</strong></td></tr>
                            @if($order->deliveryProvider?->name || $order->shipping_method)
                                <tr><td>Delivery</td><td>{{ $order->deliveryProvider?->name ?: ucfirst((string) $order->shipping_method) }}</td></tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="idx">#</th>
                    <th>Description</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td class="idx">{{ $loop->iteration }}</td>
                        <td>
                            {{ $item->product_name }}
                            @if($item->sku)<div class="sku">SKU: {{ $item->sku }}</div>@endif
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">${{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td style="width:54%">
                    <div class="note">
                        <strong>Payment received.</strong> This receipt confirms that your payment for order
                        {{ $order->order_number }} was received and verified. Please keep it for your records.
                    </div>
                </td>
                <td style="width:46%">
                    <table class="totals">
                        <tr><td>Subtotal</td><td class="val">${{ number_format($order->subtotal, 2) }}</td></tr>
                        <tr><td>Delivery fee</td><td class="val">${{ number_format($order->shipping_total, 2) }}</td></tr>
                        @if((float) $order->discount_total > 0)
                            <tr><td>Discount</td><td class="val">-${{ number_format($order->discount_total, 2) }}</td></tr>
                        @endif
                        <tr class="grand"><td>Total paid</td><td class="val">${{ number_format($order->grand_total, 2) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
