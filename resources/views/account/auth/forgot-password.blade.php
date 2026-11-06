@extends('shop.layout')

@section('title', 'Forgot Password - CEC Electronic')

@section('content')
    <section style="max-width:460px;margin:0 auto">
        <div class="panel auth-card">
            <h2>Forgot password</h2>

            @if($canSendEmail)
                <p style="color:var(--muted);margin-top:0">Enter your account email and we'll send you a link to choose a new password.</p>

                @if(session('status'))
                    <div style="padding:11px 12px;margin-bottom:14px;border-radius:6px;background:#e7f8ef;color:#087443;font-weight:800">{{ session('status') }}</div>
                @endif

                <form action="{{ route('password.email') }}" method="post">
                    @csrf
                    <label>Email
                        <input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                    </label>
                    @error('email') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

                    <div class="auth-actions">
                        <button class="btn" type="submit">Send reset link</button>
                        <a class="btn secondary" href="{{ route('customer.login') }}">Back to login</a>
                    </div>
                </form>
            @else
                <p style="color:var(--muted);margin-top:0">To reset your password, please contact us and we'll help you get back into your account.</p>
                <div class="auth-actions">
                    <a class="btn" href="https://t.me/CECELECTRONICS" target="_blank" rel="noopener">Telegram @@CECELECTRONICS</a>
                    <a class="btn secondary" href="{{ route('customer.login') }}">Back to login</a>
                </div>
                <p style="color:var(--muted);margin-bottom:0">Or call 012 220 152.</p>
            @endif
        </div>
    </section>
@endsection
