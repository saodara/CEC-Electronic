@extends('admin.layout')

@section('title', 'Categories - CEC Electronic Admin')
@section('heading', 'Categories')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Category structure</h2>
            <p class="muted" style="margin:6px 0 0">Organize products for storefront browsing and filters.</p>
        </div>
        <a class="btn" href="{{ route('admin.categories.create') }}">Add category</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td class="muted">{{ $category->slug }}</td>
                        <td><span class="status">{{ $category->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td>{{ $category->sort_order }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="post" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $categories->links() }}</div>
@endsection
