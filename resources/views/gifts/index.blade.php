@php($title = 'Gifts Catalog')
@extends('layouts.consumer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Gifts Catalog</h1>
        <p class="mt-2 text-gray-600">Redeem your points for amazing gifts</p>
        <p class="text-sm text-gray-500 mt-1">Your Balance: <span class="font-semibold text-indigo-600">{{ number_format($pointsBalance ?? 0) }} points</span></p>
    </div>

    @if(isset($gifts) && $gifts->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($gifts as $gift)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            @if($gift->image_url)
            <img src="{{ $gift->image_url }}" alt="{{ $gift->name }}" class="w-full h-48 object-cover">
            @else
            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                <span class="text-gray-400">No Image</span>
            </div>
            @endif
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 mb-2">{{ $gift->name }}</h3>
                @if($gift->description)
                <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $gift->description }}</p>
                @endif
                
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Points Required</p>
                        <p class="text-xl font-bold text-indigo-600">{{ number_format($gift->points_required) }}</p>
                    </div>
                    @if($gift->stock > 0)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        In Stock ({{ $gift->stock }})
                    </span>
                    @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                        Out of Stock
                    </span>
                    @endif
                </div>

                @if($gift->isAvailable() && ($pointsBalance ?? 0) >= $gift->points_required)
                <form method="POST" action="{{ route('gifts.redeem', $gift->id) }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Redeem Now
                    </button>
                </form>
                @elseif($gift->isAvailable())
                <button disabled class="w-full px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
                    Insufficient Points
                </button>
                @else
                <button disabled class="w-full px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
                    Not Available
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <p class="text-gray-500">No gifts available at the moment. Check back later!</p>
    </div>
    @endif
</div>
@endsection

