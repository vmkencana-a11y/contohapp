@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Total Users -->
        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md dark:ring-gray-700">
            <dt>
                <div class="absolute rounded-xl bg-blue-50 dark:bg-blue-900/20 p-3">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Pengguna</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-1 sm:pb-2">
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_users'] ?? 0) }}</p>
                {{-- <p class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                    <svg class="h-5 w-5 flex-shrink-0 self-center text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                    </svg>
                    <span class="sr-only"> Increased by </span>
                    122
                </p> --}}
            </dd>
        </div>

        <!-- Active Users -->
        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md dark:ring-gray-700">
            <dt>
                <div class="absolute rounded-xl bg-green-50 dark:bg-green-900/20 p-3">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500 dark:text-gray-400">Pengguna Aktif</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-1 sm:pb-2">
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['active_users'] ?? 0) }}</p>
            </dd>
        </div>

        <!-- Pending KYC -->
        <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md dark:ring-gray-700">
            <dt>
                <div class="absolute rounded-xl bg-yellow-50 dark:bg-yellow-900/20 p-3">
                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500 dark:text-gray-400">Verifikasi KYC Tertunda</p>
            </dt>
            <dd class="ml-16 flex items-baseline pb-1 sm:pb-2">
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['pending_kyc'] ?? 0) }}</p>
                @if(($stats['pending_kyc'] ?? 0) > 0)
                <a href="{{ route('admin.kyc.index') }}" class="absolute bottom-6 right-6 text-xs font-medium text-indigo-600 hover:text-indigo-500 hover:underline">
                    Review &rarr;
                </a>
                @endif
            </dd>
        </div>
    </div>

    {{-- Activity & Lists --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        {{-- Recent Users Table --}}
        <div class="overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Pengguna Terbaru</h3>
                <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Lihat Semua</a>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <ul role="list" class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($recentUsers as $user)
                    <li class="relative flex justify-between gap-x-6 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex min-w-0 gap-x-4">
                            <div class="h-10 w-10 flex-none rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 flex items-center justify-center font-bold text-sm">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div class="min-w-0 flex-auto">
                                <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">
                                    <a href="{{ route('admin.users.show', $user) }}">
                                        <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                        {{ $user->name }}
                                    </a>
                                </p>
                                <p class="mt-1 flex text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    <a href="mailto:{{ $user->email }}" class="relative truncate hover:underline">{{ $user->email }}</a>
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end">
                            <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $user->created_at->diffForHumans() }}</p>
                            <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $user->status === 'active' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10' }}">
                                {{ ucfirst($user->status->value ?? $user->status) }}
                            </span>
                        </div>
                    </li>
                    @empty
                    <li class="px-6 py-8 text-center bg-gray-50 dark:bg-gray-800/50">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum ada pengguna baru</p>
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Pending KYC Table --}}
        <div class="overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
             <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">KYC Menunggu Review</h3>
                <a href="{{ route('admin.kyc.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Lihat Semua</a>
            </div>
            <div class="max-h-96 overflow-y-auto">
                 <ul role="list" class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($pendingKyc as $kyc)
                    <li class="relative flex justify-between gap-x-6 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex min-w-0 gap-x-4">
                             <div class="h-10 w-10 flex-none rounded-lg bg-gray-100 dark:bg-gray-700 object-cover flex items-center justify-center overflow-hidden">
                                @if($kyc->id_card_path)
                                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0 flex-auto">
                                <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">
                                    <a href="{{ route('admin.kyc.show', $kyc) }}">
                                        <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                        {{ $kyc->user->name }}
                                    </a>
                                </p>
                                <p class="mt-1 flex text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    Diajukan {{ $kyc->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-x-2">
                             <a href="{{ route('admin.kyc.show', $kyc) }}" class="rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 z-10 relative">Review</a>
                        </div>
                    </li>
                    @empty
                    <li class="px-6 py-8 text-center bg-gray-50 dark:bg-gray-800/50">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada antrian KYC</p>
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
