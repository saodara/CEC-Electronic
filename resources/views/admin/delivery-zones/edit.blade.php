@extends('admin.layout')

@section('title', 'Edit Delivery - CEC Electronic Admin')
@section('heading', 'Edit Delivery')

@section('content')
    <form class="panel form" action="{{ route('admin.delivery-zones.update', $zone) }}" method="post">
        @method('PUT')
        @include('admin.delivery-zones._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
