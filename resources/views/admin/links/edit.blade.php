@php($title = 'Edit Link')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Edit Affiliate Link</h1>
    <p class="mt-1 text-sm text-gray-500">Update link details and settings</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.links.update', $link->id) }}" method="POST" class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="program_id" class="block text-sm font-medium text-gray-700 mb-1">Program *</label>
            <select name="program_id" id="program_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" {{ old('program_id', $link->program_id) == $program->id ? 'selected' : '' }}>
                        {{ $program->name }} ({{ $program->merchant_name }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Affiliate User *</label>
            <select name="user_id" id="user_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', $link->user_id) == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }}) - {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label for="original_url" class="block text-sm font-medium text-gray-700 mb-1">Original URL *</label>
            <input type="url" name="original_url" id="original_url" value="{{ old('original_url', $link->original_url) }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="short_code" class="block text-sm font-medium text-gray-700 mb-1">Short Code</label>
            <input type="text" name="short_code" id="short_code" value="{{ old('short_code', $link->short_code) }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <p class="mt-1 text-xs text-gray-500">Current: <code class="bg-gray-100 px-1 rounded">{{ $link->short_code }}</code></p>
        </div>

        <div>
            <label for="sub_id" class="block text-sm font-medium text-gray-700 mb-1">Sub ID</label>
            <input type="text" name="sub_id" id="sub_id" value="{{ old('sub_id', $link->sub_id) }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="campaign_name" class="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label>
            <input type="text" name="campaign_name" id="campaign_name" value="{{ old('campaign_name', $link->campaign_name) }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
            <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at', $link->expires_at ? $link->expires_at->format('Y-m-d') : '') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div class="md:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" id="description" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description', $link->description) }}</textarea>
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $link->is_active) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">Active Link</span>
            </label>
        </div>

        <div class="md:col-span-2 bg-gray-50 p-4 rounded-md">
            <p class="text-sm font-medium text-gray-700 mb-2">Link Statistics</p>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Clicks:</span>
                    <span class="font-semibold ml-2">{{ number_format($link->click_count) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Conversions:</span>
                    <span class="font-semibold ml-2">{{ number_format($link->conversion_count) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Total Commission:</span>
                    <span class="font-semibold ml-2">₹{{ number_format($link->total_commission, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end space-x-3">
        <a href="{{ route('admin.links') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            Update Link
        </button>
    </div>
</form>
@endsection

