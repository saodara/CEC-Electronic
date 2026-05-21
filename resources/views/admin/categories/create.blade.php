@extends('admin.layout')

@section('title', 'Add Category - CEC Electronic Admin')
@section('heading', 'Add Category')

@section('content')
    <form class="panel form" action="{{ route('admin.categories.store') }}" method="post" enctype="multipart/form-data">
        @include('admin.categories._form', ['buttonText' => 'Create category'])
    </form>
@endsection
