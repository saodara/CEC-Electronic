<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login - CEC Electronic</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f6fb;color:#121926;font-family:Inter,Segoe UI,Arial,sans-serif}
        .login{width:min(420px,calc(100vw - 32px));background:#fff;border:1px solid #dde5f0;border-radius:8px;padding:24px;box-shadow:0 18px 45px rgba(16,24,40,.1)}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:18px}
        .mark{width:52px;height:52px;border-radius:8px;background:#fff;border:1px solid #dde5f0;display:grid;place-items:center;overflow:hidden}
        .mark img{width:100%;height:100%;object-fit:contain;padding:3px}
        h1{font-size:24px;margin:0}
        p{color:#667085;margin:6px 0 0;line-height:1.5}
        label{display:grid;gap:8px;font-weight:800;margin:18px 0}
        input{border:1px solid #dde5f0;border-radius:7px;padding:12px;font:inherit}
        button,a{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:7px;padding:11px 14px;font:inherit;font-weight:850;text-decoration:none}
        button{width:100%;background:#0057a8;color:#fff;cursor:pointer}
        a{margin-top:10px;width:100%;background:#eef5ff;color:#0057a8}
        .error{color:#d92d20;font-size:13px;margin-top:-8px;margin-bottom:12px}
    </style>
</head>
<body>
    <form class="login" action="{{ route('admin.login.store') }}" method="post">
        @csrf
        <div class="brand">
            <span class="mark"><img src="{{ asset('images/brand-logo.jpg') }}" alt="CEC Electronic logo"></span>
            <div>
                <h1>Admin Login</h1>
                <p>Control products, customers, orders, delivery, and suppliers.</p>
            </div>
        </div>

        <label>
            Admin email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        @error('email')
            <div class="error">{{ $message }}</div>
        @enderror

        <label>
            Admin password
            <input type="password" name="password" required>
        </label>
        @error('password')
            <div class="error">{{ $message }}</div>
        @enderror

        <button type="submit">Login to admin panel</button>
        <a href="{{ route('shop.home') }}">Back to store</a>
    </form>
</body>
</html>
