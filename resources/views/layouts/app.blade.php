<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - Sekuota</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style @cspNonce>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900" x-data="{ sidebarOpen: false }">
    <div class="min-h-full">
        {{-- Mobile Sidebar Overlay --}}
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-gray-600/75" @click="sidebarOpen = false"></div>
            <div class="fixed inset-y-0 left-0 flex w-64 flex-col bg-white dark:bg-gray-800 shadow-xl">
                @include('layouts.partials.user-sidebar')
            </div>
        </div>

        {{-- Desktop Sidebar --}}
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <div class="flex min-h-0 flex-1 flex-col bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                @include('layouts.partials.user-sidebar')
            </div>
        </div>

        {{-- Main Content --}}
        <div class="lg:pl-64">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ auth()->user()?->masked_email ?? 'User' }}</span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400">Logout</button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="py-6 px-4 sm:px-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toast Container --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    @if(session('success'))
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', () => window.Sekuota?.toast?.success('{{ session('success') }}'));
    </script>
    @endif
    @if(session('error'))
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', () => window.Sekuota?.toast?.error('{{ session('error') }}'));
    </script>
    @endif
</body>
</html>
