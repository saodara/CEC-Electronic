@extends('admin.layout')

@section('title', 'Add Supplier - CEC Electronic Admin')
@section('heading', 'Add Supplier')

@section('content')
    <form class="panel form" action="{{ route('admin.suppliers.store') }}" method="post">
        @include('admin.suppliers._form', ['buttonText' => 'Create supplier'])
    </form>
@endsection
