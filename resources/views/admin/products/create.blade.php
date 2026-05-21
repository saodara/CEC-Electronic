@extends('admin.layout')

@section('title', 'Add Product - CEC Electronic Admin')
@section('heading', 'Add Product')

@section('content')
    <form class="panel form" action="{{ route('admin.products.store') }}" method="post" enctype="multipart/form-data">
        @include('admin.products._form', ['buttonText' => 'Create product'])
    </form>
@endsection
