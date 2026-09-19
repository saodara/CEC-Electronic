@csrf

<div class="form-grid">
    <div class="field full">
        <label for="name">Brand name</label>
        <input id="name" name="name" value="{{ old('name', $brand->name) }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="sort_order">Sort order</label>
        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $brand->sort_order ?? 0) }}">
        @error('sort_order') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="logo">Brand logo</label>
        @if($brand->logo_url)
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                <img class="thumb" src="{{ $brand->logo_url }}" alt="{{ $brand->name ?: 'Brand logo' }}" style="object-fit:contain">
                <span class="muted">Upload a new JPG, PNG, or WEBP file to replace this logo.</span>
            </div>
        @endif
        <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        @error('logo') <span class="error">{{ $message }}</span> @enderror
    </div>

    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active ?? true))> Active</label>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.brands.index') }}">Cancel</a>
</div>
