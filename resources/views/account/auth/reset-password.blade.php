@extends('shop.layout')

@section('title', 'Choose a New Password - CEC Electronic')

@section('content')
    <section style="max-width:460px;margin:0 auto">
        <form class="panel auth-card" action="{{ route('password.store') }}" method="post">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <h2>Choose a new password</h2>

            <label>Email
                <input name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">
            </label>
            @error('email') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">New password
                <input name="password" type="password" required minlength="8" autofocus autocomplete="new-password">
            </label>
            @error('password') <div style="color:var(--danger);margin-top:6px">{{ $message }}</div> @enderror

            <label style="display:block;margin-top:14px">Confirm new password
                <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
            </label>

            <div class="auth-actions">
                <button class="btn" type="submit">Change password</button>
            </div>
        </form>
    </section>
@endsection
