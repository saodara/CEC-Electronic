@csrf
<div class="form-grid">
    <div class="field full">
        <label for="title">Expense</label>
        <input id="title" name="title" value="{{ old('title', $expense->title) }}" required maxlength="255">
        @error('title') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category" required>
            @foreach(\App\Models\Expense::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(old('category', $expense->category) === $category)>{{ $category }}</option>
            @endforeach
        </select>
        @error('category') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field">
        <label for="amount">Amount ($)</label>
        <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', $expense->amount) }}" required>
        @error('amount') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field">
        <label for="spent_on">Date</label>
        <input id="spent_on" name="spent_on" type="date" value="{{ old('spent_on', $expense->spent_on?->toDateString()) }}" required>
        @error('spent_on') <span class="error">{{ $message }}</span> @enderror
    </div>
    <div class="field full">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" style="min-height:90px">{{ old('notes', $expense->notes) }}</textarea>
    </div>
</div>
<div style="display:flex;gap:10px;margin-top:16px">
    <button class="btn" type="submit">{{ $buttonText }}</button>
    <a class="btn secondary" href="{{ route('admin.expenses.index') }}">Cancel</a>
</div>
