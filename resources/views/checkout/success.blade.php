@extends('shop.layout')

@section('title', 'Order Placed - CEC Electronic')

@section('content')
    @php
        $needsBakongPayment = $order->payment_method === 'bakong' && $order->payment_status !== 'paid';
        $hasCheckoutUrl     = $needsBakongPayment && $order->bakong_checkout_url;
        $hasQrImage         = $needsBakongPayment && ! $hasCheckoutUrl && ! empty($bakongQrImage);
        $hasQrString        = $needsBakongPayment && ! $hasCheckoutUrl && ! $hasQrImage && $order->bakong_qr_string;
    @endphp

    <div class="panel" style="padding:28px;max-width:760px;margin:0 auto">
        <h1 style="margin-top:0">Order placed</h1>
        <p style="color:var(--muted)">Thank you. Your order number is <strong>{{ $order->order_number }}</strong>.</p>
        <div style="display:flex;justify-content:space-between;border-top:1px solid var(--line);padding-top:14px;margin-top:18px">
            <span>Payment</span>
            <strong data-order-payment-label>{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;border-top:1px solid var(--line);padding-top:14px;margin-top:18px">
            <span>Total</span>
            <strong>${{ number_format($order->grand_total, 2) }}</strong>
        </div>
        @if($needsBakongPayment)
            <div class="payment-note" style="margin-top:18px" data-payment-waiting-note>
                Please scan the KHQR code and pay. This page updates automatically once payment is confirmed.
            </div>
        @endif
        <div style="margin-top:20px;display:flex;gap:10px">
            <a class="btn" href="{{ route('shop.home') }}">Continue shopping</a>
            <a class="btn secondary" href="{{ route('account.orders') }}">View orders</a>
        </div>
    </div>

    @if($needsBakongPayment)
        <div class="modal-backdrop is-open" data-payment-modal aria-hidden="false">
            <div class="payment-modal" role="dialog" aria-modal="true" aria-labelledby="payment-title"
                 style="{{ $hasCheckoutUrl ? 'max-width:520px;width:96%' : '' }}">
                <div class="payment-modal-head">
                    <h3 id="payment-title">Pay with KHQR — ${{ number_format($order->grand_total, 2) }}</h3>
                </div>
                <div class="payment-modal-body">

                    @if($hasCheckoutUrl)
                        {{-- Hosted Bakong checkout iframe --}}
                        <iframe
                            src="{{ $order->bakong_checkout_url }}"
                            width="100%"
                            height="560"
                            allow="clipboard-write"
                            style="border:none;border-radius:12px;display:block"
                            title="Bakong KHQR Payment">
                        </iframe>

                    @elseif($hasQrImage)
                        {{-- Styled KHQR image from the relay API — amount is locked --}}
                        <div class="payment-row">
                            <span>Order</span><strong>{{ $order->order_number }}</strong>
                        </div>
                        <div class="payment-row">
                            <span>Amount</span><strong>${{ number_format($order->grand_total, 2) }}</strong>
                        </div>
                        <div style="text-align:center;padding:16px 0 8px">
                            <img src="{{ $bakongQrImage }}"
                                 alt="KHQR {{ $order->order_number }}"
                                 style="max-width:280px;width:100%;border-radius:8px;display:block;margin:0 auto">
                            <p style="font-size:13px;color:var(--muted);margin:10px 0 0">
                                Scan with ABA, ACLEDA, Wing, Bakong, or any KHQR-supported app.<br>
                                <strong>Amount ${{ number_format($order->grand_total, 2) }} is fixed — cannot be changed.</strong>
                            </p>
                        </div>

                    @elseif($hasQrString)
                        {{-- Raw KHQR string rendered as a plain QR code in-browser --}}
                        <div class="payment-row">
                            <span>Order</span><strong>{{ $order->order_number }}</strong>
                        </div>
                        <div class="payment-row">
                            <span>Amount</span><strong>${{ number_format($order->grand_total, 2) }}</strong>
                        </div>
                        <div style="text-align:center;padding:16px 0 8px">
                            <canvas id="khqr-canvas" style="max-width:260px;width:100%;display:block;margin:0 auto;border-radius:8px"></canvas>
                            <p style="font-size:13px;color:var(--muted);margin:10px 0 0">
                                Scan with ABA, ACLEDA, Wing, Bakong, or any KHQR-supported app.<br>
                                <strong>Amount ${{ number_format($order->grand_total, 2) }} is fixed — cannot be changed.</strong>
                            </p>
                        </div>
                        @push('scripts')
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
                                integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSE1FtjHQuFhKZ/z6DMgI9gonzj4i7/KHGE5A=="
                                crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                        <script>
                            (function () {
                                var canvas = document.getElementById('khqr-canvas');
                                if (! canvas) return;
                                var qrString = @json($order->bakong_qr_string);
                                var qr = new QRCode(canvas, {
                                    text:   qrString,
                                    width:  260,
                                    height: 260,
                                    correctLevel: QRCode.CorrectLevel.M,
                                });
                            })();
                        </script>
                        @endpush

                    @else
                        <div style="text-align:center;padding:24px 12px">
                            <p style="font-weight:700;margin:0 0 6px">KHQR payment is not configured.</p>
                            <p style="color:var(--muted);margin:0 0 16px;font-size:13px">
                                Set <code>BAKONG_ACCOUNT_ID</code> in your .env file.
                            </p>
                            <div style="background:var(--surface,#f4f4f5);border-radius:8px;padding:14px;text-align:left;font-size:14px">
                                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                                    <span style="color:var(--muted)">Order</span>
                                    <strong>{{ $order->order_number }}</strong>
                                </div>
                                <div style="display:flex;justify-content:space-between">
                                    <span style="color:var(--muted)">Amount due</span>
                                    <strong>${{ number_format($order->grand_total, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="payment-note" data-payment-status-message style="margin-top:12px">
                        Waiting for payment confirmation…
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    var modal         = document.querySelector('[data-payment-modal]');
                    var statusMessage = document.querySelector('[data-payment-status-message]');
                    var waitingNote   = document.querySelector('[data-payment-waiting-note]');
                    var paymentLabel  = document.querySelector('[data-order-payment-label]');
                    var statusUrl     = @json(route('checkout.payment-status', $order));
                    var attempts = 0;
                    var timer;

                    if (! modal || ! statusUrl) return;

                    function markPaid() {
                        window.clearInterval(timer);
                        modal.classList.remove('is-open');
                        modal.setAttribute('aria-hidden', 'true');
                        if (paymentLabel) paymentLabel.textContent = 'Paid';
                        if (waitingNote) {
                            waitingNote.textContent = 'Payment received. Your order is being processed.';
                            waitingNote.style.background = '#ecfdf3';
                            waitingNote.style.color = '#087443';
                        }
                    }

                    function checkPayment() {
                        attempts += 1;
                        fetch(statusUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                        .then(function (data) {
                            if (data.is_paid) { markPaid(); return; }
                            if (statusMessage && attempts % 4 === 0) {
                                statusMessage.textContent = 'Still waiting — keep this page open after scanning.';
                            }
                        })
                        .catch(function () {
                            if (statusMessage) statusMessage.textContent = 'Checking payment status…';
                        });
                    }

                    window.addEventListener('message', function (e) {
                        if (e.data && e.data.event === 'payment_success') checkPayment();
                    });

                    timer = window.setInterval(checkPayment, 5000);
                    checkPayment();
                })();
            </script>
        @endpush
    @endif
@endsection
