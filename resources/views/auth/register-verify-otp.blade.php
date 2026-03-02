@extends('layouts.guest')

@section('content')
<div class="min-h-full flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="flex items-center justify-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                <span class="text-white font-bold text-xl">S</span>
            </div>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">Sekuota</span>
        </div>

        {{-- Card --}}
        <div class="card p-8">
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Verifikasi Email</h2>
                <p class="text-gray-600 dark:text-gray-400">Masukkan kode OTP yang dikirim ke <strong class="text-gray-900 dark:text-white">{{ $email }}</strong></p>
            </div>

            {{-- Dev OTP Display - ONLY in local development environment --}}
            @if(app()->isLocal() && $devOtp)
            <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Dev Mode:</strong> Kode OTP Anda adalah <code class="font-mono font-bold text-lg">{{ $devOtp }}</code>
                </p>
            </div>
            @endif

            <form action="{{ route('register.verify-otp.submit') }}" method="POST" class="space-y-6" x-data="otpInput()" @submit="loading = true">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                
                {{-- OTP Input --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-center">Masukkan Kode 6 Digit</label>
                    <div class="flex justify-center gap-2">
                        @for($i = 0; $i < 6; $i++)
                        <input type="text" maxlength="1" 
                               class="otp-input w-12 h-14 text-center text-xl font-bold rounded-lg border-2 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:text-white"
                               x-ref="input{{ $i }}"
                               @input="handleInput($event, {{ $i }})"
                               @keydown="handleKeydown($event, {{ $i }})"
                               @paste="handlePaste($event)">
                        @endfor
                    </div>
                    <input type="hidden" name="otp" x-model="otp">
                    @error('otp')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 text-center">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="loading || otp.length < 6">
                    <span x-show="!loading">Verifikasi</span>
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
                     x-data="otpResendTimer('user_register_otp_countdown', 60, {{ session('success') ? 'true' : 'false' }})">
                    <p class="mb-2">Tidak menerima kode?</p>
                    <form action="{{ route('register.verify-otp.resend') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
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
        </div>

        <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('register') }}" class="hover:text-primary-600">&larr; Kembali ke pendaftaran</a>
        </p>
    </div>
</div>
@endsection
