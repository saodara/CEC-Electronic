@extends('admin.layout')

@section('title', 'Add Expense - CEC Electronic Admin')
@section('heading', 'Add Expense')

@section('content')
    <form class="panel form" action="{{ route('admin.expenses.store') }}" method="post">
        @include('admin.expenses._form', ['buttonText' => 'Add expense'])
    </form>
@endsection
