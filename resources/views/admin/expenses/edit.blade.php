@extends('admin.layout')

@section('title', 'Edit Expense - CEC Electronic Admin')
@section('heading', 'Edit Expense')

@section('content')
    <form class="panel form" action="{{ route('admin.expenses.update', $expense) }}" method="post">
        @method('PUT')
        @include('admin.expenses._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
