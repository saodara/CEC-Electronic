@extends('shop.layout')

@section('title', 'Customer Login - CEC Electronic')

@section('content')
    <section class="auth-shell">
        <div class="panel auth-hero">
            <h1>Welcome back to CEC Electronic</h1>
            <p>Login to view your order history, check delivery progress, keep warranty order numbers, and checkout faster next time.</p>
            <div class="service-row" style="grid-template-columns:1fr 1fr;margin-top:22px">
                <div class="service" style="background:rgba(255,255,255,.1);border-radius:8px"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/order-icon.png') }}" alt="Orders"></span><span><strong style="color:#fff">Orders</strong><span style="color:#d9e7f7">Track purchases.</span></span></div>
                <div class="service" style="background:rgba(255,255,255,.1);border-radius:8px"><span class="service-icon"><img src="{{ asset('images/ProfileAndOrder/warranty-icon.jpeg') }}" alt="Warranty"></span><span><strong style="color:#fff">Warranty</strong><span style="color:#d9e7f7">Save records.</span></span></div>
            </div>
        </div>

        <form class="panel auth-card" action="{{ route('customer.login.store') }}" method="post">
            @csrf
            <h2>Customer login</h2>

            @if(session('status'))
                <div style="padding:11px 12px;margin-bottom:14px;border-radius:6px;background:#fff3cf;color:#8a5a00;font-weight:800">
                    {{ session('status') }}
                </div>
            @endif

            <label>Email
                <input name="email" type="email" value="{{ old('email') }}" required autofocus>
            </label>
            @error('email') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Password
                <input name="password" type="password" required>
            </label>
            @error('password') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:flex;gap:8px;align-items:center;margin-top:14px">
                <input type="checkbox" name="remember" value="1" style="width:auto;margin:0">
                Remember me
            </label>

            <div class="auth-actions">
                <button class="btn" type="submit">Login</button>
                <a class="btn secondary" href="{{ route('customer.register') }}">Create account</a>
            </div>
        </form>
    </section>
@endsection
