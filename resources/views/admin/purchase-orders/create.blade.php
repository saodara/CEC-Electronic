@extends('admin.layout')

@section('title', 'New Purchase Order - CEC Electronic Admin')
@section('heading', 'New Purchase Order')

@section('content')
    <form class="panel form" style="max-width:none" action="{{ route('admin.purchase-orders.store') }}" method="post">
        @include('admin.purchase-orders._form', ['buttonText' => 'Create purchase order'])
    </form>
@endsection
