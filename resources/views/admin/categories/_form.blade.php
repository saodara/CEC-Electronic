@csrf

<div class="form-grid">
    <div class="field full">
        <label for="name">Category name</label>
        <input id="name" name="name" value="{{ old('name', $category->name) }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="parent_id">Parent category</label>
        <select id="parent_id" name="parent_id">
            <option value="">None</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>{{ $parent->name }}</option>
            @endforeach
        </select>
        @error('parent_id') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="sort_order">Sort order</label>
        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
        @error('sort_order') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="image">Category image</label>
        @if($category->image)
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                <img class="thumb" src="{{ $category->image_url }}" alt="{{ $category->name ?: 'Category image' }}">
                <span class="muted">Upload a new JPG, PNG, or WEBP file to replace this image.</span>
            </div>
        @endif
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        @error('image') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field full">
        <label for="description">Description</label>
        <textarea id="description" name="description">{{ old('description', $category->description) }}</textarea>
        @error('description') <span class="error">{{ $message }}</span> @enderror
    </div>

    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Active</label>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.categories.index') }}">Cancel</a>
</div>
