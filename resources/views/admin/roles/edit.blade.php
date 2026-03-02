@extends('layouts.admin')

@section('title', 'Edit Role')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.roles.index') }}" class="text-sm text-gray-500 hover:text-primary-600">
            &larr; Kembali ke Daftar Role
        </a>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Role: {{ $role->name }}</h1>
            @if(in_array($role->name, ['super_admin', 'Super Admin']))
                <span class="badge badge-warning">System Role</span>
            @endif
        </div>

        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Role</label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" required
                       class="input-field" {{ in_array($role->name, ['super_admin', 'Super Admin']) ? 'readonly' : '' }}>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deskripsi</label>
                <textarea name="description" rows="3" class="input-field">{{ old('description', $role->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Permissions</label>
                
                <div class="space-y-6">
                    @foreach($permissions as $module => $modulePermissions)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3 capitalize flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                            {{ $module }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($modulePermissions as $perm)
                            <label class="flex items-start gap-2 cursor-pointer group">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                       class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500 bg-gray-50 dark:bg-gray-800 dark:border-gray-600"
                                       {{ in_array($perm->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-primary-600 transition-colors">
                                    {{ $perm->name }}
                                    @if($perm->description)
                                    <span class="block text-xs text-gray-500">{{ $perm->description }}</span>
                                    @endif
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.roles.index') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
