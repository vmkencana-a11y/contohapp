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
                        <div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold ring-2 ring-indigo-100 dark:ring-indigo-900">1</div>
                        <span class="ml-2 text-xs font-bold text-indigo-600 dark:text-indigo-400">Login</span>
                    </div>
                    <div class="w-8 h-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="flex items-center">
                         <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-400 flex items-center justify-center text-xs font-bold">2</div>
                        <span class="ml-2 text-xs font-medium text-gray-400">OTP</span>
                    </div>
                    <div class="w-8 h-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-400 flex items-center justify-center text-xs font-bold">3</div>
                        <span class="ml-2 text-xs text-gray-400">2FA</span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                @if(session('error'))
                <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-600 text-sm border border-red-100 flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('error') }}
                </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-700 text-sm border border-red-100">
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

                <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-6" x-data="{ loading: false, showPassword: false }" @submit="loading = true">
                    @csrf
                    
                    <div class="space-y-1">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" required 
                                   value="{{ old('email') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-lg h-10 dark:bg-gray-800 dark:border-gray-700 dark:text-white placeholder-gray-400" 
                                   placeholder="email@sekuota.com">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required 
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 pr-10 sm:text-sm border-gray-300 rounded-lg h-10 dark:bg-gray-800 dark:border-gray-700 dark:text-white placeholder-gray-400" 
                                   placeholder="••••••••">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" @click="showPassword = !showPassword">
                                <svg x-show="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </div>
                        </div>
                    </div>



                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform active:scale-[0.98] disabled:opacity-50 disabled:cursor-wait btn" :disabled="loading">
                        <span x-show="!loading">Masuk</span>
                        <svg x-show="loading" x-cloak class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="loading" x-cloak>Memproses...</span>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700 text-center">
                    <p class="text-sm text-gray-500">
                        &larr; <a href="{{ route('home') }}" class="font-medium text-indigo-600 hover:text-indigo-500 transition-colors">Kembali ke Beranda</a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="mt-8 text-center">
             <p class="text-sm text-gray-400">
                &copy; {{ date('Y') }} Sekuota Admin Console
            </p>
        </div>
    </div>
</div>
@endsection
