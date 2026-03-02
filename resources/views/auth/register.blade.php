@extends('layouts.guest')

@section('content')
<div class="min-h-full flex">
    {{-- Left Panel - Branding --}}
    <div class="hidden lg:flex lg:w-1/2 lg:flex-col lg:justify-center bg-gradient-to-br from-green-600 via-emerald-700 to-teal-900 p-12">
        <div class="max-w-md">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                    <span class="text-white font-bold text-2xl">S</span>
                </div>
                <span class="text-3xl font-bold text-white">Sekuota</span>
            </div>
            <h1 class="text-4xl font-bold text-white mb-4">Bergabunglah dengan Ribuan Mitra Kami</h1>
            <p class="text-emerald-100 text-lg">Daftar gratis dan mulai transaksi PPOB dengan margin terbaik.</p>
            
            <div class="mt-12 grid grid-cols-2 gap-4">
                <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                    <div class="text-3xl font-bold text-white">10K+</div>
                    <div class="text-emerald-200 text-sm">Mitra Aktif</div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                    <div class="text-3xl font-bold text-white">1M+</div>
                    <div class="text-emerald-200 text-sm">Transaksi/Bulan</div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                    <div class="text-3xl font-bold text-white">99.9%</div>
                    <div class="text-emerald-200 text-sm">Uptime</div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                    <div class="text-3xl font-bold text-white">24/7</div>
                    <div class="text-emerald-200 text-sm">Support</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Panel - Register Form --}}
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            {{-- Mobile Logo --}}
            <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                    <span class="text-white font-bold text-xl">S</span>
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">Sekuota</span>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Daftar Akun Baru</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-8">Isi data di bawah untuk membuat akun</p>

            <form action="{{ route('register.request-otp') }}" method="POST" class="space-y-5" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="name" required autofocus
                           value="{{ old('name') }}"
                           class="input-field w-full"
                           placeholder="John Doe">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" name="email" id="email" required
                           value="{{ old('email') }}"
                           class="input-field w-full"
                           placeholder="nama@email.com">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="referral_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kode Referral <span class="text-gray-400">(Opsional)</span>
                    </label>
                    <input type="text" name="referral_code" id="referral_code"
                           value="{{ old('referral_code', $referralCode) }}"
                           class="input-field w-full"
                           placeholder="ABCD1234"
                           maxlength="8">
                    @error('referral_code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-start">
                    <input type="checkbox" id="terms" required
                           class="mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="terms" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                        Saya menyetujui <a href="#" class="text-primary-600 hover:underline">Syarat & Ketentuan</a> serta <a href="#" class="text-primary-600 hover:underline">Kebijakan Privasi</a>
                    </label>
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <span x-show="!loading">Daftar</span>
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
                Daftar dengan Google
            </a>
            @endif

            <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">Masuk</a>
            </p>
        </div>
    </div>
</div>
@endsection
