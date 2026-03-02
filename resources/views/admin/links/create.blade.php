@php($title = 'Create Link')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Create New Affiliate Link</h1>
    <p class="mt-1 text-sm text-gray-500">Generate a new tracking link for an affiliate program</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.links.store') }}" method="POST" class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
    @csrf
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="program_id" class="block text-sm font-medium text-gray-700 mb-1">Program *</label>
            <select name="program_id" id="program_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('program_id') border-red-300 @enderror">
                <option value="">Select Program</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>
                        {{ $program->name }} ({{ $program->merchant_name }})
                    </option>
                @endforeach
            </select>
            @error('program_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Affiliate User *</label>
            <select name="user_id" id="user_id" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('user_id') border-red-300 @enderror">
                <option value="">Select User</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }}) - {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                    </option>
                @endforeach
            </select>
            @error('user_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="md:col-span-2">
            <label for="original_url" class="block text-sm font-medium text-gray-700 mb-1">Original URL *</label>
            <input type="url" name="original_url" id="original_url" value="{{ old('original_url') }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('original_url') border-red-300 @enderror"
                   placeholder="https://example.com/product">
            @error('original_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="short_code" class="block text-sm font-medium text-gray-700 mb-1">Short Code</label>
            <input type="text" name="short_code" id="short_code" value="{{ old('short_code') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('short_code') border-red-300 @enderror"
                   placeholder="Leave blank for auto-generation">
            @error('short_code')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Auto-generated if left blank</p>
        </div>

        <div>
            <label for="sub_id" class="block text-sm font-medium text-gray-700 mb-1">Sub ID</label>
            <input type="text" name="sub_id" id="sub_id" value="{{ old('sub_id') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                   placeholder="Optional tracking parameter">
        </div>

        <div>
            <label for="campaign_name" class="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label>
            <input type="text" name="campaign_name" id="campaign_name" value="{{ old('campaign_name') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                   placeholder="e.g., Summer Sale 2025">
        </div>

        <div>
            <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
            <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div class="md:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" id="description" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">Active Link</span>
            </label>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end space-x-3">
        <a href="{{ route('admin.links') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            Create Link
        </button>
    </div>
</form>
@endsection

