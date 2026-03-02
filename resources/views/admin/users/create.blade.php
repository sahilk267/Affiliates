@php($title = 'Create User')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Create New User</h1>
    <p class="mt-1 text-sm text-gray-500">Add a new admin, affiliate, or sub-affiliate user</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
        {{ session('error') }}
    </div>
@endif

<form action="{{ route('admin.users.store') }}" method="POST" class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
    @csrf
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-300 @enderror">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('email') border-red-300 @enderror">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
            <input type="password" name="password" id="password" required minlength="8"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('password') border-red-300 @enderror">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
        </div>

        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
            <select name="role" id="role" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('role') border-red-300 @enderror">
                <option value="">Select Role</option>
                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="affiliate" {{ old('role') === 'affiliate' ? 'selected' : '' }}>Affiliate</option>
                <option value="sub_affiliate" {{ old('role') === 'sub_affiliate' ? 'selected' : '' }}>Sub-Affiliate</option>
            </select>
            @error('role')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div id="parent_id_field" style="display: none;">
            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Affiliate</label>
            <select name="parent_id" id="parent_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Select Parent</option>
                @foreach($users as $parentUser)
                    <option value="{{ $parentUser->id }}" {{ old('parent_id') == $parentUser->id ? 'selected' : '' }}>
                        {{ $parentUser->name }} ({{ $parentUser->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">Active User</span>
            </label>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end space-x-3">
        <a href="{{ route('admin.users') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            Create User
        </button>
    </div>
</form>

<script>
document.getElementById('role').addEventListener('change', function() {
    const parentField = document.getElementById('parent_id_field');
    if (this.value === 'sub_affiliate') {
        parentField.style.display = 'block';
    } else {
        parentField.style.display = 'none';
    }
});
</script>
@endsection




