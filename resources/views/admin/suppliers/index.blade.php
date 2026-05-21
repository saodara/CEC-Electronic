@extends('admin.layout')

@section('title', 'Suppliers - CEC Electronic Admin')
@section('heading', 'Suppliers')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Supplier management</h2>
            <p class="muted" style="margin:6px 0 0">Track vendors, contact details, terms, and product sourcing.</p>
        </div>
        <a class="btn" href="{{ route('admin.suppliers.create') }}">Add supplier</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Contact</th>
                    <th>Terms</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td><strong>{{ $supplier->name }}</strong><div class="muted">{{ $supplier->company_name }}</div></td>
                        <td>{{ $supplier->phone }}<div class="muted">{{ $supplier->email }}</div></td>
                        <td>{{ $supplier->payment_terms ?: 'Not set' }}</td>
                        <td>{{ $supplier->products_count }}</td>
                        <td><span class="status">{{ $supplier->is_active ? 'Active' : 'Hidden' }}</span></td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.suppliers.edit', $supplier) }}">Edit</a>
                                <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="post" onsubmit="return confirm('Delete this supplier?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No suppliers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $suppliers->links() }}</div>
@endsection
