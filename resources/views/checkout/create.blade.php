@extends('shop.layout')

@section('title', 'Checkout - CEC Electronic')

@section('content')
    <div class="section-head">
        <h2>Checkout</h2>
        <a class="btn secondary" href="{{ route('shop.cart') }}">Back to cart</a>
    </div>

    @if($items->isEmpty())
        <div class="panel" style="padding:30px;text-align:center;color:var(--muted)">
            Your cart is empty. <a style="color:var(--brand);font-weight:800" href="{{ route('shop.home') }}">Continue shopping</a>
        </div>
    @else
        <ol class="checkout-steps">
            <li class="checkout-step is-done"><span class="checkout-step-num">&#10003;</span><span>Cart</span></li>
            <li class="checkout-step-line is-done"></li>
            <li class="checkout-step is-active"><span class="checkout-step-num">2</span><span>Checkout</span></li>
            <li class="checkout-step-line"></li>
            <li class="checkout-step"><span class="checkout-step-num">3</span><span>Confirmation</span></li>
        </ol>

        <form class="checkout" action="{{ route('checkout.store') }}" method="post" data-checkout-form>
            @csrf
            <div>
                <div class="panel checkout-section">
                    <div class="checkout-section-head">
                        <span class="icon">&#9993;</span>
                        <h3>Contact & delivery details</h3>
                    </div>
                    <div class="field-grid">
                        <label>Full name<input name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required></label>
                        <label>Phone<input name="customer_phone" value="{{ old('customer_phone') }}" required></label>
                        <label>Email<input name="customer_email" type="email" value="{{ old('customer_email', auth()->user()?->email) }}"></label>
                        <label>City<input name="city" value="{{ old('city', 'Phnom Penh') }}" required></label>
                        <label style="grid-column:1 / -1">Address line 1<input name="address_line_1" value="{{ old('address_line_1') }}" required></label>
                        <label style="grid-column:1 / -1">Address line 2<input name="address_line_2" value="{{ old('address_line_2') }}"></label>
                        <label>Province<input name="province" value="{{ old('province') }}"></label>
                        <label>Country<input name="country" value="{{ old('country', 'Cambodia') }}"></label>
                        <label style="grid-column:1 / -1">Notes<textarea name="notes" style="min-height:80px">{{ old('notes') }}</textarea></label>
                    </div>
                    @if($errors->any())
                        <div style="color:#e11d48;margin-top:12px;font-weight:700">Please check the form fields.</div>
                    @endif
                </div>

                <div class="panel checkout-section">
                    <div class="checkout-section-head">
                        <span class="icon">&#128666;</span>
                        <h3>Shipping method</h3>
                    </div>
                    <label>
                        <select name="shipping_method">
                            <option value="standard">Standard delivery — Free</option>
                            <option value="express">Same-day Phnom Penh</option>
                        </select>
                    </label>
                </div>

                <div class="panel checkout-section">
                    <div class="checkout-section-head">
                        <span class="icon">&#128179;</span>
                        <h3>Payment method</h3>
                    </div>
                    <div class="payment-options" data-payment-options>
                        <label class="payment-option is-checked">
                            <input type="radio" name="payment_method" value="bakong" data-payment-method checked>
                            <span class="payment-option-icon">&#128241;</span>
                            <span class="payment-option-body">
                                <strong>KHQR (Bakong)</strong>
                                <span>Pay instantly with ABA, ACLEDA, Wing, or any Bakong app</span>
                            </span>
                            <span class="payment-option-check"></span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash_on_delivery" data-payment-method>
                            <span class="payment-option-icon">&#128176;</span>
                            <span class="payment-option-body">
                                <strong>Cash on delivery</strong>
                                <span>Pay with cash when your order arrives</span>
                            </span>
                            <span class="payment-option-check"></span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" data-payment-method>
                            <span class="payment-option-icon">&#127974;</span>
                            <span class="payment-option-body">
                                <strong>Bank transfer</strong>
                                <span>Transfer to our bank account and we'll confirm manually</span>
                            </span>
                            <span class="payment-option-check"></span>
                        </label>
                    </div>
                </div>
            </div>

            <aside class="panel checkout-section order-summary">
                <div class="checkout-section-head">
                    <span class="icon">&#128722;</span>
                    <h3>Order summary</h3>
                </div>
                @foreach($items as $item)
                    <div class="order-summary-item">
                        <span class="order-summary-thumb">
                            <img src="{{ $item->product?->image_url }}" alt="{{ $item->product?->name }}">
                        </span>
                        <span class="order-summary-info">
                            <strong>{{ $item->product?->name }}</strong>
                            <span>Qty {{ $item->quantity }}</span>
                        </span>
                        <span class="order-summary-price">${{ number_format($item->line_total, 2) }}</span>
                    </div>
                @endforeach
                <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span>Subtotal</span><strong>${{ number_format($subtotal, 2) }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:16px">
                    <span>Delivery</span><strong style="color:var(--success)">Free</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:16px;font-size:18px;border-top:1px solid var(--line);padding-top:14px">
                    <span>Total</span><strong data-payment-total>${{ number_format($subtotal, 2) }}</strong>
                </div>
                <button class="btn" style="width:100%" type="submit">Place order</button>
                <div class="trust-row">&#128274; Secure checkout &nbsp;•&nbsp; Buyer protection on every order</div>
            </aside>
        </form>

        @push('scripts')
            <script>
                (function () {
                    var form = document.querySelector('[data-checkout-form]');
                    if (! form) return;

                    // Prevents a double-click (or slow network + impatient click)
                    // from submitting the order twice — the second submission
                    // would otherwise hit an already-cleared cart and error out.
                    form.addEventListener('submit', function () {
                        var button = form.querySelector('button[type="submit"]');
                        if (! button) return;

                        window.setTimeout(function () {
                            button.disabled = true;
                            button.textContent = 'Placing order…';
                        }, 0);
                    });

                    var options = document.querySelector('[data-payment-options]');
                    if (options) {
                        options.addEventListener('change', function (event) {
                            if (! event.target.matches('[data-payment-method]')) return;

                            options.querySelectorAll('.payment-option').forEach(function (option) {
                                var input = option.querySelector('[data-payment-method]');
                                option.classList.toggle('is-checked', !!(input && input.checked));
                            });
                        });
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
