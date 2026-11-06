@php($avatarUrl = $user->avatarUrl())
<span id="{{ $id ?? '' }}" style="width:{{ $size }}px;height:{{ $size }}px;border-radius:50%;overflow:hidden;flex:0 0 auto;display:grid;place-items:center;background:var(--brand);color:#fff;font-weight:900;font-size:{{ (int) ($size * 0.42) }}px">
    @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="Profile picture" style="width:100%;height:100%;object-fit:cover">
    @else
        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
    @endif
</span>
