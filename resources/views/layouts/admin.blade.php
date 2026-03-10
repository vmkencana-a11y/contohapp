<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} - Sekuota</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- Alpine.js (Loaded via Vite, but ensuring availability) --}}
    <style @cspNonce>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased text-gray-900 dark:text-gray-100" x-data="adminLayout" :class="{ 'dark': darkMode }">
    
    <div>
        {{-- Mobile Sidebar Backdrop --}}
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/80 z-40 lg:hidden" @click="sidebarOpen = false" x-cloak></div>

        {{-- Mobile Sidebar --}}
        <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 z-50 w-72 bg-gray-900 text-white lg:hidden" x-cloak>
            <div class="flex h-16 shrink-0 items-center justify-between px-6 bg-gray-900 border-b border-gray-800">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                     <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-600/20">
                        <span class="text-white font-bold text-lg">S</span>
                    </div>
                    <span class="text-xl font-bold tracking-tight">Sekuota</span>
                </a>
                <button type="button" @click="sidebarOpen = false" class="-m-2.5 p-2.5 text-gray-400 hover:text-white">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex grow flex-col gap-y-5 overflow-y-auto px-6 py-4">
                @include('layouts.partials.admin-sidebar')
            </div>
        </div>

        {{-- Desktop Sidebar --}}
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center border-b border-gray-800">
                     <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-600/20">
                            <span class="text-white font-bold text-lg">S</span>
                        </div>
                        <span class="text-xl font-bold text-white tracking-tight">Sekuota</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-800 text-gray-300 border border-gray-700 ml-1">ADMIN</span>
                    </a>
                </div>
                @include('layouts.partials.admin-sidebar')
            </div>
        </div>

        {{-- Main Content Wrapper --}}
        <div class="lg:pl-72 transition-all duration-300">
            {{-- Top Header --}}
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-200 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden user-select-none" aria-hidden="true"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex-1"></div>
                    
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                    
                        {{-- Notifications --}}
                        <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 relative">
                            <span class="sr-only">View notifications</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            {{-- Notification Dot --}}
                            <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-600 ring-2 ring-white dark:ring-gray-900"></span>
                        </button>

                        {{-- Profile Dropdown --}}
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button type="button" @click="open = !open" class="flex items-center p-1.5 -m-1.5">
                                <span class="sr-only">Open user menu</span>
                                <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold border border-gray-200 dark:border-gray-700">
                                    {{ substr(auth()->guard('admin')->user()?->name ?? 'A', 0, 1) }}
                                </div>
                                <span class="hidden lg:flex lg:items-center">
                                    <span class="ml-4 text-sm font-semibold leading-6 text-gray-900 dark:text-white" aria-hidden="true">{{ auth()->guard('admin')->user()?->name }}</span>
                                    <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </button>

                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                                <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700 mb-1 lg:hidden">
                                     <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ auth()->guard('admin')->user()?->name }}</p>
                                     <p class="text-xs text-gray-500">{{ auth()->guard('admin')->user()?->email }}</p>
                                </div>
                                <a href="{{ route('admin.profile.edit') }}" class="block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700">Profil Saya</a>
                                <a href="{{ route('admin.settings.index') }}" class="block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700">Pengaturan</a>
                                <a href="{{ route('admin.secrets.index') }}" class="block px-3 py-1 text-sm leading-6 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700">Secret Manager</a>
                                <div class="h-px my-1 bg-gray-100 dark:bg-gray-700"></div>
                                <form action="{{ route('admin.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-3 py-1 text-sm leading-6 text-red-600 hover:bg-gray-50 dark:hover:bg-gray-700">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <main class="py-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    
    {{-- Toast Notifications --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    @if(session('success'))
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', () => window.Sekuota?.toast?.success('{{ session('success') }}'));
    </script>
    @endif
</body>
</html>
