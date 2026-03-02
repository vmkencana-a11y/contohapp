@extends('layouts.admin')

@section('title', 'Manajemen Role')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Role</h1>
        <a href="{{ route('admin.roles.create') }}" class="btn-primary">
            + Tambah Role
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 text-green-700 p-4 rounded-lg border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-700 p-4 rounded-lg border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase font-medium text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-4">Nama Role</th>
                        <th class="px-6 py-4">Deskripsi</th>
                        <th class="px-6 py-4 text-center">Admin</th>
                        <th class="px-6 py-4 text-center">Permission</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($roles as $role)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $role->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $role->description ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $role->admins_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                {{ $role->permissions_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-primary-600 hover:text-primary-700 font-medium">Edit</a>
                            
                            @if(!in_array($role->name, ['super_admin', 'Super Admin']) && $role->admins_count === 0)
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin hapus role ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada role.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
