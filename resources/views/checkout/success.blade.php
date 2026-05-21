@extends('shop.layout')

@section('title', 'Order Placed - CEC Electronic')

@section('content')
    @php
        $needsBakongPayment = $order->payment_method === 'bakong' && $order->payment_status !== 'paid';
        $bakongDeeplink = config('services.bakong.deeplink');
        $bakongMerchantName = config('services.bakong.merchant_name');
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
            <strong>${{ number_format($order->grand_total) }}</strong>
        </div>
        @if($needsBakongPayment)
            <div class="payment-note" style="margin-top:18px" data-payment-waiting-note>
                Please scan the KHQR popup and pay from your banking app. This page will close the popup automatically after the payment is verified by the system.
            </div>
        @endif
        <div style="margin-top:20px;display:flex;gap:10px">
            <a class="btn" href="{{ route('shop.home') }}">Continue shopping</a>
            <a class="btn secondary" href="{{ route('account.orders') }}">View orders</a>
        </div>
    </div>

    @if($needsBakongPayment)
        <div class="modal-backdrop is-open" data-payment-modal aria-hidden="false">
            <div class="payment-modal" role="dialog" aria-modal="true" aria-labelledby="payment-title">
                <div class="payment-modal-head">
                    <h3 id="payment-title">Scan KHQR to pay</h3>
                </div>
                <div class="payment-modal-body">
                    <div class="payment-row">
                        <span>Order</span>
                        <strong>{{ $order->order_number }}</strong>
                    </div>
                    <div class="payment-row">
                        <span>Total amount</span>
                        <strong>${{ number_format($order->grand_total) }}</strong>
                    </div>
                    <div class="payment-qr is-visible">
                        <img src="{{ asset('images/Payment/bakong-qr.jpg') }}" alt="CEC Electronic Bakong KHQR">
                        <span>Merchant: {{ $bakongMerchantName }}. Scan with ABA, ACLEDA, Wing, Bakong, or another KHQR-supported Cambodia banking app.</span>
                    </div>
                    <div class="payment-note" data-payment-status-message>
                        Waiting for bank payment verification. The popup will close automatically when payment is received.
                    </div>
                </div>
                @if($bakongDeeplink)
                    <div style="padding:0 18px 18px">
                        <a class="btn secondary payment-app-link is-visible" style="width:100%" href="{{ $bakongDeeplink }}">
                            Open Bakong / bank app
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    var modal = document.querySelector('[data-payment-modal]');
                    var statusMessage = document.querySelector('[data-payment-status-message]');
                    var waitingNote = document.querySelector('[data-payment-waiting-note]');
                    var paymentLabel = document.querySelector('[data-order-payment-label]');
                    var statusUrl = @json(route('checkout.payment-status', $order));
                    var attempts = 0;

                    if (! modal || ! statusUrl) return;

                    function markPaid(data) {
                        modal.classList.remove('is-open');
                        modal.setAttribute('aria-hidden', 'true');
                        if (paymentLabel) paymentLabel.textContent = 'Paid';
                        if (waitingNote) {
                            waitingNote.textContent = 'Payment received. Admin has been notified and your order is ready for processing.';
                            waitingNote.style.background = '#ecfdf3';
                            waitingNote.style.color = '#087443';
                        }
                        window.clearInterval(timer);
                    }

                    function checkPayment() {
                        attempts += 1;

                        fetch(statusUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(function (response) {
                                if (! response.ok) throw new Error('Payment status unavailable.');
                                return response.json();
                            })
                            .then(function (data) {
                                if (data.is_paid) {
                                    markPaid(data);
                                    return;
                                }

                                if (statusMessage && attempts % 4 === 0) {
                                    statusMessage.textContent = 'Still waiting for verified payment. Please keep this page open after scanning KHQR.';
                                }
                            })
                            .catch(function () {
                                if (statusMessage) {
                                    statusMessage.textContent = 'Checking payment status again. Please keep this page open.';
                                }
                            });
                    }

                    var timer = window.setInterval(checkPayment, 3000);
                    checkPayment();
                })();
            </script>
        @endpush
    @endif
@endsection
