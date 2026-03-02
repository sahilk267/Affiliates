@php($title = $product->name)
@extends('layouts.guest')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li><a href="{{ route('home') }}" class="hover:text-gray-700">Home</a></li>
            <li>/</li>
            <li><a href="{{ route('products.index') }}" class="hover:text-gray-700">Products</a></li>
            <li>/</li>
            <li class="text-gray-900">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Product Image -->
        <div>
            @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full rounded-lg shadow-sm border border-gray-200">
            @else
            <div class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center">
                <span class="text-gray-400">No Image Available</span>
            </div>
            @endif
        </div>

        <!-- Product Details -->
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>
            
            @if($product->brand)
            <p class="text-lg text-gray-600 mb-4">Brand: {{ $product->brand }}</p>
            @endif

            @if($product->description)
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">Description</h2>
                <p class="text-gray-700">{{ $product->description }}</p>
            </div>
            @endif

            <!-- Commission Badge -->
            @if($product->max_commission_rate > 0)
            <div class="mb-6">
                <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-green-100 text-green-800">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                    Best Commission: {{ number_format($product->max_commission_rate, 1) }}%
                </span>
            </div>
            @endif
        </div>
    </div>

    <!-- Price Comparison & Buy Options -->
    @if($priceComparison && $priceComparison->count() > 0)
    <div class="mt-12">
        <h2 class="text-2xl font-bold mb-6">Buy with Me - Compare Prices & Commissions</h2>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Availability</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($priceComparison as $link)
                        <tr class="hover:bg-gray-50 {{ $link['is_best_price'] ? 'bg-green-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($link['program']->logo_url)
                                    <img src="{{ $link['program']->logo_url }}" alt="{{ $link['program']->name }}" class="h-8 w-8 rounded mr-3">
                                    @endif
                                    <span class="font-medium text-gray-900">{{ $link['program']->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-semibold text-gray-900">₹{{ number_format($link['price'], 2) }}</span>
                                @if($link['is_best_price'])
                                <span class="ml-2 text-xs text-green-600 font-semibold">Best Price</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($link['commission_rate'] > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ number_format($link['commission_rate'], 1) }}%
                                </span>
                                @if($link['commission_rate'] == $product->max_commission_rate)
                                <span class="ml-2 text-xs text-yellow-600 font-semibold">⭐ Best Commission</span>
                                @endif
                                @else
                                <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">{{ $link['availability'] ?? 'In Stock' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('products.buy', ['productId' => $product->id, 'programId' => $link['program']->id]) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    Buy Now
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="mt-12 bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <p class="text-gray-500">No purchase options available for this product.</p>
    </div>
    @endif
</div>
@endsection

