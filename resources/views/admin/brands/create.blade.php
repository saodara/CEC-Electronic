@extends('admin.layout')

@section('title', 'Add Brand - CEC Electronic Admin')
@section('heading', 'Add Brand')

@section('content')
    <form class="panel form" action="{{ route('admin.brands.store') }}" method="post" enctype="multipart/form-data">
        @include('admin.brands._form', ['buttonText' => 'Create brand'])
    </form>
@endsection
