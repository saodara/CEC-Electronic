@csrf

<div class="form-grid">
    <div class="field">
        <label for="name">Supplier name</label>
        <input id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
        @error('name') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="field">
        <label for="company_name">Company name</label>
        <input id="company_name" name="company_name" value="{{ old('company_name', $supplier->company_name) }}">
    </div>

    <div class="field">
        <label for="contact_person">Contact person</label>
        <input id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}">
    </div>

    <div class="field">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}">
    </div>

    <div class="field">
        <label for="website">Website</label>
        <input id="website" name="website" type="url" value="{{ old('website', $supplier->website) }}">
    </div>

    <div class="field full">
        <label for="address">Address</label>
        <textarea id="address" name="address">{{ old('address', $supplier->address) }}</textarea>
    </div>

    <div class="field">
        <label for="payment_terms">Payment terms</label>
        <input id="payment_terms" name="payment_terms" value="{{ old('payment_terms', $supplier->payment_terms) }}" placeholder="Net 30, prepaid, COD">
    </div>

    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true))> Active</label>

    <div class="field full">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes">{{ old('notes', $supplier->notes) }}</textarea>
    </div>
</div>

<div style="display:flex;gap:10px;margin-top:18px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.suppliers.index') }}">Cancel</a>
</div>
