@extends('admin.layout')

@section('title', 'Add New Delivery - CEC Electronic Admin')
@section('heading', 'Add New Delivery')

@section('content')
    <form class="panel form" action="{{ route('admin.delivery-zones.store') }}" method="post">
        @include('admin.delivery-zones._form', ['buttonText' => 'Create delivery'])
    </form>
@endsection
