@php($title = $product->name)
@extends('layouts.guest')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li><a href="{{ route('home') }}" class="hover:text-gray-700">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('products.index') }}" class="hover:text-gray-700">Compare products</a></li>
            <li aria-hidden="true">/</li>
            <li class="text-gray-900">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div>
            @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full rounded-lg border border-gray-200 shadow-sm">
            @else
            <div class="flex h-96 w-full items-center justify-center rounded-lg bg-gray-100">
                <span class="text-gray-400">No image available</span>
            </div>
            @endif
        </div>

        <div>
            <div class="mb-4 flex flex-wrap gap-2">
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Comparison preview</span>
                @if($product->category)
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">{{ $product->category }}</span>
                @endif
            </div>
            <h1 class="mb-4 text-3xl font-bold text-gray-900">{{ $product->name }}</h1>

            @if($product->brand)
            <p class="mb-4 text-lg text-gray-600">Brand: {{ $product->brand }}</p>
            @endif

            @if($product->description)
            <div class="mb-6">
                <h2 class="mb-2 text-lg font-semibold">Description</h2>
                <p class="text-gray-700">{{ $product->description }}</p>
            </div>
            @endif

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Prices, availability, ratings, and history below are source observations. They may be local fixtures or partner snapshots and are not a purchase guarantee. ZenithSoles redirects you to the external merchant; checkout and fulfilment happen there.
            </div>
        </div>
    </div>

    @if($priceComparison->isNotEmpty())
    <section class="mt-12" aria-labelledby="comparison-heading">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="comparison-heading" class="text-2xl font-bold text-gray-900">Compare recorded offers</h2>
                <p class="mt-1 text-sm text-gray-600">Offers are ordered by lowest known observed price. Unknown prices appear last. Commercial ranking weights are not applied in this preview.</p>
            </div>
            <p class="text-xs text-gray-500">{{ $priceComparison->count() }} offer{{ $priceComparison->count() === 1 ? '' : 's' }} recorded</p>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Platform</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Observed price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Availability</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Observed at</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($priceComparison as $offer)
                        <tr class="hover:bg-gray-50 {{ $offer['is_lowest_known_price'] ? 'bg-green-50' : '' }}">
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center">
                                    @if($offer['program']?->logo_url)
                                    <img src="{{ $offer['program']->logo_url }}" alt="{{ $offer['program']->name }} logo" class="mr-3 h-8 w-8 rounded">
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $offer['program']?->name ?: 'Merchant not recorded' }}</p>
                                        <p class="mt-1 text-xs text-gray-500">Source: {{ $offer['source'] ?: 'not recorded' }}</p>
                                        @if($offer['is_fixture'])
                                        <span class="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Local fixture</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                @if($offer['price'] !== null)
                                <p class="text-lg font-semibold text-gray-900">{{ $offer['currency'] ?: 'Currency not recorded' }} {{ number_format((float) $offer['price'], 2) }}</p>
                                @if($offer['is_lowest_known_price'])
                                <span class="mt-1 inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Lowest known observed price</span>
                                @endif
                                @else
                                <p class="font-medium text-gray-500">Price unavailable</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                {{ $offer['availability'] ?: 'Availability unavailable' }}
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                @if($offer['rating'] !== null)
                                {{ number_format((float) $offer['rating'], 2) }}/5
                                @if($offer['rating_count'] !== null)
                                <span class="block text-xs text-gray-500">{{ number_format((int) $offer['rating_count']) }} ratings</span>
                                @endif
                                @else
                                Rating unavailable
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top text-sm text-gray-700">
                                @if($offer['observed_at'])
                                {{ $offer['observed_at']->format('d M Y, H:i') }}
                                @else
                                Not recorded
                                @endif
                                @if($offer['history']->isNotEmpty())
                                <details class="mt-2 text-xs">
                                    <summary class="cursor-pointer font-medium text-indigo-700">View history</summary>
                                    <ul class="mt-2 space-y-1 text-gray-600">
                                        @foreach($offer['history']->take(5) as $snapshot)
                                        <li>{{ $snapshot->observed_at->format('d M Y, H:i') }} — {{ $snapshot->price !== null ? ($snapshot->currency ?: 'INR') . ' ' . number_format((float) $snapshot->price, 2) : 'Price unavailable' }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top">
                                @if($offer['link'])
                                <a href="{{ route('products.buy', ['productId' => $product->id, 'programId' => $offer['program']->id]) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Open merchant</a>
                                @else
                                <span class="text-sm text-gray-400">Link unavailable</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @else
    <div class="mt-12 rounded-lg border border-gray-200 bg-white p-12 text-center">
        <p class="text-gray-500">No source observations are available for this product yet.</p>
    </div>
    @endif
</div>
@endsection
