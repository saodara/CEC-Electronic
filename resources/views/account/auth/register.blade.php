@extends('shop.layout')

@section('title', 'Register - CEC Electronic')

@section('content')
    <section class="auth-shell">
        <div class="panel auth-hero">
            <h1>Create your CEC customer account</h1>
            <p>Register once and keep your order history, delivery details, and warranty support records connected to your email.</p>
            <div class="service-row" style="grid-template-columns:1fr 1fr;margin-top:22px">
                <div class="service" style="background:rgba(255,255,255,.1);border-radius:8px"><span class="service-icon">DL</span><span><strong style="color:#fff">Delivery</strong><span style="color:#d9e7f7">Track status.</span></span></div>
                <div class="service" style="background:rgba(255,255,255,.1);border-radius:8px"><span class="service-icon">SP</span><span><strong style="color:#fff">Support</strong><span style="color:#d9e7f7">Faster help.</span></span></div>
            </div>
        </div>

        <form class="panel auth-card" action="{{ route('customer.register.store') }}" method="post">
            @csrf
            <h2>Register account</h2>

            <label>Name
                <input name="name" value="{{ old('name') }}" required autofocus>
            </label>
            @error('name') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Email
                <input name="email" type="email" value="{{ old('email') }}" required>
            </label>
            @error('email') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Password
                <input name="password" type="password" required>
            </label>
            @error('password') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Confirm password
                <input name="password_confirmation" type="password" required>
            </label>

            <div class="auth-actions">
                <button class="btn" type="submit">Create account</button>
                <a class="btn secondary" href="{{ route('customer.login') }}">Login</a>
            </div>
        </form>
    </section>
@endsection
