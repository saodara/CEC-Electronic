@extends('admin.layout')

@section('title', 'Edit Brand - CEC Electronic Admin')
@section('heading', 'Edit Brand')

@section('content')
    <form class="panel form" action="{{ route('admin.brands.update', $brand) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.brands._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
