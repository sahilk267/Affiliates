@php($title = 'Wallets & Points')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Wallets & Points Management</h1>
    <p class="mt-1 text-sm text-gray-500">Manage user points and balances</p>
</div>

<!-- Search -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('admin.wallets') }}" class="flex gap-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Search</button>
    </form>
</div>

<!-- Wallets Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    @if($wallets->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Earned</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Redeemed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($wallets as $wallet)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $wallet->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $wallet->user->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ number_format($wallet->balance) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ number_format($wallet->pending_balance) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">{{ number_format($wallet->total_earned) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">{{ number_format($wallet->total_redeemed) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button onclick="openAdjustModal({{ $wallet->user_id }}, '{{ $wallet->user->name }}')" class="text-indigo-600 hover:text-indigo-700">Adjust</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $wallets->links() }}
    </div>
    @else
    <div class="p-12 text-center">
        <p class="text-gray-500">No wallets found.</p>
    </div>
    @endif
</div>

<!-- Adjust Points Modal -->
<div id="adjustModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-semibold mb-4">Adjust Points</h3>
        <form method="POST" id="adjustForm">
            @csrf
            <input type="hidden" name="user_id" id="adjustUserId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                <input type="text" id="adjustUserName" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="credit">Credit</option>
                    <option value="debit">Debit</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Points</label>
                <input type="number" name="points" min="1" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="2" required class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex items-center justify-end">
                <button type="button" onclick="closeAdjustModal()" class="mr-4 px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Adjust</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdjustModal(userId, userName) {
    document.getElementById('adjustUserId').value = userId;
    document.getElementById('adjustUserName').value = userName;
    document.getElementById('adjustForm').action = `/admin/ui/wallets/${userId}/adjust`;
    document.getElementById('adjustModal').classList.remove('hidden');
}

function closeAdjustModal() {
    document.getElementById('adjustModal').classList.add('hidden');
}
</script>
@endsection

