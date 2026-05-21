@csrf

<div class="form-grid">
    <div class="field full">
        <label for="name">Product name</label>
        <input id="name" name="name" value="{{ old('name', $product->name) }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category_id">
            <option value="">Uncategorized</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="price">Price USD</label>
        <input id="price" name="price" type="number" min="0" step="1" value="{{ old('price', $product->price ?? 0) }}" required>
        @error('price') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="supplier_id">Supplier</label>
        <select id="supplier_id" name="supplier_id">
            <option value="">No supplier</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $product->supplier_id) === $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
        @error('supplier_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="sku">SKU</label>
        <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
        @error('sku') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="stock_quantity">Stock quantity</label>
        <input id="stock_quantity" name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}">
        @error('stock_quantity') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="compare_at_price">Compare at price</label>
        <input id="compare_at_price" name="compare_at_price" type="number" min="0" value="{{ old('compare_at_price', $product->compare_at_price) }}">
        @error('compare_at_price') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="cost_price">Cost price</label>
        <input id="cost_price" name="cost_price" type="number" min="0" value="{{ old('cost_price', $product->cost_price) }}">
        @error('cost_price') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="image">Product image</label>
        @if($product->image)
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                <img class="thumb" src="{{ $product->image_url }}" alt="{{ $product->name ?: 'Product image' }}">
                <span class="muted">Upload a new JPG, PNG, or WEBP file to replace this image.</span>
            </div>
        @endif
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        @error('image') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="description">Description and specs</label>
        <textarea id="description" name="description">{{ old('description', $product->description) }}</textarea>
        @error('description') <span class="error">{{ $message }}</span> @enderror
    </div>

    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Active</label>
    <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))> Featured</label>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.products.index') }}">Cancel</a>
</div>
