@extends('layouts.admin')

@section('title', 'Review KYC')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Review KYC</h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-400">Daftar pengajuan verifikasi identitas (KYC) pengguna.</p>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
    {{-- Filters --}}
    <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 backdrop-blur-sm">
        <form method="GET" class="flex flex-col sm:flex-row gap-4 items-center">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter Status:</span>
            <div class="w-full sm:w-64">
                <select id="admin-kyc-status-filter" name="status"
                        class="block w-full rounded-xl border-gray-300 py-2.5 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white shadow-sm">
                    <option value="{{ route('admin.kyc.index') }}" {{ !$currentStatus ? 'selected' : '' }}>Semua Pengajuan</option>
                    @foreach($statuses as $status)
                        <option value="{{ route('admin.kyc.index', ['status' => $status->value]) }}" {{ $currentStatus === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    {{-- KYC Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:pl-6">Pengguna</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Jenis ID</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Waktu Pengajuan</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse($kycList as $kyc)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/50">
                                    <span class="font-medium leading-none text-indigo-700 dark:text-indigo-300">{{ substr($kyc->user->name ?? 'U', 0, 1) }}</span>
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $kyc->user->name ?? 'Unknown User' }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs font-mono">{{ $kyc->user->id ?? '-' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400 uppercase font-medium">
                        {{ $kyc->id_type }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                         <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $kyc->status->badgeClass() }}">
                            {{ $kyc->status->label() }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $kyc->created_at->format('d M Y, H:i') }}
                    </td>
                     <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('admin.kyc.show', $kyc) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 group flex items-center justify-end gap-1">
                            Review
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Tidak ada pengajuan KYC</p>
                            <p class="mt-2 text-sm text-gray-500">Belum ada pengguna yang mengajukan verifikasi identitas.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($kycList->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        {{ $kycList->links() }}
    </div>
    @endif
</div>

<script @cspNonce>
document.addEventListener('DOMContentLoaded', () => {
    const statusFilter = document.getElementById('admin-kyc-status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            if (statusFilter.value) window.location.href = statusFilter.value;
        });
    }
});
</script>
@endsection
