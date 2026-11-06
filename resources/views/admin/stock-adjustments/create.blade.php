@extends('admin.layout')

@section('title', 'New Stock Adjustment - CEC Electronic Admin')
@section('heading', 'New Stock Adjustment')

@section('content')
    <form class="panel form" action="{{ route('admin.stock-adjustments.store') }}" method="post">
        @csrf
        <div class="form-grid">
            <div class="field full">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Choose a product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }} ({{ $product->stock_quantity }} in stock)</option>
                    @endforeach
                </select>
                @error('product_id') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label for="direction">Add or remove</label>
                <select id="direction" name="direction" required>
                    <option value="add" @selected(old('direction') === 'add')>Add to stock</option>
                    <option value="remove" @selected(old('direction') === 'remove')>Remove from stock</option>
                </select>
            </div>
            <div class="field">
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity', 1) }}" required>
                @error('quantity') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="field full">
                <label for="reason">Reason</label>
                <select id="reason" name="reason" required>
                    @foreach(\App\Models\StockAdjustment::REASONS as $reason)
                        <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ $reason }}</option>
                    @endforeach
                </select>
                @error('reason') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="field full">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" style="min-height:90px">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:16px">
            <button class="btn" type="submit">Save adjustment</button>
            <a class="btn secondary" href="{{ route('admin.stock-adjustments.index') }}">Cancel</a>
        </div>
    </form>
@endsection
