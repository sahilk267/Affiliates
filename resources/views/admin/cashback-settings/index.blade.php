@php($title = 'Cashback Settings')
@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Cashback Settings</h1>
    <p class="mt-1 text-sm text-gray-500">Configure cashback and referral rates for each program</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cashback Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referral Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points/Rupee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($programs as $program)
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($program->logo_url)
                            <img src="{{ $program->logo_url }}" alt="{{ $program->name }}" class="h-8 w-8 rounded mr-3">
                            @endif
                            <span class="font-medium text-gray-900">{{ $program->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $program->cashbackSetting ? number_format($program->cashbackSetting->cashback_rate, 1) . '%' : 'Not Set' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $program->cashbackSetting ? number_format($program->cashbackSetting->referral_rate, 1) . '%' : 'Not Set' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $program->cashbackSetting ? $program->cashbackSetting->points_per_rupee : 'Not Set' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($program->cashbackSetting && $program->cashbackSetting->status === 'active')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button onclick="openSettingsModal({{ $program->id }}, '{{ $program->name }}')" class="text-indigo-600 hover:text-indigo-700">Configure</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Settings Modal -->
<div id="settingsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-semibold mb-4">Cashback Settings</h3>
        <form method="POST" id="settingsForm">
            @csrf
            <input type="hidden" name="program_id" id="settingsProgramId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Program</label>
                <input type="text" id="settingsProgramName" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cashback Rate (%)</label>
                <input type="number" name="cashback_rate" step="0.1" min="0" max="100" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Referral Rate (%)</label>
                <input type="number" name="referral_rate" step="0.1" min="0" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Points per Rupee</label>
                <input type="number" name="points_per_rupee" min="1" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex items-center justify-end">
                <button type="button" onclick="closeSettingsModal()" class="mr-4 px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSettingsModal(programId, programName) {
    document.getElementById('settingsProgramId').value = programId;
    document.getElementById('settingsProgramName').value = programName;
    document.getElementById('settingsForm').action = `/admin/ui/cashback-settings/${programId}`;
    document.getElementById('settingsModal').classList.remove('hidden');
}

function closeSettingsModal() {
    document.getElementById('settingsModal').classList.add('hidden');
}
</script>
@endsection

