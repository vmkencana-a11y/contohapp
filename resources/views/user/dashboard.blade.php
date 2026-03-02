@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Welcome Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Selamat Datang, {{ $user->name }}!</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Berikut ringkasan akun Anda</p>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Referral Code --}}
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kode Referral</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">{{ $user->referral_code }}</p>
                </div>
                <button id="copy-referral-code-btn" type="button"
                        class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                        title="Salin">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Referral Count --}}
        <div class="card p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Downline</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['referral_count'] }}</p>
                </div>
            </div>
        </div>

        {{-- KYC Status --}}
        <div class="card p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status KYC</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $stats['kyc_status'] }}</p>
                </div>
            </div>
        </div>

        {{-- Account Status --}}
        <div class="card p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg {{ $user->status->color() === 'green' ? 'bg-green-100 dark:bg-green-900/50' : 'bg-yellow-100 dark:bg-yellow-900/50' }} flex items-center justify-center">
                    <svg class="w-6 h-6 {{ $user->status->color() === 'green' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status Akun</p>
                    <span class="{{ $user->status->badgeClass() }}">{{ $user->status->label() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="card p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aksi Cepat</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('referral') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors group">
                <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center group-hover:bg-primary-200 dark:group-hover:bg-primary-800/50 transition-colors">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Bagikan Referral</span>
            </a>

            <a href="{{ route('kyc') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors group">
                <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800/50 transition-colors">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Verifikasi KYC</span>
            </a>

            <a href="{{ route('profile') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors group">
                <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-800/50 transition-colors">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Edit Profil</span>
            </a>

            <a href="#" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors group">
                <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center group-hover:bg-orange-200 dark:group-hover:bg-orange-800/50 transition-colors">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Bantuan</span>
            </a>
        </div>
    </div>
</div>

<script @cspNonce>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        window.Sekuota?.toast?.success('Kode referral berhasil disalin!');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const copyBtn = document.getElementById('copy-referral-code-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => copyToClipboard(@js($user->referral_code)));
    }
});
</script>
@endsection
