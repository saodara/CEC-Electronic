<form class="panel" method="get" action="{{ url()->current() }}" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px;padding:14px;margin-bottom:18px">
    <div class="field">
        <label for="from">From</label>
        <input id="from" type="date" name="from" value="{{ $from->toDateString() }}">
    </div>
    <div class="field">
        <label for="to">To</label>
        <input id="to" type="date" name="to" value="{{ $to->toDateString() }}">
    </div>
    <button class="btn" type="submit">Apply</button>
    <a class="btn secondary" href="{{ url()->current() }}">This month</a>
</form>
