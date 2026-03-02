@php($title = 'Referrals')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Referrals Management</h1>
    <p class="mt-1 text-sm text-gray-500">View and manage all referral relationships</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('admin.referrals') }}" class="flex flex-col md:flex-row gap-4">
        <div>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            </select>
        </div>
        <div>
            <select name="program_id" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="">All Programs</option>
                @foreach($programs as $program)
                <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Filter</button>
    </form>
</div>

<!-- Referrals Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    @if($referrals->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referrer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referred User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conversions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points Earned</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($referrals as $referral)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $referral->referrer->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $referral->referred->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $referral->program->name ?? 'All Programs' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">{{ $referral->referral_code }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($referral->total_conversions ?? 0) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">{{ number_format($referral->total_points_earned ?? 0) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($referral->status === 'active')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Pending</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $referral->created_at->format('M d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $referrals->links() }}
    </div>
    @else
    <div class="p-12 text-center">
        <p class="text-gray-500">No referrals found.</p>
    </div>
    @endif
</div>
@endsection

