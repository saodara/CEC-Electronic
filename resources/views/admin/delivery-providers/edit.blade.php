@extends('admin.layout')

@section('title', 'Edit Delivery Provider - CEC Electronic Admin')
@section('heading', 'Edit Delivery Provider')

@section('content')
    <form class="panel form" action="{{ route('admin.delivery-providers.update', $provider) }}" method="post">
        @method('PUT')
        @include('admin.delivery-providers._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
