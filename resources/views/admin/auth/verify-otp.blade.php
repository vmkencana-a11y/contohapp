@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6 bg-gray-50 dark:bg-gray-900">
    <div class="w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600 text-white mb-4 shadow-lg shadow-indigo-600/30">
                <span class="font-bold text-2xl">S</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Login</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">Masuk untuk mengelola platform</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <!-- Progress Steps -->
            <div class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700 p-4">
                <div class="flex items-center justify-center gap-2">
                    <div class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-bold">✓</div>
                        <span class="ml-2 text-xs font-medium text-gray-600 dark:text-gray-300">Login</span>
                    </div>
                    <div class="w-8 h-px bg-indigo-600"></div>
                    <div class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold ring-2 ring-indigo-100 dark:ring-indigo-900">2</div>
                        <span class="ml-2 text-xs font-bold text-indigo-600 dark:text-indigo-400">OTP</span>
                    </div>
                    <div class="w-8 h-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-400 flex items-center justify-center text-xs font-bold">3</div>
                        <span class="ml-2 text-xs text-gray-400">2FA</span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Verifikasi Kode OTP</h3>
                </div>

                @if(session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-100 flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-4 p-3 rounded-lg bg-blue-50 text-blue-700 text-sm border border-blue-100 flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ session('info') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-100">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-semibold">Terjadi Kesalahan</span>
                        </div>
                        <ul class="list-disc list-inside pl-7">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                <form action="{{ route('admin.verify-otp.submit') }}" method="POST" class="space-y-6" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    
                    <div>
                        <input type="text" name="otp" id="otp" required maxlength="6"
                               class="block w-full text-center font-mono text-3xl tracking-[0.5em] py-4 border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white dark:focus:border-indigo-400"
                               placeholder="******"
                               inputmode="numeric"
                               pattern="[0-9]*"
                               autocomplete="one-time-code"
                               autofocus>
                    </div>

                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform active:scale-[0.98] disabled:opacity-50 disabled:cursor-wait" :disabled="loading">
                        <span x-show="!loading">Verifikasi & Lanjut</span>
                        <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="loading" x-cloak>Memproses...</span>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700 text-center"
                     x-data="otpResendTimer('admin_otp_countdown', 60, {{ (session('success') || session('info')) ? 'true' : 'false' }})">
                    <p class="text-sm text-gray-500 mb-3">Belum menerima email?</p>
                    <form action="{{ route('admin.resend-otp') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="text-sm font-medium transition-colors"
                                :class="countdown > 0 ? 'text-gray-400 cursor-not-allowed' : 'text-indigo-600 hover:text-indigo-700 dark:text-indigo-400'"
                                :disabled="countdown > 0">
                            <span x-show="countdown <= 0">Kirim Ulang Kode OTP</span>
                            <span x-show="countdown > 0">Kirim ulang dalam <span x-text="countdown"></span> detik</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('admin.login') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                &larr; Batalkan & Login Ulang
            </a>
        </div>
    </div>
</div>
@endsection
