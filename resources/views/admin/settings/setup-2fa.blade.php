@extends('layouts.admin')

@section('title', 'Setup Google Authenticator')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.settings.security') }}" class="text-sm text-gray-500 hover:text-primary-600">
            &larr; Kembali ke Pengaturan Keamanan
        </a>
    </div>

    <div class="card p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Setup Google Authenticator</h1>
            <p class="text-gray-500 mt-2">Ikuti langkah-langkah berikut untuk mengaktifkan 2FA</p>
        </div>

        <!-- Step 1: Install App -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center text-sm font-bold">1</div>
                <h2 class="font-semibold text-gray-900 dark:text-white">Install Google Authenticator</h2>
            </div>
            <p class="text-sm text-gray-500 ml-11">
                Download dan install aplikasi Google Authenticator dari 
                <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="text-primary-600 hover:underline">Play Store</a> atau 
                <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" class="text-primary-600 hover:underline">App Store</a>.
            </p>
        </div>

        <!-- Step 2: Scan QR -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center text-sm font-bold">2</div>
                <h2 class="font-semibold text-gray-900 dark:text-white">Scan QR Code</h2>
            </div>
            <p class="text-sm text-gray-500 ml-11 mb-4">
                Buka aplikasi Google Authenticator dan scan QR code berikut:
            </p>
            
            <div class="flex justify-center mb-4">
                <div class="bg-white p-4 rounded-lg shadow-inner">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" 
                         alt="QR Code" class="w-48 h-48">
                </div>
            </div>

            <div class="ml-11">
                <p class="text-sm text-gray-500 mb-2">Atau masukkan kode ini secara manual:</p>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 font-mono text-center break-all">
                    {{ $secret }}
                </div>
            </div>
        </div>

        <!-- Step 3: Verify -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center text-sm font-bold">3</div>
                <h2 class="font-semibold text-gray-900 dark:text-white">Verifikasi</h2>
            </div>
            <p class="text-sm text-gray-500 ml-11 mb-4">
                Masukkan kode 6-digit yang muncul di aplikasi Google Authenticator:
            </p>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 ml-11">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('admin.settings.2fa.confirm') }}" method="POST" class="ml-11" x-data="{ loading: false }">
                @csrf
                <div class="flex gap-3">
                    <input type="text" name="code" maxlength="6" required
                           class="input-field text-center font-mono text-xl tracking-widest w-40"
                           placeholder="000000"
                           inputmode="numeric"
                           pattern="[0-9]*">
                    <button type="submit" class="btn-primary" :disabled="loading" @click="loading = true">
                        <span x-show="!loading">Aktifkan 2FA</span>
                        <span x-show="loading">Memproses...</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Warning -->
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm text-amber-700 dark:text-amber-400">
                    <p class="font-medium mb-1">Penting!</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Simpan kode secret di tempat yang aman sebagai backup</li>
                        <li>Jika kehilangan akses ke Google Authenticator, Anda memerlukan bantuan super admin</li>
                        <li>Pastikan waktu di perangkat Anda sinkron dengan internet</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
