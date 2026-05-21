@extends('admin.layout')

@section('title', 'Add Delivery Provider - CEC Electronic Admin')
@section('heading', 'Add Delivery Provider')

@section('content')
    <form class="panel form" action="{{ route('admin.delivery-providers.store') }}" method="post">
        @include('admin.delivery-providers._form', ['buttonText' => 'Create provider'])
    </form>
@endsection
