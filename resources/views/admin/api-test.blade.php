@php($title = 'API Testing')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">API Testing Tool</h1>
    <p class="mt-1 text-sm text-gray-500">Test click tracking and conversion reporting APIs</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Click Tracking Test -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Click Tracking</h2>
        <form id="clickTestForm">
            @csrf
            <div class="mb-4">
                <label for="click_link_id" class="block text-sm font-medium text-gray-700 mb-1">Select Link *</label>
                <select name="link_id" id="click_link_id" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Choose a link...</option>
                    @foreach($links as $link)
                        <option value="{{ $link->id }}">
                            {{ $link->short_code }} - {{ $link->program->name }} ({{ $link->user->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                Track Click
            </button>
        </form>
        <div id="clickResult" class="mt-4 hidden p-4 rounded-md"></div>
    </div>

    <!-- Conversion Reporting Test -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Conversion Reporting</h2>
        <form id="conversionTestForm">
            @csrf
            <div class="mb-4">
                <label for="conversion_click_id" class="block text-sm font-medium text-gray-700 mb-1">Select Click *</label>
                <select name="click_id" id="conversion_click_id" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Choose a click...</option>
                    @foreach($clicks as $click)
                        <option value="{{ $click->id }}">
                            Click #{{ $click->id }} - {{ $click->link->short_code }} ({{ $click->created_at->format('M d, H:i') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label for="event_type" class="block text-sm font-medium text-gray-700 mb-1">Event Type *</label>
                <select name="event_type" id="event_type" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="purchase">Purchase</option>
                    <option value="signup">Signup</option>
                    <option value="download">Download</option>
                    <option value="install">Install</option>
                    <option value="lead">Lead</option>
                    <option value="click">Click</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="conversion_value" class="block text-sm font-medium text-gray-700 mb-1">Conversion Value</label>
                <input type="number" name="conversion_value" id="conversion_value" value="1000" step="0.01" min="0"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <button type="submit" class="w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                Report Conversion
            </button>
        </form>
        <div id="conversionResult" class="mt-4 hidden p-4 rounded-md"></div>
    </div>
</div>

<script>
document.getElementById('clickTestForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const resultDiv = document.getElementById('clickResult');
    
    resultDiv.classList.remove('hidden', 'bg-green-50', 'bg-red-50', 'border-green-200', 'border-red-200', 'text-green-800', 'text-red-800');
    resultDiv.innerHTML = '<p class="text-gray-600">Testing...</p>';
    
    try {
        const response = await fetch('{{ route("admin.api-test.click") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
            resultDiv.innerHTML = '<p class="font-semibold">✓ Success!</p><pre class="mt-2 text-xs overflow-auto">' + JSON.stringify(data.data, null, 2) + '</pre>';
        } else {
            resultDiv.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
            resultDiv.innerHTML = '<p class="font-semibold">✗ Error</p><pre class="mt-2 text-xs overflow-auto">' + JSON.stringify(data.data, null, 2) + '</pre>';
        }
    } catch (error) {
        resultDiv.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
        resultDiv.innerHTML = '<p class="font-semibold">✗ Error: ' + error.message + '</p>';
    }
});

document.getElementById('conversionTestForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const resultDiv = document.getElementById('conversionResult');
    
    resultDiv.classList.remove('hidden', 'bg-green-50', 'bg-red-50', 'border-green-200', 'border-red-200', 'text-green-800', 'text-red-800');
    resultDiv.innerHTML = '<p class="text-gray-600">Testing...</p>';
    
    try {
        const response = await fetch('{{ route("admin.api-test.conversion") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
            resultDiv.innerHTML = '<p class="font-semibold">✓ Success!</p><pre class="mt-2 text-xs overflow-auto">' + JSON.stringify(data.data, null, 2) + '</pre>';
        } else {
            resultDiv.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
            resultDiv.innerHTML = '<p class="font-semibold">✗ Error</p><pre class="mt-2 text-xs overflow-auto">' + JSON.stringify(data.data, null, 2) + '</pre>';
        }
    } catch (error) {
        resultDiv.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
        resultDiv.innerHTML = '<p class="font-semibold">✗ Error: ' + error.message + '</p>';
    }
});
</script>
@endsection

