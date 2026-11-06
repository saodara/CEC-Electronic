<tr>
    <td>
        <select name="items[{{ $index }}][product_id]" data-product required>
            <option value="">Choose a product</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" data-cost="{{ $product->cost_price }}" @selected((string) $row['product_id'] === (string) $product->id)>{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
            @endforeach
        </select>
        @error("items.$index.product_id") <span class="error">{{ $message }}</span> @enderror
    </td>
    <td>
        <input name="items[{{ $index }}][quantity]" type="number" min="1" step="1" value="{{ $row['quantity'] }}" data-quantity required>
        @error("items.$index.quantity") <span class="error">{{ $message }}</span> @enderror
    </td>
    <td>
        <input name="items[{{ $index }}][unit_cost]" type="number" min="0" step="0.01" value="{{ $row['unit_cost'] }}" data-unit-cost required>
        @error("items.$index.unit_cost") <span class="error">{{ $message }}</span> @enderror
    </td>
    <td data-line>$0.00</td>
    <td><button class="btn danger" type="button" data-remove aria-label="Remove item">&times;</button></td>
</tr>
