@php
    $today = now(config('app.shop_timezone'))->toImmutable()->startOfDay();
    $lastMonth = $today->startOfMonth()->subMonthNoOverflow();
    $lastYear = $today->startOfYear()->subYearNoOverflow();
    $presets = [
        'Today' => [$today, $today],
        'Last 7 days' => [$today->subDays(6), $today],
        'This month' => [$today->startOfMonth(), $today],
        'Last month' => [$lastMonth, $lastMonth->endOfMonth()],
        'This year' => [$today->startOfYear(), $today],
        'Last year' => [$lastYear, $lastYear->endOfYear()],
    ];
@endphp

<style>
    .rp-title{font-size:27px;line-height:1.2;font-weight:800;letter-spacing:-.015em;margin:0}
    .rp-sub{color:var(--muted);margin:6px 0 18px;max-width:72ch}
    .rp-sub a,.rp-link{color:var(--brand);font-weight:800;text-decoration:underline}
    .rp-range{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:14px;padding:14px;margin-bottom:18px}
    .rp-presets{display:flex;flex-wrap:wrap;gap:6px}
    .rp-presets a{padding:9px 12px;border-radius:7px;font-weight:700;color:var(--brand);background:#eef5ff}
    .rp-presets a.is-active{background:var(--brand);color:#fff}
    .rp-presets a:focus-visible{outline:2px solid var(--brand);outline-offset:2px}
    .rp-dates{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px}
    .rp-export{position:relative}
    .rp-export summary{list-style:none}
    .rp-export summary::-webkit-details-marker{display:none}
    .rp-export summary:focus-visible,.rp-export-menu a:focus-visible{outline:2px solid var(--brand);outline-offset:2px}
    .rp-export-menu{position:absolute;right:0;top:calc(100% + 6px);z-index:20;width:190px;padding:6px;background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 18px 40px rgba(16,24,40,.16)}
    .rp-export-menu a{display:block;padding:9px 10px;border-radius:6px;font-weight:700}
    .rp-export-menu a:hover{background:#eef5ff;color:var(--brand)}
    .rp-num{font-variant-numeric:tabular-nums}
    .rp-legend{display:flex;flex-wrap:wrap;gap:16px;margin:0 0 10px;color:#344054;font-size:12.5px;font-weight:700}
    .rp-legend span{display:inline-flex;align-items:center;gap:7px}
    .rp-legend i{width:12px;height:12px;border-radius:3px}
    .rp-in{background:#2a72c0}
    .rp-out{background:#c2410c}
    .rp-total{background:#344054}
    .rp-meter{display:flex;align-items:center;gap:8px;margin-top:6px;max-width:220px}
    .rp-meter-track{flex:0 0 110px;display:flex;gap:2px;height:8px;background:#eef2f8;border-radius:4px;overflow:hidden}
    .rp-meter-track b{display:block;height:100%}
    .rp-meter small{color:var(--muted);font-size:11.5px;white-space:nowrap}
</style>

<form class="panel rp-range" method="get" action="{{ url()->current() }}">
    <div class="rp-presets" aria-label="Quick date ranges">
        @foreach($presets as $label => [$start, $end])
            <a @class(['is-active' => $start->toDateString() === $from->toDateString() && $end->toDateString() === $to->toDateString()])
               href="{{ url()->current().'?from='.$start->toDateString().'&to='.$end->toDateString() }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="rp-dates">
        <div class="field">
            <label for="from">From</label>
            <input id="from" type="date" name="from" value="{{ $from->toDateString() }}">
        </div>
        <div class="field">
            <label for="to">To</label>
            <input id="to" type="date" name="to" value="{{ $to->toDateString() }}">
        </div>
        <button class="btn" type="submit">Show report</button>
        @isset($pdfRoute)
            <details class="rp-export">
                <summary class="btn secondary">Export PDF</summary>
                <div class="rp-export-menu">
                    @foreach($presets as $label => [$start, $end])
                        <a href="{{ route($pdfRoute, ['from' => $start->toDateString(), 'to' => $end->toDateString()]) }}" download onclick="this.closest('details').open = false">{{ $label }}</a>
                    @endforeach
                </div>
            </details>
        @endisset
    </div>
</form>
