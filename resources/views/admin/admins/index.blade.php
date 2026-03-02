@extends('layouts.admin')

@section('title', 'Manajemen Admin')

@section('content')
<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Admin</h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-400">Kelola akun administrator dan hak akses mereka.</p>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <a href="{{ route('admin.admins.create') }}" class="inline-flex items-center justify-center rounded-xl border border-transparent bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto gap-2">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Admin
        </a>
    </div>
</div>

@if(session('success'))
    <div class="mb-6 rounded-xl bg-green-50 p-4 border border-green-200 dark:bg-green-900/30 dark:border-green-900/50">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="mb-6 rounded-xl bg-red-50 p-4 border border-red-200 dark:bg-red-900/30 dark:border-red-900/50">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        </div>
    </div>
@endif

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:pl-6">Admin Info</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Role</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Last Login</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse($admins as $admin)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/50">
                                    <span class="font-medium leading-none text-indigo-700 dark:text-indigo-300">{{ substr($admin->name, 0, 1) }}</span>
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $admin->name }}</div>
                                <div class="text-gray-500 dark:text-gray-400 text-xs">{{ $admin->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <div class="flex flex-wrap gap-1">
                            @forelse($admin->roles as $role)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-gray-400 text-xs italic">- No Role -</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @if($admin->status === 'active')
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-300">Active</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-900/30 dark:text-red-300">Suspended</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ $admin->last_login_at ? $admin->last_login_at->diffForHumans() : 'Belum pernah' }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                         @if($admin->id !== Auth::guard('admin')->id())
                            <div class="flex justify-end items-center gap-3">
                                <form action="{{ route('admin.admins.toggle-status', $admin) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-medium {{ $admin->isActive() ? 'text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300' : 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300' }}">
                                        {{ $admin->isActive() ? 'Suspend' : 'Activate' }}
                                    </button>
                                </form>

                                <a href="{{ route('admin.admins.edit', $admin) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Edit</a>
                                
                                <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin hapus admin ini permanen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Hapus</button>
                                </form>
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">Akun Anda</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        Belum ada admin lain.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    {{-- Pagination --}}
    @if($admins->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $admins->links() }}
    </div>
    @endif
</div>
@endsection
