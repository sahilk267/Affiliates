@php($title = 'My Wallet')
@extends('layouts.consumer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">My Wallet</h1>
        <p class="mt-2 text-gray-600">Manage your points balance and transactions</p>
    </div>

    <!-- Balance Card -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-lg p-8 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-indigo-100 mb-2">Available Balance</p>
                <p class="text-5xl font-bold">{{ number_format($pointsBalance->balance ?? 0) }}</p>
                <p class="text-indigo-100 mt-2">Points (₹{{ number_format($pointsBalance->balance ?? 0) }})</p>
            </div>
            <div class="text-right">
                <p class="text-indigo-100 mb-2">Total Earned</p>
                <p class="text-2xl font-semibold">{{ number_format($pointsBalance->total_earned ?? 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Withdraw -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4">Withdraw Cash</h3>
            <p class="text-sm text-gray-600 mb-4">Minimum withdrawal: 100 points (₹100)</p>
            <form method="POST" action="{{ route('withdraw') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Points to Withdraw</label>
                    <input type="number" name="points" min="100" step="1" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Request Withdrawal
                </button>
            </form>
        </div>

        <!-- Redeem Gift -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold mb-4">Redeem Gifts</h3>
            <p class="text-sm text-gray-600 mb-4">Browse our gift catalog and redeem your points</p>
            <a href="{{ route('gifts') }}" class="block w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-center">
                Browse Gifts
            </a>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Transaction History</h2>
            <a href="{{ route('transactions') }}" class="text-sm text-indigo-600 hover:text-indigo-700">View All</a>
        </div>
        <div class="overflow-x-auto">
            @if(isset($transactions) && $transactions->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($transactions as $transaction)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($transaction->type === 'credit')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Credit</span>
                            @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Debit</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->points) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $transaction->description }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($transaction->balance_after) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-12 text-center">
                <p class="text-gray-500">No transactions yet.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

