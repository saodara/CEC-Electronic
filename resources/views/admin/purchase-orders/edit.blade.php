@extends('admin.layout')

@section('title', 'Edit Purchase Order - CEC Electronic Admin')
@section('heading', 'Edit '.$purchaseOrder->po_number)

@section('content')
    <form class="panel form" style="max-width:none" action="{{ route('admin.purchase-orders.update', $purchaseOrder) }}" method="post">
        @method('PUT')
        @include('admin.purchase-orders._form', ['buttonText' => 'Save changes'])
    </form>
@endsection
