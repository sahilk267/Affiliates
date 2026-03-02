@php($title = 'Home')
@extends('layouts.guest')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4">Earn Points on Every Purchase</h1>
            <p class="text-xl mb-8">Shop from your favorite brands and earn cashback points. Redeem for cash or gifts!</p>
            @auth
                <a href="{{ route('products.index') }}" class="inline-block bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100">
                    Browse Products
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-block bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100">
                    Get Started
                </a>
            @endauth
        </div>
    </div>
</div>

<!-- Featured Products -->
@if(isset($featuredProducts) && $featuredProducts->count() > 0)
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-2xl font-bold mb-6">Featured Products (Highest Commission)</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($featuredProducts as $product)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <a href="{{ route('products.show', $product->id) }}">
                @if($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                @else
                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                    <span class="text-gray-400">No Image</span>
                </div>
                @endif
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">{{ $product->name }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ $product->brand ?? 'N/A' }}</p>
                    @if($product->max_commission_rate > 0)
                    <div class="flex items-center justify-between">
                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded">
                            Up to {{ number_format($product->max_commission_rate, 1) }}% Commission
                        </span>
                    </div>
                    @endif
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Latest Products -->
@if(isset($latestProducts) && $latestProducts->count() > 0)
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h2 class="text-2xl font-bold mb-6">Latest Products</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($latestProducts as $product)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <a href="{{ route('products.show', $product->id) }}">
                @if($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                @else
                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                    <span class="text-gray-400">No Image</span>
                </div>
                @endif
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">{{ $product->name }}</h3>
                    <p class="text-sm text-gray-500 mb-2">{{ $product->brand ?? 'N/A' }}</p>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- How It Works -->
<div class="bg-white border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-2xl font-bold mb-8 text-center">How It Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="bg-indigo-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-indigo-600">1</span>
                </div>
                <h3 class="font-semibold mb-2">Browse Products</h3>
                <p class="text-gray-600">Find products from multiple platforms with the best prices and commissions.</p>
            </div>
            <div class="text-center">
                <div class="bg-indigo-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-indigo-600">2</span>
                </div>
                <h3 class="font-semibold mb-2">Make Purchase</h3>
                <p class="text-gray-600">Click "Buy with me" and complete your purchase on the merchant site.</p>
            </div>
            <div class="text-center">
                <div class="bg-indigo-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-indigo-600">3</span>
                </div>
                <h3 class="font-semibold mb-2">Earn Points</h3>
                <p class="text-gray-600">Get cashback points automatically. Redeem for cash or choose from our gift catalog!</p>
            </div>
        </div>
    </div>
</div>
@endsection

