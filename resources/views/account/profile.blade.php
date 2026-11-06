@extends('shop.layout')

@section('title', 'Edit Profile - CEC Electronic')

@section('content')
    <div class="section-head">
        <div>
            <h2>Edit profile</h2>
            <p>Update your picture, details, email and password.</p>
        </div>
        <a class="btn secondary" href="{{ route('account.dashboard') }}">Back to my account</a>
    </div>

    @if(session('status'))
        <div style="padding:11px 12px;margin-bottom:14px;border-radius:6px;background:#e7f8ef;color:#087443;font-weight:800">{{ session('status') }}</div>
    @endif

    <div style="display:grid;gap:14px;max-width:760px">
        <form class="panel" style="padding:18px" action="{{ route('account.profile.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <h3 style="margin-top:0">Profile</h3>

            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:14px">
                @include('account._avatar', ['size' => 88, 'id' => 'avatarPreview'])
                <label style="flex:1 1 240px">Profile picture
                    <input id="avatarInput" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
                    <span style="display:block;margin-top:5px;color:var(--muted);font-weight:400;font-size:12px">JPG, PNG or WEBP, up to 8 MB. It will be cropped to a square.</span>
                </label>
            </div>
            <div id="avatarClientError" style="color:var(--danger);margin:-6px 0 12px" hidden></div>
            @error('avatar') <div style="color:var(--danger);margin:-6px 0 12px">{{ $message }}</div> @enderror

            <div class="field-grid">
                <label>Name
                    <input name="name" value="{{ old('name', $user->name) }}" required maxlength="255">
                    @error('name') <span style="display:block;color:var(--danger);margin-top:6px;font-weight:400">{{ $message }}</span> @enderror
                </label>
                <label>Phone
                    <input name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required maxlength="20">
                    @error('phone') <span style="display:block;color:var(--danger);margin-top:6px;font-weight:400">{{ $message }}</span> @enderror
                </label>
            </div>

            <button class="btn" style="margin-top:14px" type="submit">Save profile</button>
        </form>

        @if($user->avatarUrl())
            <form action="{{ route('account.avatar.destroy') }}" method="post" style="margin-top:-6px">
                @csrf
                @method('DELETE')
                <button class="btn secondary" type="submit">Remove profile picture</button>
            </form>
        @endif

        <form class="panel" style="padding:18px" action="{{ route('account.email.update') }}" method="post">
            @csrf
            @method('PUT')
            <h3 style="margin-top:0">Change email</h3>
            <p style="color:var(--muted);margin-top:0">Current email: <strong>{{ $user->email }}</strong>. You will use the new one to log in, so check it carefully.</p>
            <div class="field-grid">
                <label>New email
                    <input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                    @error('email', 'updateEmail') <span style="display:block;color:var(--danger);margin-top:6px;font-weight:400">{{ $message }}</span> @enderror
                </label>
                <label>Current password
                    <input name="current_password" type="password" required autocomplete="current-password">
                    @error('current_password', 'updateEmail') <span style="display:block;color:var(--danger);margin-top:6px;font-weight:400">{{ $message }}</span> @enderror
                </label>
            </div>
            <button class="btn" style="margin-top:14px" type="submit">Change email</button>
        </form>

        <form class="panel" style="padding:18px" action="{{ route('account.password.update') }}" method="post">
            @csrf
            @method('PUT')
            <h3 style="margin-top:0">Change password</h3>
            <label style="display:block">Current password
                <input name="current_password" type="password" required autocomplete="current-password">
                @error('current_password', 'updatePassword') <span style="display:block;color:var(--danger);margin-top:6px;font-weight:400">{{ $message }}</span> @enderror
            </label>
            <div class="field-grid" style="margin-top:14px">
                <label>New password
                    <input name="password" type="password" required minlength="8" autocomplete="new-password">
                    @error('password', 'updatePassword') <span style="display:block;color:var(--danger);margin-top:6px;font-weight:400">{{ $message }}</span> @enderror
                </label>
                <label>Confirm new password
                    <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                </label>
            </div>
            <button class="btn" style="margin-top:14px" type="submit">Change password</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('avatarInput');
            var preview = document.getElementById('avatarPreview');
            var error = document.getElementById('avatarClientError');

            input.addEventListener('change', function () {
                var file = input.files[0];
                error.hidden = true;
                if (! file) return;

                if (file.size > 8 * 1024 * 1024) {
                    input.value = '';
                    error.textContent = 'Please choose an image smaller than 8 MB.';
                    error.hidden = false;
                    return;
                }

                var image = document.createElement('img');
                image.alt = 'New profile picture';
                image.style.cssText = 'width:100%;height:100%;object-fit:cover';
                image.src = URL.createObjectURL(file);
                preview.replaceChildren(image);
            });
        })();
    </script>
@endpush
