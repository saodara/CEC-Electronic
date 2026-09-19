@extends('admin.layout')

@section('title', 'Brands - CEC Electronic Admin')
@section('heading', 'Brands')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Product brands</h2>
            <p class="muted" style="margin:6px 0 0">Assign brands to products so customers can browse and filter by brand.</p>
        </div>
        <a class="btn" href="{{ route('admin.brands.create') }}">Add brand</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Brand</th>
                    <th>Slug</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($brands as $brand)
                    <tr>
                        <td>
                            <div class="product-cell">
                                @if($brand->logo_url)
                                    <img class="thumb" src="{{ $brand->logo_url }}" alt="{{ $brand->name }} logo" style="object-fit:contain">
                                @endif
                                <strong>{{ $brand->name }}</strong>
                            </div>
                        </td>
                        <td class="muted">{{ $brand->slug }}</td>
                        <td>{{ $brand->products_count }}</td>
                        <td><span class="status">{{ $brand->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td>{{ $brand->sort_order }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.products.index', ['brand' => $brand->id]) }}">Products</a>
                                <a class="btn secondary" href="{{ route('admin.brands.edit', $brand) }}">Edit</a>
                                <form action="{{ route('admin.brands.destroy', $brand) }}" method="post" onsubmit="return confirm('Delete this brand? Its products will be kept without a brand.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No brands yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $brands->links() }}</div>
@endsection
