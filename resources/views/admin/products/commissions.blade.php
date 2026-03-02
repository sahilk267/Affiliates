@php($title = 'Product Commissions')
@extends('layouts.app')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Product Commissions</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $product->name }}</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Back to Products</a>
</div>

<!-- Add Commission Form -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Add/Update Commission Rate</h2>
    <form method="POST" action="{{ route('admin.products.commissions.store', $product->id) }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Program *</label>
                <select name="program_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Select Program</option>
                    @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%) *</label>
                <input type="number" name="commission_rate" step="0.1" min="0" max="100" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                <select name="commission_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed Amount</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Save Commission
            </button>
        </div>
    </form>
</div>

<!-- Import Commissions -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Import Commissions from CSV</h2>
    <form method="POST" action="{{ route('admin.products.commissions.import', $product->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="flex items-center gap-4">
            <input type="file" name="csv_file" accept=".csv,.txt" required class="px-4 py-2 border border-gray-300 rounded-lg">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Import
            </button>
        </div>
        <p class="mt-2 text-xs text-gray-500">CSV Format: program_id, commission_rate, commission_type, status</p>
    </form>
</div>

<!-- Commissions List -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Commission Rates</h2>
    </div>
    @if($product->productCommissions->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($product->productCommissions as $commission)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $commission->program->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            {{ number_format($commission->commission_rate, 1) }}%
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ ucfirst($commission->commission_type) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($commission->status === 'active')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ ucfirst($commission->source) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <form method="POST" action="{{ route('admin.products.commissions.delete', [$product->id, $commission->id]) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-12 text-center">
        <p class="text-gray-500">No commission rates set for this product.</p>
    </div>
    @endif
</div>
@endsection

