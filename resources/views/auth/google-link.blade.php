@extends('layouts.guest')

@section('content')
<div class="min-h-full flex items-center justify-center p-6 lg:p-12">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="flex items-center justify-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                <span class="text-white font-bold text-xl">S</span>
            </div>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">Sekuota</span>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
            {{-- Info Card --}}
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Verifikasi Diperlukan</p>
                        <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                            Email <strong>{{ $email }}</strong> sudah terdaftar. Untuk menghubungkan akun Google (<strong>{{ $googleName }}</strong>), silakan verifikasi kepemilikan akun dengan kode OTP.
                        </p>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hubungkan Akun Google</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">Masukkan kode OTP yang telah dikirim ke email Anda</p>

            @if(session('info'))
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm">
                    {{ session('info') }}
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(app()->isLocal() && $devOtp)
                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 rounded-lg text-sm font-mono">
                    <span class="font-medium">DEV OTP:</span> {{ $devOtp }}
                </div>
            @endif

            <form action="{{ route('auth.google.link') }}" method="POST" class="space-y-6" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kode OTP</label>
                    <input type="text" name="otp" id="otp" required autofocus
                           class="input-field w-full text-center text-2xl tracking-[0.5em] font-mono"
                           placeholder="------"
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           autocomplete="one-time-code">
                    @error('otp')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <span x-show="!loading">Verifikasi & Hubungkan</span>
                    <span x-show="loading">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                    </span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <div class="text-sm text-gray-600 dark:text-gray-400"
                     x-data="otpResendTimer('user_google_link_otp_countdown', 60, {{ (session('info') || session('success')) ? 'true' : 'false' }})">
                    <p class="mb-2">Tidak menerima kode?</p>
                    <form action="{{ route('auth.google.link.resend') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="font-medium transition-colors"
                                :class="countdown > 0 ? 'text-gray-400 cursor-not-allowed' : 'text-primary-600 hover:text-primary-500 dark:text-primary-400'"
                                :disabled="countdown > 0">
                            <span x-show="countdown <= 0">Kirim ulang</span>
                            <span x-show="countdown > 0">Kirim ulang dalam <span x-text="countdown"></span> detik</span>
                        </button>
                    </form>
                </div>
            </div>

            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">Kembali ke halaman login</a>
            </p>
        </div>
    </div>
</div>
@endsection
