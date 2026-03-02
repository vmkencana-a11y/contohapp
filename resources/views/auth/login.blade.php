@extends('layouts.guest')

@section('content')
<div class="min-h-full flex">
    {{-- Left Panel - Branding --}}
    <div class="hidden lg:flex lg:w-1/2 lg:flex-col lg:justify-center bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 p-12">
        <div class="max-w-md">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                    <span class="text-white font-bold text-2xl">S</span>
                </div>
                <span class="text-3xl font-bold text-white">Sekuota</span>
            </div>
            <h1 class="text-4xl font-bold text-white mb-4">PPOB Terpercaya untuk Bisnis Anda</h1>
            <p class="text-primary-100 text-lg">Platform transaksi pulsa, paket data, dan pembayaran online dengan keamanan tingkat bank.</p>
            
            <div class="mt-12 space-y-4">
                <div class="flex items-center gap-3 text-white">
                    <svg class="w-6 h-6 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Enkripsi AES-256-GCM untuk data sensitif</span>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <svg class="w-6 h-6 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Autentikasi OTP tanpa password</span>
                </div>
                <div class="flex items-center gap-3 text-white">
                    <svg class="w-6 h-6 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>Transaksi cepat & realtime</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Panel - Login Form --}}
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            {{-- Mobile Logo --}}
            <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                    <span class="text-white font-bold text-xl">S</span>
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">Sekuota</span>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Masuk ke Akun</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-8">Masukkan email Anda untuk menerima kode OTP</p>

            <form action="{{ route('login.request-otp') }}" method="POST" class="space-y-6" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" name="email" id="email" required autofocus
                           value="{{ old('email') }}"
                           class="input-field w-full"
                           placeholder="nama@email.com">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <span x-show="!loading">Kirim Kode OTP</span>
                    <span x-show="loading">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                    </span>
                </button>
            </form>

            @if(\App\Models\SystemSetting::getValue('google.oauth_enabled', false))
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-white dark:bg-gray-900 px-3 text-gray-500 dark:text-gray-400">atau</span>
                </div>
            </div>

            <a href="{{ route('auth.google') }}" 
               class="w-full inline-flex items-center justify-center gap-3 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 shadow-sm">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Masuk dengan Google
            </a>
            @endif

            <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">Daftar sekarang</a>
            </p>
        </div>
    </div>
</div>
@endsection
