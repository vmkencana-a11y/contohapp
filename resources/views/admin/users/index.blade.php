@extends('layouts.admin')

@section('title', 'Kelola User')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold font-sans text-gray-900 dark:text-white">Kelola Pengguna</h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-400">Daftar semua pengguna terdaftar dan status akun mereka.</p>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
    {{-- Filters & Search --}}
    <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 backdrop-blur-sm">
        <form method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-grow max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="block w-full rounded-xl border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 py-2.5 shadow-sm"
                       placeholder="Cari nama, email, atau ID...">
            </div>
            
            <div class="w-full sm:w-48">
                <select id="admin-users-status-filter" name="status"
                        class="block w-full rounded-xl border-gray-300 py-2.5 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white shadow-sm">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ $currentStatus === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="hidden sm:inline-flex items-center justify-center rounded-xl border border-transparent bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                Terapkan
            </button>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:pl-6">User Info</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ID System</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Terdaftar</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/50">
                                    <span class="font-medium leading-none text-indigo-700 dark:text-indigo-300">{{ substr($user->name, 0, 1) }}</span>
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                <div class="text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono">
                        {{ $user->id }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $user->status->label() }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $user->created_at->format('d M Y, H:i') }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 group flex items-center justify-end gap-1">
                            Detail
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Tidak ada pengguna ditemukan</p>
                            <p class="mt-2 text-sm text-gray-500">Coba ubah filter pencarian atau status.</p>
                            <a href="{{ route('admin.users.index') }}" class="mt-6 text-sm font-medium text-indigo-600 hover:text-indigo-500">Reset Filter</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        {{ $users->links() }}
    </div>
    @endif
</div>

<script @cspNonce>
document.addEventListener('DOMContentLoaded', () => {
    const statusFilter = document.getElementById('admin-users-status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            if (statusFilter.form) statusFilter.form.submit();
        });
    }
});
</script>
@endsection
