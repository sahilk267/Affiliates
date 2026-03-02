@php($title = 'Referrals')
@extends('layouts.consumer')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Referrals</h1>
        <p class="mt-2 text-gray-600">Invite friends and earn points when they make purchases</p>
    </div>

    <!-- Referral Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-sm font-medium text-gray-500 mb-2">Total Referrals</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($referralStats['total_referrals'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-sm font-medium text-gray-500 mb-2">Active Referrals</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($referralStats['active_referrals'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-sm font-medium text-gray-500 mb-2">Total Conversions</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($referralStats['total_conversions'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-sm font-medium text-gray-500 mb-2">Points Earned</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($referralStats['total_points_earned'] ?? 0) }}</p>
        </div>
    </div>

    <!-- Referral Code -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-lg p-8 mb-8 text-white">
        <h2 class="text-xl font-semibold mb-4">Your Referral Link</h2>
        <div class="bg-white bg-opacity-20 rounded-lg p-4 mb-4">
            <p class="text-sm mb-2">Share this link with your friends:</p>
            <div class="flex items-center">
                <input type="text" value="{{ $referralLink }}" readonly id="referralLink" class="flex-1 px-4 py-2 bg-white bg-opacity-30 text-white rounded-l-lg border border-white border-opacity-30">
                <button onclick="copyReferralLink()" class="px-4 py-2 bg-white text-indigo-600 rounded-r-lg hover:bg-gray-100 font-semibold">
                    Copy
                </button>
            </div>
        </div>
        <p class="text-sm text-indigo-100">Referral Code: <span class="font-bold">{{ $referral->referral_code }}</span></p>
    </div>

    <!-- Referrals List -->
    @if(isset($referrals) && $referrals->count() > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Your Referrals</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conversions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points Earned</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($referrals as $ref)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $ref->referred->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ $ref->program->name ?? 'All Programs' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ref->status === 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($ref->total_conversions ?? 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">{{ number_format($ref->total_points_earned ?? 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $ref->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <p class="text-gray-500 mb-4">No referrals yet. Share your referral link to start earning!</p>
    </div>
    @endif
</div>

<script>
function copyReferralLink() {
    const link = document.getElementById('referralLink');
    link.select();
    document.execCommand('copy');
    alert('Referral link copied!');
}
</script>
@endsection

