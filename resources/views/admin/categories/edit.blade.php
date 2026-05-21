@extends('admin.layout')

@section('title', 'Edit Category - CEC Electronic Admin')
@section('heading', 'Edit Category')

@section('content')
    <form class="panel form" action="{{ route('admin.categories.update', $category) }}" method="post" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.categories._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
