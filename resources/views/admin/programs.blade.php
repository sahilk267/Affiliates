@php($title = 'Programs')
@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Affiliate Programs</h1>
        <p class="mt-1 text-sm text-gray-500">Manage affiliate programs and networks</p>
    </div>
    <a href="{{ route('admin.programs.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add Program
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="/admin/ui/programs" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" id="search" value="{{ request('search') }}" 
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                   placeholder="Program name...">
        </div>
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select name="type" id="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Types</option>
                <option value="ecommerce" {{ request('type') === 'ecommerce' ? 'selected' : '' }}>E-commerce</option>
                <option value="finance" {{ request('type') === 'finance' ? 'selected' : '' }}>Finance</option>
                <option value="referral" {{ request('type') === 'referral' ? 'selected' : '' }}>Referral</option>
                <option value="app_download" {{ request('type') === 'app_download' ? 'selected' : '' }}>App Download</option>
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Filter
            </button>
        </div>
    </form>
</div>

<!-- Programs Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($programs as $program)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        @if($program->logo_url)
                            <img src="{{ $program->logo_url }}" alt="{{ $program->name }}" class="h-10 w-10 rounded">
                        @else
                            <div class="h-10 w-10 bg-indigo-100 rounded flex items-center justify-center">
                                <span class="text-indigo-600 font-semibold text-sm">{{ strtoupper(substr($program->name, 0, 2)) }}</span>
                            </div>
                        @endif
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $program->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $program->merchant_name }}</p>
                        </div>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        {{ $program->status === 'active' ? 'bg-green-100 text-green-800' : ($program->status === 'inactive' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($program->status) }}
                    </span>
                </div>
                
                <div class="mb-4">
                    <span class="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800">
                        {{ ucfirst(str_replace('_', ' ', $program->type)) }}
                    </span>
                    @if($program->supports_sub_affiliate)
                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">
                            Sub-Affiliate Enabled
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-lg font-semibold text-gray-900">{{ $program->links_count }}</p>
                        <p class="text-xs text-gray-500">Links</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-semibold text-gray-900">{{ $program->clicks_count }}</p>
                        <p class="text-xs text-gray-500">Clicks</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-semibold text-gray-900">{{ $program->conversions_count }}</p>
                        <p class="text-xs text-gray-500">Conversions</p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end space-x-2">
                <a href="{{ route('admin.programs.edit', $program->id) }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Edit</a>
                <form action="{{ route('admin.programs.delete', $program->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this program?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-gray-600 hover:text-gray-700 font-medium">Delete</button>
                </form>
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500">No programs found</p>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($programs->hasPages())
<div class="mt-6 bg-white px-4 py-3 border border-gray-200 rounded-lg sm:px-6">
    {{ $programs->links() }}
</div>
@endif
@endsection
