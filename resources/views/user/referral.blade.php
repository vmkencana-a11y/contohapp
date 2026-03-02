@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">Program Referral</h1>

    {{-- Referral Link Card --}}
    <div class="card p-6 mb-8 bg-gradient-to-r from-primary-500 to-primary-700 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold mb-1">Bagikan Link Referral Anda</h2>
                <p class="text-primary-100 text-sm">Ajak teman bergabung dan dapatkan bonus untuk setiap referral aktif</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" readonly 
                       value="{{ url('/register?ref=' . $user->referral_code) }}"
                       id="referral-link"
                       class="px-4 py-2 rounded-lg bg-white/20 text-white placeholder-white/60 border border-white/30 text-sm w-64">
                <button id="copy-referral-link-btn" type="button" class="px-4 py-2 bg-white text-primary-700 rounded-lg font-medium hover:bg-primary-50 transition-colors">
                    Salin
                </button>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="card p-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Referral</p>
            </div>
        </div>
        <div class="card p-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['active'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Aktif</p>
            </div>
        </div>
        <div class="card p-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $stats['cancelled'] }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Dibatalkan</p>
            </div>
        </div>
    </div>

    {{-- Referral List --}}
    <div class="card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Downline</h2>
        </div>

        @if($referrals->isEmpty())
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum Ada Referral</h3>
            <p class="text-gray-500 dark:text-gray-400">Bagikan kode referral Anda untuk mulai mendapatkan downline</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal Bergabung</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($referrals as $referral)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center text-primary-600 dark:text-primary-400 font-medium text-sm">
                                    {{ substr($referral->user->name ?? 'U', 0, 1) }}
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium">{{ $referral->user->name ?? 'Unknown' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $referral->referred_at->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($referral->status === 'active')
                                <span class="badge-success">Aktif</span>
                            @else
                                <span class="badge-danger">Dibatalkan</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($referrals->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $referrals->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

<script @cspNonce>
function copyReferralLink() {
    const input = document.getElementById('referral-link');
    navigator.clipboard.writeText(input.value).then(() => {
        window.Sekuota?.toast?.success('Link referral berhasil disalin!');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const copyBtn = document.getElementById('copy-referral-link-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', copyReferralLink);
    }
});
</script>
@endsection
