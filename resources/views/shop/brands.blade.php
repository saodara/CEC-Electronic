@extends('shop.layout')

@section('title', 'All Brands - CEC Electronic')

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('shop.home') }}">Home</a>
        <span>/</span>
        <span>All Brands</span>
    </nav>

    <section class="panel brand-page-hero">
        <div>
            <h1>All Brands</h1>
            <p>Browse official laptops, desktops, monitors, printers, components, and accessories by brand. CEC Electronic keeps this page clean so customers can find products fast.</p>
        </div>
        <div class="brand-page-stats">
            <div>
                <strong>{{ $brands->count() }}</strong>
                <span>Available brands</span>
            </div>
            <div>
                <strong>{{ number_format($brands->sum('products_count')) }}</strong>
                <span>Matched products</span>
            </div>
        </div>
    </section>

    <div class="section-head">
        <div>
            <h2>Brand directory</h2>
            <p>Select a brand to view matching products.</p>
        </div>
        <a class="btn secondary" href="{{ route('shop.home') }}">Back home</a>
    </div>

    <section class="brand-grid">
        @foreach($brands as $brand)
            <a class="panel brand-card" href="{{ route('shop.brand', $brand['slug']) }}">
                <span class="brand-logo-box">
                    @if($brand['logo'])
                        <img src="{{ asset($brand['logo']) }}" alt="{{ $brand['name'] }} logo">
                    @else
                        <span class="brand-initials">{{ $brand['initials'] }}</span>
                    @endif
                </span>
                <strong>{{ $brand['name'] }}</strong>
                <span>{{ $brand['products_count'] }} products</span>
            </a>
        @endforeach
    </section>
@endsection
