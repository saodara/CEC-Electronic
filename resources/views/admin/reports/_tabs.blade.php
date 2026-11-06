@php
    $reportTabs = [
        ['label' => 'Profit & Loss', 'url' => route('admin.reports.profit-loss'), 'active' => request()->routeIs('admin.reports.profit-loss')],
        ['label' => 'Product Revenue', 'url' => route('admin.reports.products'), 'active' => request()->routeIs('admin.reports.products')],
        ['label' => 'Customer', 'url' => route('admin.reports.customers'), 'active' => request()->routeIs('admin.reports.customers')],
        ['label' => 'Supplier', 'url' => route('admin.reports.suppliers'), 'active' => request()->routeIs('admin.reports.suppliers')],
    ];

    foreach (['day' => 'Daily Orders', 'week' => 'Weekly Orders', 'month' => 'Monthly Orders', 'year' => 'Yearly Orders'] as $tabPeriod => $tabLabel) {
        $reportTabs[] = [
            'label' => $tabLabel,
            'url' => route('admin.reports.orders', ['period' => $tabPeriod]),
            'active' => request()->routeIs('admin.reports.orders') && request()->route('period') === $tabPeriod,
        ];
    }
@endphp
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px">
    @foreach($reportTabs as $tab)
        <a @class(['btn', 'secondary' => ! $tab['active']]) href="{{ $tab['url'] }}">{{ $tab['label'] }}</a>
    @endforeach
</div>
