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
        <form class="checkout" action="{{ route('checkout.store') }}" method="post" data-checkout-form>
            @csrf
            <div class="panel" style="padding:18px">
                <h3 style="margin-top:0">Customer and delivery details</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <label>Full name<input name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required></label>
                    <label>Phone<input name="customer_phone" value="{{ old('customer_phone') }}" required></label>
                    <label>Email<input name="customer_email" type="email" value="{{ old('customer_email', auth()->user()?->email) }}"></label>
                    <label>City<input name="city" value="{{ old('city', 'Phnom Penh') }}" required></label>
                    <label style="grid-column:1 / -1">Address line 1<input name="address_line_1" value="{{ old('address_line_1') }}" required></label>
                    <label style="grid-column:1 / -1">Address line 2<input name="address_line_2" value="{{ old('address_line_2') }}"></label>
                    <label>Province<input name="province" value="{{ old('province') }}"></label>
                    <label>Country<input name="country" value="{{ old('country', 'Cambodia') }}"></label>
                    <label>Shipping
                        <select name="shipping_method">
                            <option value="standard">Standard delivery</option>
                            <option value="express">Same-day Phnom Penh</option>
                        </select>
                    </label>
                    <label>Payment
                        <select name="payment_method" data-payment-method>
                            <option value="bakong">Bakong / KHQR</option>
                            <option value="cash_on_delivery">Cash on delivery</option>
                            <option value="bank_transfer">Bank transfer</option>
                        </select>
                    </label>
                    <label style="grid-column:1 / -1">Notes<textarea name="notes" style="min-height:90px">{{ old('notes') }}</textarea></label>
                </div>
                @if($errors->any())
                    <div style="color:#e11d48;margin-top:12px">Please check the form fields.</div>
                @endif
            </div>

            <aside class="panel" style="padding:18px">
                <h3 style="margin-top:0">Order summary</h3>
                @foreach($items as $item)
                    <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:10px">
                        <span>{{ $item->product?->name }} x {{ $item->quantity }}</span>
                        <strong>${{ number_format($item->line_total, 2) }}</strong>
                    </div>
                @endforeach
                <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span>Subtotal</span><strong>${{ number_format($subtotal, 2) }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:16px">
                    <span>Delivery</span><strong>Free</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:16px;font-size:18px">
                    <span>Total</span><strong data-payment-total>${{ number_format($subtotal, 2) }}</strong>
                </div>
                <button class="btn" style="width:100%" type="submit">Place order</button>
            </aside>
        </form>
    @endif
@endsection
