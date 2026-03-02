@php($title = 'Redemptions')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Redemptions Management</h1>
    <p class="mt-1 text-sm text-gray-500">Approve or reject redemption requests</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('admin.redemptions') }}" class="flex flex-col md:flex-row gap-4">
        <div>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div>
            <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">All Types</option>
                <option value="cash" {{ request('type') == 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="gift" {{ request('type') == 'gift' ? 'selected' : '' }}>Gift</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Filter</button>
    </form>
</div>

<!-- Redemptions Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    @if($redemptions->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount/Gift</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($redemptions as $redemption)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $redemption->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $redemption->user->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ ucfirst($redemption->redemption_type) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ number_format($redemption->points_used) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        @if($redemption->redemption_type === 'cash')
                        ₹{{ number_format($redemption->cash_amount, 2) }}
                        @else
                        {{ $redemption->gift->name ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($redemption->status === 'pending')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                        @elseif($redemption->status === 'approved')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Approved</span>
                        @elseif($redemption->status === 'completed')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $redemption->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($redemption->status === 'pending')
                        <div class="flex items-center space-x-2">
                            <form method="POST" action="{{ route('admin.redemptions.approve', $redemption->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-700">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.redemptions.reject', $redemption->id) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-700">Reject</button>
                            </form>
                        </div>
                        @elseif($redemption->status === 'approved')
                        <form method="POST" action="{{ route('admin.redemptions.complete', $redemption->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-blue-600 hover:text-blue-700">Mark Complete</button>
                        </form>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $redemptions->links() }}
    </div>
    @else
    <div class="p-12 text-center">
        <p class="text-gray-500">No redemptions found.</p>
    </div>
    @endif
</div>
@endsection

