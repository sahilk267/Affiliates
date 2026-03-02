@php($title = 'Quick Add Program')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Quick Add Program</h1>
    <p class="mt-1 text-sm text-gray-500">Add a new affiliate program using templates</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <form method="POST" action="{{ route('admin.programs.quick-add.store') }}">
        @csrf

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Template *</label>
            <select name="template" id="templateSelect" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="updateTemplateFields()">
                <option value="">Choose a template...</option>
                <option value="amazon">Amazon Associates</option>
                <option value="flipkart">Flipkart Affiliate</option>
                <option value="myntra">Myntra Affiliate</option>
                <option value="gpay">GPay Referral</option>
                <option value="phonepe">PhonePe Referral</option>
                <option value="upstox">Upstox Account Opening</option>
                <option value="custom">Custom</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Program Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Merchant Name *</label>
                <input type="text" name="merchant_name" value="{{ old('merchant_name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('merchant_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Merchant URL *</label>
                <input type="url" name="merchant_url" value="{{ old('merchant_url') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('merchant_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo URL</label>
                <input type="url" name="logo_url" value="{{ old('logo_url') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('logo_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end">
            <a href="{{ route('admin.programs') }}" class="mr-4 px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Create Program
            </button>
        </div>
    </form>
</div>

<!-- Template Info -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h3 class="text-sm font-semibold text-blue-900 mb-2">Template Information</h3>
    <div id="templateInfo" class="text-sm text-blue-700">
        <p>Select a template to see pre-configured settings.</p>
    </div>
</div>

<script>
function updateTemplateFields() {
    const template = document.getElementById('templateSelect').value;
    const templates = {
        'amazon': { name: 'Amazon Associates', type: 'E-commerce', subAffiliate: false, commission: '2.5%' },
        'flipkart': { name: 'Flipkart Affiliate', type: 'E-commerce', subAffiliate: false, commission: '3.0%' },
        'myntra': { name: 'Myntra Affiliate', type: 'E-commerce', subAffiliate: false, commission: '2.0%' },
        'gpay': { name: 'GPay Referral', type: 'Finance', subAffiliate: true, commission: '₹50 fixed' },
        'phonepe': { name: 'PhonePe Referral', type: 'Finance', subAffiliate: true, commission: '₹30 fixed' },
        'upstox': { name: 'Upstox Account Opening', type: 'Finance', subAffiliate: true, commission: '₹200 fixed' },
        'custom': { name: 'Custom', type: 'Other', subAffiliate: false, commission: '1.0%' }
    };

    if (template && templates[template]) {
        const info = templates[template];
        document.getElementById('templateInfo').innerHTML = `
            <p><strong>Type:</strong> ${info.type}</p>
            <p><strong>Sub-Affiliate Support:</strong> ${info.subAffiliate ? 'Yes' : 'No'}</p>
            <p><strong>Default Commission:</strong> ${info.commission}</p>
        `;
    } else {
        document.getElementById('templateInfo').innerHTML = '<p>Select a template to see pre-configured settings.</p>';
    }
}
</script>
@endsection

