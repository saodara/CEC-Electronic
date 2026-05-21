@csrf

<div class="form-grid">
    <div class="field">
        <label for="name">Zone name</label>
        <input id="name" name="name" value="{{ old('name', $zone->name) }}" required>
    </div>
    <div class="field">
        <label for="city">City</label>
        <input id="city" name="city" value="{{ old('city', $zone->city) }}">
    </div>
    <div class="field">
        <label for="province">Province</label>
        <input id="province" name="province" value="{{ old('province', $zone->province) }}">
    </div>
    <div class="field">
        <label for="delivery_fee">Delivery fee USD</label>
        <input id="delivery_fee" name="delivery_fee" type="number" min="0" value="{{ old('delivery_fee', $zone->delivery_fee ?? 0) }}">
    </div>
    <div class="field">
        <label for="free_delivery_minimum">Free delivery minimum</label>
        <input id="free_delivery_minimum" name="free_delivery_minimum" type="number" min="0" value="{{ old('free_delivery_minimum', $zone->free_delivery_minimum) }}">
    </div>
    <div class="field">
        <label for="estimated_days">Estimated days</label>
        <input id="estimated_days" name="estimated_days" type="number" min="1" value="{{ old('estimated_days', $zone->estimated_days ?? 1) }}">
    </div>
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $zone->is_active ?? true))> Active</label>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.delivery-zones.index') }}">Cancel</a>
</div>
