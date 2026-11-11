@extends('admin.layout')

@section('title', 'Bill vs Invoice - CEC Electronic Admin')
@section('heading', 'Bill vs Invoice')

@section('content')
    <style>
        .guide{max-width:980px}
        .guide h2{font-size:18px;margin:28px 0 12px}
        .guide h2:first-child{margin-top:0}
        .guide-compare{width:100%;border-collapse:collapse}
        .guide-compare th,.guide-compare td{text-align:left;padding:11px 14px;border-bottom:1px solid var(--line);vertical-align:top}
        .guide-compare thead th{background:var(--soft);font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted)}
        .guide-compare tbody th{width:150px;color:var(--muted);font-weight:700}
        .guide-examples{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
        .guide-stage{border-radius:14px;height:420px;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:20px}
        .guide-stage.bill{background:linear-gradient(160deg,#eef1f5,#cfd5dd)}
        .guide-stage.invoice{background:#c9ced6;justify-content:flex-end;padding-right:0}
        .guide-caption{margin:12px 2px 0}
        .guide-caption strong{display:block;font-size:15px}
        .guide-caption span{color:var(--muted);font-size:13px}

        .slip{width:230px;background:#fff;color:#3a3f47;font-family:"Courier New",Courier,monospace;font-size:11px;line-height:1.45;padding:18px 16px;box-shadow:0 14px 30px rgba(16,24,40,.22);position:relative}
        .slip::before,.slip::after{content:"";position:absolute;left:0;right:0;height:8px;background:radial-gradient(circle at 5px 0,transparent 4px,#fff 4.5px) 0 0/10px 8px repeat-x}
        .slip::before{top:-8px;transform:rotate(180deg)}
        .slip::after{bottom:-8px}
        .slip-center{text-align:center}
        .slip-shop{font-family:Inter,Segoe UI,Arial,sans-serif;font-weight:800;font-size:15px;letter-spacing:.08em}
        .slip-stars{text-align:center;letter-spacing:.18em;color:#9aa1ab;overflow:hidden;white-space:nowrap;margin:6px 0}
        .slip-row{display:flex;justify-content:space-between;gap:8px}
        .slip-total{font-size:14px;font-weight:800}
        .slip-barcode{height:30px;margin:10px auto 0;width:150px;background:repeating-linear-gradient(90deg,#2b2f36 0 2px,transparent 2px 4px,#2b2f36 4px 5px,transparent 5px 8px,#2b2f36 8px 11px,transparent 11px 13px)}

        .sheet{width:330px;min-height:360px;background:#fff;color:#2b2f36;font-size:7.5px;line-height:1.5;padding:22px 0 18px 22px;box-shadow:-10px 14px 30px rgba(16,24,40,.25)}
        .sheet-pad{padding-right:22px}
        .sheet-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
        .sheet-title{font-size:24px;font-weight:900;color:#2f6fd1;letter-spacing:.04em;line-height:1}
        .sheet-company{text-align:right}
        .sheet-company strong{color:#2f6fd1;font-size:9px}
        .sheet-meta{display:flex;justify-content:space-between;margin:14px 0 10px}
        .sheet-label{font-weight:800;text-transform:uppercase}
        .sheet table{width:100%;border-collapse:collapse}
        .sheet thead th{background:#2f6fd1;color:#fff;text-align:left;padding:5px 4px;font-weight:700}
        .sheet tbody td{padding:5px 4px;border-bottom:1px solid #d9dee6}
        .sheet .num{text-align:right}
        .sheet-totals{margin:8px 22px 0 auto;width:120px}
        .sheet-grand{background:#2f6fd1;color:#fff;font-weight:800;padding:4px}
        .sheet-foot{display:flex;justify-content:space-between;align-items:flex-end;margin-top:16px;gap:10px}
        .sheet-sign{border-top:1px solid #2b2f36;width:80px;text-align:center;padding-top:2px}

        .guide-pill{display:inline-block;background:#e7f8ef;color:#087443;font-size:12px;font-weight:800;border-radius:999px;padding:4px 10px;margin-bottom:10px}
        .guide-steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
        .guide-steps ol{margin:8px 0 14px;padding-left:20px;line-height:1.8}
        .guide-steps h3{margin:0;font-size:15px}
        @media (max-width:520px){
            .guide-stage{height:auto;padding:24px 12px}
            .guide-stage.invoice{justify-content:center;padding-right:12px}
            .sheet{width:100%;padding-right:0}
            .guide-compare tbody th{width:auto}
        }
    </style>

    <div class="guide">
        <p class="muted" style="margin:0 0 20px;max-width:680px">Bills and invoices both show money owed for goods, but they go in opposite directions. This page explains which one to use in CEC Electronic, and when.</p>

        <h2>1. What is the difference?</h2>
        <div class="panel" style="overflow-x:auto">
            <table class="guide-compare">
                <thead><tr><th></th><th>Bill</th><th>Invoice</th></tr></thead>
                <tbody>
                    <tr><th scope="row">In one line</th><td>Shows what has to be paid, or what was just paid, for a purchase.</td><td>A formal request from the seller asking the buyer to pay for an order.</td></tr>
                    <tr><th scope="row">Who makes it</th><td>The shop at the counter (customer bill / receipt), or a supplier who sold to us (supplier bill).</td><td>We make it, as the seller.</td></tr>
                    <tr><th scope="row">Who gets it</th><td>The customer who just bought, or <strong>us</strong> when a supplier bills us.</td><td>Our customer.</td></tr>
                    <tr><th scope="row">Money</th><td>Customer bill: money in, already paid.<br>Supplier bill: <strong>money out</strong>, we must pay.</td><td>Money in, often not paid yet.</td></tr>
                    <tr><th scope="row">Looks like</th><td>A short till slip: items, total, paid, change.</td><td>A full A4 page: invoice number, bill to, items, total, payment details, signature.</td></tr>
                    <tr><th scope="row">In this admin</th><td><a style="color:var(--brand);font-weight:800" href="{{ route('admin.bills.index') }}">Bill</a> menu: <em>Supplier bills</em> and <em>Customer bills</em>.</td><td><a style="color:var(--brand);font-weight:800" href="{{ route('admin.invoices.index') }}">Invoice</a> menu, or <em>Make invoice</em> on a customer bill.</td></tr>
                </tbody>
            </table>
        </div>

        <h2>2. Example</h2>
        <div class="guide-examples">
            <div>
                <div class="guide-stage bill" aria-hidden="true">
                    <div class="slip">
                        <div class="slip-center slip-shop">CEC ELECTRONIC</div>
                        <div class="slip-center">Phnom Penh, Cambodia</div>
                        <div class="slip-center">Tel. 012 220 152</div>
                        <div class="slip-stars">************************</div>
                        <div class="slip-center" style="font-size:13px">CASH RECEIPT</div>
                        <div class="slip-stars">************************</div>
                        <div class="slip-row"><strong>Description</strong><strong>Price</strong></div>
                        <div class="slip-row"><span>Wireless mouse</span><span>12.00</span></div>
                        <div class="slip-row"><span>USB-C cable x2</span><span>7.00</span></div>
                        <div class="slip-row"><span>Power bank</span><span>25.00</span></div>
                        <div class="slip-stars">************************</div>
                        <div class="slip-row slip-total"><span>Total</span><span>44.00</span></div>
                        <div class="slip-row"><span>Cash</span><span>50.00</span></div>
                        <div class="slip-row"><span>Change</span><span>6.00</span></div>
                        <div class="slip-stars">************************</div>
                        <div class="slip-center" style="font-weight:800">THANK YOU!</div>
                        <div class="slip-barcode"></div>
                    </div>
                </div>
                <p class="guide-caption"><strong>Bill</strong><span>A customer buys products and gets a bill showing the amount, how they paid and their change.</span></p>
            </div>

            <div>
                <div class="guide-stage invoice" aria-hidden="true">
                    <div class="sheet">
                        <div class="sheet-pad">
                            <div class="sheet-top">
                                <div class="sheet-title">INVOICE</div>
                                <div class="sheet-company"><strong>CEC Electronic</strong><br>Phnom Penh, Cambodia<br>012 220 152</div>
                            </div>
                            <div class="sheet-meta">
                                <div><span class="sheet-label">Invoice to:</span><br><strong>Customer name</strong><br>Street, City<br>0xx xxx xxx</div>
                                <div style="text-align:right">Invoice # INV-20261109-1024<br>Date: 09 Nov 2026</div>
                            </div>
                        </div>
                        <table>
                            <thead><tr><th>#</th><th>Item description</th><th class="num">Price</th><th class="num">Qty</th><th class="num" style="padding-right:22px">Total</th></tr></thead>
                            <tbody>
                                <tr><td>1</td><td>Laptop 14"</td><td class="num">$520.00</td><td class="num">1</td><td class="num" style="padding-right:22px">$520.00</td></tr>
                                <tr><td>2</td><td>Wireless mouse</td><td class="num">$12.00</td><td class="num">2</td><td class="num" style="padding-right:22px">$24.00</td></tr>
                                <tr><td>3</td><td>Laptop bag</td><td class="num">$18.00</td><td class="num">1</td><td class="num" style="padding-right:22px">$18.00</td></tr>
                                <tr><td>4</td><td>Delivery</td><td class="num">$2.00</td><td class="num">1</td><td class="num" style="padding-right:22px">$2.00</td></tr>
                            </tbody>
                        </table>
                        <div class="sheet-totals">
                            <div class="slip-row" style="padding:3px 4px"><span>Sub total</span><span>$564.00</span></div>
                            <div class="slip-row sheet-grand"><span>Total</span><span>$564.00</span></div>
                        </div>
                        <div class="sheet-pad sheet-foot">
                            <div><span class="sheet-label">Payment info</span><br>KHQR (Bakong) or cash at store<br><span class="sheet-label">Terms</span><br>Please pay within 7 days.</div>
                            <div class="sheet-sign">Signature</div>
                        </div>
                    </div>
                </div>
                <p class="guide-caption"><strong>Invoice</strong><span>We send an invoice asking the customer to pay for an order.</span></p>
            </div>
        </div>

        <h2>3. Which one should you use?</h2>
        <span class="guide-pill">Recommendation</span>
        <p style="margin:0 0 6px">Use an <strong>Invoice</strong> for online orders, and a <strong>Bill</strong> for supplier purchases and as proof that a customer paid.</p>
        <p class="muted" style="margin:0 0 16px">Why? An invoice has an invoice number, the customer's details and payment terms. That makes it the professional choice for online shopping and for company customers who need a document for their accounts.</p>

        <div class="guide-steps">
            <div class="panel" style="padding:16px">
                <h3>Selling to a customer</h3>
                <ol>
                    <li>The customer places a sale order.</li>
                    <li>Click <strong>Make invoice</strong> (or <strong>Create invoice</strong> on the order) and send it to the customer.</li>
                    <li>The customer pays by KHQR or cash.</li>
                    <li>Give them the <strong>receipt</strong> (customer bill) as proof of payment.</li>
                </ol>
                <a class="btn" href="{{ route('admin.bills.index', ['tab' => 'customers']) }}">Open customer bills</a>
            </div>
            <div class="panel" style="padding:16px">
                <h3>Buying from a supplier</h3>
                <ol>
                    <li>Create a purchase order for the supplier.</li>
                    <li>When the goods are received, click <strong>Create bill</strong> on the purchase order.</li>
                    <li>Pay the supplier before the due date.</li>
                    <li>Click <strong>Mark as paid</strong> on the bill.</li>
                </ol>
                <a class="btn" href="{{ route('admin.bills.index') }}">Open supplier bills</a>
            </div>
        </div>
    </div>
@endsection
