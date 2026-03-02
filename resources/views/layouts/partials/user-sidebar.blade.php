<div class="flex h-full flex-col">
    {{-- Logo --}}
    <div class="flex h-16 shrink-0 items-center px-6 border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                <span class="text-white font-bold text-lg">S</span>
            </div>
            <span class="text-xl font-bold text-gray-900 dark:text-white">Sekuota</span>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-1 px-3 py-4">
        <a href="{{ route('dashboard') }}" 
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <a href="{{ route('profile') }}" 
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('profile') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Profil
        </a>

        <a href="{{ route('referral') }}" 
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('referral') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Referral
        </a>

        <a href="{{ route('kyc') }}" 
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('kyc') ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Verifikasi KYC
        </a>
    </nav>

    {{-- User Info --}}
    <div class="border-t border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-medium">
                {{ substr(request()->user()?->name ?? 'U', 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ request()->user()?->name ?? 'User' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ request()->user()?->referral_code }}</p>
            </div>
        </div>
    </div>
</div>
