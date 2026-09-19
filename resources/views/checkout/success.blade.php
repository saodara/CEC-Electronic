@extends('shop.layout')

@section('title', 'Order Placed - CEC Electronic')

@section('content')
    @php
        $needsBakongPayment = $order->payment_method === 'bakong' && $order->payment_status !== 'paid';
        $hasQrString        = $needsBakongPayment && $order->bakong_qr_string;
        $isPaid             = $order->payment_status === 'paid';
        $qrExpired          = $hasQrString && $order->bakongQrExpired();
        // Remaining seconds are computed server-side so the countdown doesn't
        // depend on the customer's device clock being correct.
        $qrSecondsLeft      = $hasQrString && $order->bakong_qr_expires_at
            ? max(0, (int) now()->diffInSeconds($order->bakong_qr_expires_at, false))
            : null;
    @endphp

    <div class="panel receipt-card">
        <div class="success-hero">
            <div class="check">&#10003;</div>
            <h1>Order placed successfully!</h1>
            <p>Thank you, {{ $order->customer_name }}. We've received your order.</p>
        </div>

        <div class="receipt-body">
            <div class="receipt-order-no">Order number<br><strong>{{ $order->order_number }}</strong></div>

            <div class="receipt-row">
                <span>Payment method</span>
                <strong>{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</strong>
            </div>
            <div class="receipt-row">
                <span>Payment status</span>
                <strong data-order-payment-label>
                    <span class="status-pill {{ $isPaid ? 'is-paid' : 'is-pending' }}">
                        {{ $isPaid ? '● Paid' : '● Pending' }}
                    </span>
                </strong>
            </div>
            <div class="receipt-row is-total">
                <span>Total</span>
                <strong>${{ number_format($order->grand_total, 2) }}</strong>
            </div>

            @if($needsBakongPayment)
                <div class="payment-note" style="margin-top:18px" data-payment-waiting-note>
                    Please scan the KHQR code and pay. This page updates automatically once payment is confirmed.
                </div>
            @endif

            <div class="next-steps">
                <div>
                    <span class="num">1</span>
                    <strong>Processing</strong>
                    <span>We're preparing your order</span>
                </div>
                <div>
                    <span class="num">2</span>
                    <strong>Shipping</strong>
                    <span>Out for delivery</span>
                </div>
                <div>
                    <span class="num">3</span>
                    <strong>Delivered</strong>
                    <span>Enjoy your purchase</span>
                </div>
            </div>

            <div class="receipt-actions">
                @if($needsBakongPayment)
                    <button type="button" class="btn" data-payment-reopen hidden>Pay with KHQR</button>
                @endif
                <a class="btn" href="{{ route('account.orders.receipt', $order) }}" data-receipt-link @unless($isPaid) hidden @endunless>Download receipt</a>
                <a class="btn secondary" href="{{ route('shop.home') }}">Continue shopping</a>
                <a class="btn" href="{{ route('account.orders') }}">View orders</a>
            </div>
        </div>
    </div>

    @if($needsBakongPayment)
        <div class="modal-backdrop is-open" data-payment-modal aria-hidden="false">
            <div class="payment-modal" role="dialog" aria-modal="true" aria-labelledby="payment-title">
                <div class="payment-modal-head" data-payment-modal-head>
                    <h3 id="payment-title">Pay with KHQR — ${{ number_format($order->grand_total, 2) }}</h3>
                </div>
                <div class="payment-modal-body" data-payment-modal-body>

                    @if($hasQrString)
                        {{-- Raw KHQR string rendered as a plain QR code in-browser --}}
                        <div class="payment-row">
                            <span>Order</span><strong>{{ $order->order_number }}</strong>
                        </div>
                        <div class="payment-row">
                            <span>Amount</span><strong>${{ number_format($order->grand_total, 2) }}</strong>
                        </div>
                        <div data-qr-active @if($qrExpired) hidden @endif style="text-align:center;padding:16px 0 8px">
                            <div id="khqr-canvas" style="max-width:260px;width:260px;display:inline-block;margin:0 auto;border-radius:8px;overflow:hidden;padding:10px;background:#fff;border:1px solid var(--line)"></div>
                            @if($qrSecondsLeft !== null)
                                <p style="font-size:14px;font-weight:700;margin:10px 0 0">
                                    QR expires in <span data-qr-countdown style="font-variant-numeric:tabular-nums">--:--</span>
                                </p>
                            @endif
                            <p style="font-size:13px;color:var(--muted);margin:10px 0 0">
                                Scan with ABA, ACLEDA, Wing, Bakong, or any KHQR-supported app.<br>
                                <strong>Amount ${{ number_format($order->grand_total, 2) }} is fixed — cannot be changed.</strong>
                            </p>
                            <button type="button" class="btn secondary" data-payment-close style="width:100%;margin-top:14px">Close</button>
                        </div>
                        <div data-qr-expired @if(! $qrExpired) hidden @endif style="text-align:center;padding:24px 12px">
                            <p style="font-weight:700;margin:0 0 6px">This QR code has expired</p>
                            <p style="color:var(--muted);margin:0 0 16px;font-size:13px">
                                QR codes are valid for {{ (int) config('services.bakong.qr_expiry_seconds', 90) }} seconds. Do not pay an expired code — generate a new one.
                            </p>
                            <div style="display:flex;gap:10px">
                                <form method="POST" action="{{ route('checkout.regenerate-qr', $order) }}" style="flex:1;margin:0">
                                    @csrf
                                    <button type="submit" class="btn" style="width:100%">Generate new QR</button>
                                </form>
                                <button type="button" class="btn secondary" data-payment-close style="flex:1">Close</button>
                            </div>
                        </div>
                        @push('scripts')
                        {{-- Self-hosted: don't depend on an external CDN to render a payment QR --}}
                        <script src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}"></script>
                        <script>
                            (function () {
                                var canvas = document.getElementById('khqr-canvas');
                                if (! canvas || @json($qrExpired)) return;
                                var qrString = @json($order->bakong_qr_string);
                                var qr = new QRCode(canvas, {
                                    text:   qrString,
                                    width:  240,
                                    height: 240,
                                    correctLevel: QRCode.CorrectLevel.M,
                                });
                            })();
                        </script>
                        @endpush

                    @else
                        <div style="text-align:center;padding:24px 12px">
                            <p style="font-weight:700;margin:0 0 6px">KHQR payment is not configured.</p>
                            <p style="color:var(--muted);margin:0 0 16px;font-size:13px">
                                Set <code>BAKONG_ACCOUNT_USERNAME</code> and <code>BAKONG_ACCESS_TOKEN</code> in your .env file.
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
                        &#8987; Waiting for payment confirmation…
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    var modal         = document.querySelector('[data-payment-modal]');
                    var modalHead     = document.querySelector('[data-payment-modal-head]');
                    var modalBody     = document.querySelector('[data-payment-modal-body]');
                    var statusMessage = document.querySelector('[data-payment-status-message]');
                    var waitingNote   = document.querySelector('[data-payment-waiting-note]');
                    var paymentLabel  = document.querySelector('[data-order-payment-label]');
                    var statusUrl     = @json(route('checkout.payment-status', $order));
                    var ordersUrl     = @json(route('account.orders'));
                    var receiptUrl    = @json(route('account.orders.receipt', $order));
                    var receiptLink   = document.querySelector('[data-receipt-link]');
                    var orderNumber   = @json($order->order_number);
                    var amountLabel   = @json('$' . number_format($order->grand_total, 2));
                    var qrActive      = document.querySelector('[data-qr-active]');
                    var qrExpiredBox  = document.querySelector('[data-qr-expired]');
                    var countdownEl   = document.querySelector('[data-qr-countdown]');
                    var secondsLeft   = @json($qrSecondsLeft);
                    var attempts = 0;
                    var timer;
                    var countdownTimer;

                    if (! modal || ! statusUrl) return;

                    var reopenBtn = document.querySelector('[data-payment-reopen]');

                    var isPaid = false;

                    function setModalOpen(open) {
                        modal.classList.toggle('is-open', open);
                        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
                        if (reopenBtn) reopenBtn.hidden = open || isPaid;
                    }

                    modal.querySelectorAll('[data-payment-close]').forEach(function (btn) {
                        btn.addEventListener('click', function () { setModalOpen(false); });
                    });
                    if (reopenBtn) reopenBtn.addEventListener('click', function () { setModalOpen(true); });

                    function showExpired() {
                        window.clearInterval(timer);
                        window.clearInterval(countdownTimer);
                        if (qrActive) qrActive.hidden = true;
                        if (qrExpiredBox) qrExpiredBox.hidden = false;
                        if (statusMessage) statusMessage.textContent = 'This QR code has expired.';
                        if (waitingNote) waitingNote.textContent = 'The QR code expired. Generate a new one to pay.';
                    }

                    function renderCountdown() {
                        if (! countdownEl) return;
                        var m = Math.floor(secondsLeft / 60);
                        var sec = secondsLeft % 60;
                        countdownEl.textContent = (m < 10 ? '0' : '') + m + ':' + (sec < 10 ? '0' : '') + sec;
                    }

                    function markPaid() {
                        window.clearInterval(timer);
                        // Polling continues while the popup is closed, so show the
                        // success popup (with the receipt button) even then.
                        isPaid = true;
                        setModalOpen(true);

                        if (modalHead) {
                            modalHead.innerHTML = '<h3 id="payment-title">Payment successful</h3>';
                        }
                        if (modalBody) {
                            modalBody.innerHTML =
                                '<div style="text-align:center;padding:12px 4px 4px">' +
                                    '<div style="width:64px;height:64px;border-radius:50%;background:#ecfdf3;color:#087443;' +
                                        'display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:32px;line-height:1">&#10003;</div>' +
                                    '<h4 style="margin:0 0 6px;font-size:18px;color:var(--ink)">Payment successful!</h4>' +
                                    '<p style="margin:0 0 18px;color:var(--muted)">Order <strong>' + orderNumber + '</strong> — ' + amountLabel + ' paid via KHQR.</p>' +
                                    '<a class="btn" href="' + receiptUrl + '" style="width:100%;display:block;box-sizing:border-box;margin-bottom:10px">Download receipt</a>' +
                                    '<button type="button" class="btn secondary" data-payment-success-close style="width:100%">Continue</button>' +
                                '</div>';

                            var closeBtn = modalBody.querySelector('[data-payment-success-close]');
                            if (closeBtn) {
                                closeBtn.addEventListener('click', function () {
                                    setModalOpen(false);
                                    window.location.href = ordersUrl;
                                });
                            }
                        }

                        if (receiptLink) receiptLink.hidden = false;

                        if (paymentLabel) {
                            paymentLabel.innerHTML = '<span class="status-pill is-paid">&#9679; Paid</span>';
                        }
                        if (waitingNote) {
                            waitingNote.textContent = 'Payment received. Your order is being processed.';
                            waitingNote.style.background = '#ecfdf3';
                            waitingNote.style.color = '#087443';
                        }
                    }

                    // Bakong's transaction-check API allows very few requests per
                    // day for the whole store, so this polls slowly and stops
                    // after a while rather than hammering it while a tab sits open.
                    var POLL_INTERVAL_MS = 15000;
                    var MAX_ATTEMPTS     = 40; // ~10 minutes

                    function checkPayment() {
                        attempts += 1;

                        if (attempts > MAX_ATTEMPTS) {
                            window.clearInterval(timer);
                            if (statusMessage) {
                                statusMessage.textContent = 'Still not confirmed. If you already paid, refresh this page in a minute — no need to pay again.';
                            }
                            return;
                        }

                        fetch(statusUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                        .then(function (data) {
                            if (data.is_paid) { window.clearInterval(countdownTimer); markPaid(); return; }
                            if (data.qr_expired) { showExpired(); return; }
                            if (statusMessage && attempts % 2 === 0) {
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

                    if (secondsLeft === null) {
                        timer = window.setInterval(checkPayment, POLL_INTERVAL_MS);
                        checkPayment();
                    } else if (secondsLeft <= 0) {
                        showExpired();
                    } else {
                        renderCountdown();
                        countdownTimer = window.setInterval(function () {
                            secondsLeft -= 1;
                            if (secondsLeft <= 0) { showExpired(); return; }
                            renderCountdown();
                        }, 1000);
                        timer = window.setInterval(checkPayment, POLL_INTERVAL_MS);
                        checkPayment();
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
