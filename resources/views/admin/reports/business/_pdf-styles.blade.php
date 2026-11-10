{{-- dompdf: table layout and DejaVu Sans only, same as the order report. --}}
<style>
    @page { margin: 34px 36px 46px; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }

    .company { font-size: 16px; font-weight: bold; color: #063a74; }
    .generated { color: #6b7280; font-size: 9px; margin-top: 3px; }
    .doc-title { font-size: 15px; font-weight: bold; color: #0057a8; text-align: right; }
    .doc-period { font-size: 11px; font-weight: bold; text-align: right; margin-top: 3px; }
    .rule { border-top: 2px solid #0057a8; margin: 12px 0 16px; }

    .summary { font-size: 13px; font-weight: bold; color: #111827; margin-bottom: 12px; }
    .list thead { display: table-header-group; }
    .list tr { page-break-inside: avoid; }
    .list th { background: #063a74; color: #fff; text-align: left; padding: 6px 8px; font-size: 9px; }
    .list td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
    .list .num { text-align: right; white-space: nowrap; }
    .sub { color: #6b7280; font-size: 9px; margin-top: 2px; }
    .empty { color: #6b7280; }
    .order-head td { background: #f5f8fc; font-weight: bold; }
    .note { color: #6b7280; font-size: 9px; margin-top: 12px; }
</style>
