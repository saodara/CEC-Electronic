@csrf

<div class="form-grid">
    <div class="field">
        <label for="name">Provider name</label>
        <input id="name" name="name" value="{{ old('name', $provider->name) }}" required>
    </div>
    <div class="field">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" value="{{ old('phone', $provider->phone) }}">
    </div>
    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $provider->email) }}">
    </div>
    <div class="field">
        <label for="base_fee">Base fee USD</label>
        <input id="base_fee" name="base_fee" type="number" min="0" value="{{ old('base_fee', $provider->base_fee ?? 0) }}">
    </div>
    <div class="field full">
        <label for="tracking_url">Tracking URL</label>
        <input id="tracking_url" name="tracking_url" type="url" value="{{ old('tracking_url', $provider->tracking_url) }}">
    </div>
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $provider->is_active ?? true))> Active</label>
    <div class="field full">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes">{{ old('notes', $provider->notes) }}</textarea>
    </div>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.delivery-providers.index') }}">Cancel</a>
</div>
