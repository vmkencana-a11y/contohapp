@extends('layouts.admin')

@section('title', 'Referral Monitoring')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Referral Monitoring</h1>

    {{-- Stats Stats Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="card p-6 border-l-4 border-blue-500">
            <p class="text-sm font-medium text-gray-500">Total Referrals</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="card p-6 border-l-4 border-green-500">
            <p class="text-sm font-medium text-gray-500">Active Referrals</p>
            <p class="text-3xl font-bold text-green-600">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="card p-6 border-l-4 border-red-500">
            <p class="text-sm font-medium text-gray-500">Cancelled / Banned</p>
            <p class="text-3xl font-bold text-red-600">{{ number_format($stats['cancelled']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Top Referrers --}}
        <div class="lg:col-span-1">
            <div class="card h-full">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Top 5 Referrers</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topReferrers as $top)
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-xs">
                                {{ substr($top->referrer->name ?? 'Unknown', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate w-32">
                                    {{ $top->referrer->name ?? 'Unknown' }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $top->referrer->referral_code ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="badge badge-success">{{ $top->count }} Refs</span>
                    </div>
                    @empty
                    <div class="p-6 text-center text-gray-500 text-sm">Belum ada data</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Referrals Table --}}
        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Riwayat Referral</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase font-medium text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Referrer (Upline)</th>
                                <th class="px-6 py-3">Referred (Downline)</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($referrals as $ref)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $ref->referrer->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ $ref->referrer->email ?? '' }}</p>
                                </td>
                                <td class="px-6 py-3">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $ref->user->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ $ref->user->email ?? '' }}</p>
                                </td>
                                <td class="px-6 py-3">
                                    @if($ref->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-error">Cancelled</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs">
                                    {{ $ref->created_at->format('d M Y H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada data referral.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($referrals->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $referrals->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
