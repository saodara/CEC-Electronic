@csrf

@php
    $rows = old('items', $purchaseOrder->relationLoaded('items')
        ? $purchaseOrder->items->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_cost' => $item->unit_cost])->all()
        : [['product_id' => '', 'quantity' => 1, 'unit_cost' => '']]);
@endphp

<div class="form-grid">
    <div class="field">
        <label for="supplier_id">Supplier</label>
        <select id="supplier_id" name="supplier_id" required>
            <option value="">Choose a supplier</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $purchaseOrder->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
        @error('supplier_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="expected_date">Expected date</label>
        <input id="expected_date" name="expected_date" type="date" value="{{ old('expected_date', $purchaseOrder->expected_date?->toDateString()) }}">
        @error('expected_date') <span class="error">{{ $message }}</span> @enderror
    </div>
</div>

<h3 style="margin:20px 0 10px">Items</h3>
@error('items') <p class="error">{{ $message }}</p> @enderror
<div style="overflow:auto">
    <table>
        <thead>
            <tr><th style="min-width:280px">Product</th><th style="width:110px">Quantity</th><th style="width:140px">Unit cost ($)</th><th style="width:120px">Line total</th><th style="width:60px"></th></tr>
        </thead>
        <tbody id="poItems">
            @foreach($rows as $index => $row)
                @include('admin.purchase-orders._item_row', ['index' => $index, 'row' => $row])
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="3" style="text-align:right"><strong>Total</strong></td><td colspan="2"><strong id="poTotal">$0.00</strong></td></tr>
        </tfoot>
    </table>
</div>
<button class="btn secondary" style="margin-top:10px" type="button" id="poAddItem">+ Add item</button>

<template id="poItemTemplate">
    @include('admin.purchase-orders._item_row', ['index' => '__INDEX__', 'row' => ['product_id' => '', 'quantity' => 1, 'unit_cost' => '']])
</template>

<div class="field" style="margin-top:16px">
    <label for="notes">Notes</label>
    <textarea id="notes" name="notes" style="min-height:90px">{{ old('notes', $purchaseOrder->notes) }}</textarea>
</div>

<div style="display:flex;gap:10px;margin-top:16px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.purchase-orders.index') }}">Cancel</a>
</div>

<script>
    (function () {
        var body = document.getElementById('poItems');
        var template = document.getElementById('poItemTemplate').innerHTML;
        var next = {{ count($rows) }};

        function refresh() {
            var total = 0;
            body.querySelectorAll('tr').forEach(function (row) {
                var quantity = parseFloat(row.querySelector('[data-quantity]').value) || 0;
                var cost = parseFloat(row.querySelector('[data-unit-cost]').value) || 0;
                total += quantity * cost;
                row.querySelector('[data-line]').textContent = '$' + (quantity * cost).toFixed(2);
            });
            document.getElementById('poTotal').textContent = '$' + total.toFixed(2);
        }

        document.getElementById('poAddItem').addEventListener('click', function () {
            body.insertAdjacentHTML('beforeend', template.replace(/__INDEX__/g, next++));
            refresh();
        });

        body.addEventListener('input', refresh);

        body.addEventListener('change', function (event) {
            if (! event.target.matches('[data-product]')) return;
            var cost = event.target.selectedOptions[0].dataset.cost;
            var input = event.target.closest('tr').querySelector('[data-unit-cost]');
            if (cost && ! input.value) input.value = cost;
            refresh();
        });

        body.addEventListener('click', function (event) {
            if (! event.target.matches('[data-remove]')) return;
            if (body.querySelectorAll('tr').length > 1) event.target.closest('tr').remove();
            refresh();
        });

        refresh();
    })();
</script>
