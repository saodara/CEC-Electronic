<section class="panel" style="padding:16px 18px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:12px 32px;align-items:center">
    <div>
        <span class="sku" style="display:block">Total spent</span>
        <strong style="font-size:26px">${{ number_format($spending['paid'], 2) }}</strong>
    </div>
    <div>
        <span class="sku" style="display:block">Orders</span>
        <strong style="font-size:26px">{{ number_format($spending['orders']) }}</strong>
    </div>
    @if($spending['unpaid'] > 0)
        <div>
            <span class="sku" style="display:block">Waiting for payment</span>
            <strong style="font-size:26px;color:var(--danger, #b42318)">${{ number_format($spending['unpaid'], 2) }}</strong>
        </div>
    @endif
    <span class="sku" style="flex:1 1 220px">Total spent counts paid orders. Cancelled orders are not counted.</span>
</section>
