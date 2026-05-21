@extends('admin.layout')

@section('title', 'Products - CEC Electronic Admin')
@section('heading', 'Products')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Product catalog</h2>
            <p class="muted" style="margin:6px 0 0">Manage names, categories, prices, images, and specs shown on the storefront.</p>
        </div>
        <a class="btn" href="{{ route('admin.products.create') }}">Add product</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="product-cell">
                                <img class="thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                <div>
                                    <strong>{{ $product->name }}</strong>
                                    <div class="muted">{{ $product->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->display_category ?: 'Uncategorized' }}</td>
                        <td>{{ $product->supplier?->name ?: 'No supplier' }}</td>
                        <td>${{ number_format($product->price) }}</td>
                        <td><span class="status">In stock</span></td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('shop.product', $product->slug) }}">View</a>
                                <a class="btn secondary" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="post" onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No products yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $products->links() }}
    </div>
@endsection
