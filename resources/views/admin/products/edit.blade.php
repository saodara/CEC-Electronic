@extends('admin.layout')

@section('title', 'Edit Product - CEC Electronic Admin')
@section('heading', 'Edit Product')

@section('content')
    <form class="panel form" action="{{ route('admin.products.update', $product) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.products._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
