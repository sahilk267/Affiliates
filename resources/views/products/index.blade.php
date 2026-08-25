@php($title = 'Compare Products')
@extends('layouts.guest')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <div class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
            API-independent preview
        </div>
        <h1 class="mt-3 text-3xl font-bold text-gray-900">Compare products across merchants</h1>
        <p class="mt-2 max-w-3xl text-gray-600">Search a product, review source-labelled offer observations, and open the external merchant site. Local fixture data is clearly labelled; live partner prices and availability are not claimed until approved APIs or feeds are connected.</p>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="md:col-span-2">
                <label for="search" class="mb-1 block text-sm font-medium text-gray-700">Search</label>
                <input id="search" type="text" name="search" value="{{ request('search') }}" placeholder="Search products, brands, or categories" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label for="category" class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                <input id="category" type="text" name="category" value="{{ request('category') }}" placeholder="Optional category" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label for="sort" class="mb-1 block text-sm font-medium text-gray-700">Sort</label>
                <select id="sort" name="sort" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                    <option value="price" {{ request('sort', 'price') === 'price' ? 'selected' : '' }}>Lowest known observed price</option>
                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name A–Z</option>
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Recently added</option>
                </select>
            </div>
            <div class="md:col-span-4 flex items-center justify-between gap-4">
                <p class="text-xs text-gray-500">Prices and availability are observations with timestamps, not a purchase guarantee.</p>
                <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2 font-medium text-white hover:bg-indigo-700">Search</button>
            </div>
        </form>
    </div>

    @if($products->count() > 0)
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach($products as $product)
        <article class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
            <a href="{{ route('products.show', $product->id) }}" class="block">
                @if($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-48 w-full object-cover">
                @else
                <div class="flex h-48 w-full items-center justify-center bg-gray-100">
                    <span class="text-gray-400">No image</span>
                </div>
                @endif
                <div class="p-4">
                    <div class="mb-3 flex flex-wrap gap-2">
                        @if($product->comparison_has_fixture)
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Local fixture</span>
                        @else
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">Source snapshot</span>
                        @endif
                        @if($product->category)
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600">{{ $product->category }}</span>
                        @endif
                    </div>
                    <h2 class="mb-2 line-clamp-2 font-semibold text-gray-900">{{ $product->name }}</h2>
                    <p class="mb-3 text-sm text-gray-500">{{ $product->brand ?: 'Brand not recorded' }}</p>

                    @if($product->comparison_min_price !== null)
                    <p class="text-lg font-bold text-gray-900">From ₹{{ number_format((float) $product->comparison_min_price, 2) }}</p>
                    @else
                    <p class="text-sm font-medium text-gray-500">Price unavailable</p>
                    @endif
                    <p class="mt-2 text-xs text-gray-500">{{ $product->comparison_offer_count }} offer{{ $product->comparison_offer_count === 1 ? '' : 's' }} recorded</p>
                    @if($product->comparison_observed_at)
                    <p class="mt-1 text-xs text-gray-500">Observed {{ $product->comparison_observed_at->format('d M Y, H:i') }}</p>
                    @endif
                </div>
            </a>
        </article>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
    @else
    <div class="rounded-lg border border-gray-200 bg-white p-12 text-center">
        <p class="text-gray-500">No comparison records found. Try a different search or category.</p>
    </div>
    @endif
</div>
@endsection
