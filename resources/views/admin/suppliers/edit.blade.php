@extends('admin.layout')

@section('title', 'Edit Supplier - CEC Electronic Admin')
@section('heading', 'Edit Supplier')

@section('content')
    <form class="panel form" action="{{ route('admin.suppliers.update', $supplier) }}" method="post">
        @method('PUT')
        @include('admin.suppliers._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
