@extends('admin.layout')

@section('title', 'Add Delivery Zone - CEC Electronic Admin')
@section('heading', 'Add Delivery Zone')

@section('content')
    <form class="panel form" action="{{ route('admin.delivery-zones.store') }}" method="post">
        @include('admin.delivery-zones._form', ['buttonText' => 'Create zone'])
    </form>
@endsection
