@extends('admin.layout')

@section('title', 'Expenses - CEC Electronic Admin')
@section('heading', 'Expenses')

@section('content')
    <div class="toolbar">
        <div>
            <h2 style="margin:0">Expenses</h2>
            <p class="muted" style="margin:6px 0 0">Running costs like rent, salaries and utilities. Paying suppliers for stock goes under Bills.</p>
        </div>
        <a class="btn" href="{{ route('admin.expenses.create') }}">Add expense</a>
    </div>

    <div class="panel" style="overflow:hidden">
        <table>
            <thead><tr><th>Date</th><th>Expense</th><th>Category</th><th>Amount</th><th style="text-align:right">Actions</th></tr></thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->spent_on->format('d M Y') }}</td>
                        <td><strong>{{ $expense->title }}</strong><div class="muted">{{ $expense->notes }}</div></td>
                        <td>{{ $expense->category }}</td>
                        <td>${{ number_format($expense->amount, 2) }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.expenses.edit', $expense) }}">Edit</a>
                                <form action="{{ route('admin.expenses.destroy', $expense) }}" method="post" onsubmit="return confirm('Delete this expense?')">@csrf @method('DELETE')<button class="btn danger" type="submit">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No expenses yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $expenses->links() }}</div>
@endsection
